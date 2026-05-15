<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Production\JobOrder;
use App\Services\Lark\LarkApiClient;
use App\Services\Lark\LarkJobOrderSyncService;
use App\DTO\LarkJobOrderDTO;
use App\Transformers\JobOrderTransformer;
use Illuminate\Support\Facades\Log;

/**
 * Backfill wip_photos and final_image for existing Job Orders from Lark.
 * Targets records that have NULL photos OR still store Lark API URLs.
 *
 * Usage:
 *   php artisan joborder:backfill-wip-photos
 *   php artisan joborder:backfill-wip-photos --limit=50    (process in batches)
 *   php artisan joborder:backfill-wip-photos --force       (re-download all, even if already local)
 */
class BackfillJobOrderWipPhotos extends Command
{
    protected $signature = 'joborder:backfill-wip-photos
                            {--limit=100 : Max number of JOs to process in one run}
                            {--force : Re-download even if wip_photos already set}';

    protected $description = 'Backfill wip_photos and final_image for existing Job Orders from Lark (downloads to local storage)';

    public function handle(LarkApiClient $apiClient, JobOrderTransformer $transformer): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = JobOrder::whereNotNull('lark_record_id');
        if (!$force) {
            // Target records that need photos downloaded:
            // (a) wip_photos NULL or still has Lark API URLs
            // (b) final_image still has a Lark API URL (not NULL — NULL = legitimately no image)
            $query->where(function ($q) {
                $q->whereNull('wip_photos')->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(wip_photos, '$[0]')) LIKE 'http%'")->orWhere('final_image', 'LIKE', 'http%');
            });
        }

        $total = $query->count();
        $this->info("Found {$total} JOs to process (limit: {$limit})");

        if ($total === 0) {
            $this->info('Nothing to backfill. All JOs already have wip_photos.');
            return 0;
        }

        // Fetch target JOs from DB
        $jobs = $query
            ->select(['id', 'lark_record_id', 'wip_photos', 'final_image'])
            ->limit($limit)
            ->get();
        $targetLarkIds = $jobs->pluck('lark_record_id')->filter()->flip(); // record_id => index map

        $appToken = config('lark.base_id');
        $tableId = config('lark.job_orders.table_id');

        $this->info('Fetching all records from Lark...');
        // Fetch all Lark records once, then filter by our target IDs
        $allRecords = $apiClient->fetchRecords($appToken, $tableId, null, 'id');
        $recordMap = collect($allRecords)->keyBy('record_id');

        $done = 0;
        $errors = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        foreach ($jobs as $jo) {
            try {
                $rawRecord = $recordMap->get($jo->lark_record_id);

                if (!$rawRecord) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $dto = new LarkJobOrderDTO($rawRecord);

                $updates = [];

                // ── wip_photos ─────────────────────────────────────────────────────
                $needsWip = $force || empty($jo->wip_photos) || str_starts_with((string) ($jo->wip_photos[0] ?? ''), 'http');

                if ($needsWip) {
                    $paths = $transformer->normalizeWipPhotosPublic($dto->wipPhotoRaw);
                    $updates['wip_photos'] = $paths;

                    // Extract file_tokens so future incremental syncs skip re-download
                    $tokens = [];
                    if (!empty($dto->wipPhotoRaw)) {
                        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp'];
                        foreach ($dto->wipPhotoRaw as $attachment) {
                            $mime = $attachment['mime_type'] ?? ($attachment['type'] ?? '');
                            if ($mime && str_starts_with($mime, 'video/')) {
                                continue;
                            }
                            $ext = strtolower(pathinfo($attachment['name'] ?? '', PATHINFO_EXTENSION));
                            if (in_array($ext, $videoExtensions)) {
                                continue;
                            }
                            if ($token = $attachment['file_token'] ?? null) {
                                $tokens[] = $token;
                            }
                        }
                        sort($tokens);
                    }
                    $updates['lark_photo_tokens'] = $tokens ?: null;
                }

                // ── final_image ────────────────────────────────────────────────────
                $needsFinal = $force || empty($jo->final_image) || str_starts_with((string) $jo->final_image, 'http');

                if ($needsFinal) {
                    $finalPath = $transformer->normalizeFinalImagePublic($dto->finalImageRaw);
                    $updates['final_image'] = $finalPath;
                }

                if (!empty($updates)) {
                    $jo->update($updates);
                    $done++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::warning('BackfillWipPhotos: failed for JO #' . $jo->id, ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $remaining = max(0, $total - $jobs->count());
        $this->info("Done: {$done} updated, {$skipped} skipped (not in Lark), {$errors} errors.");
        if ($remaining > 0) {
            $this->info("{$remaining} more JOs remaining — run the command again to continue.");
        }

        return 0;
    }
}
