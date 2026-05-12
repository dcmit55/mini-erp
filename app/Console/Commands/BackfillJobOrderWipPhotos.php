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
 * Backfill wip_photos for existing Job Orders that have lark_record_id but no wip_photos.
 * Run this once after adding the wip_photos column, or to refresh photo data.
 *
 * Usage:
 *   php artisan joborder:backfill-wip-photos
 *   php artisan joborder:backfill-wip-photos --limit=50    (process in batches)
 *   php artisan joborder:backfill-wip-photos --force       (re-download even if wip_photos set)
 */
class BackfillJobOrderWipPhotos extends Command
{
    protected $signature = 'joborder:backfill-wip-photos
                            {--limit=100 : Max number of JOs to process in one run}
                            {--force : Re-download even if wip_photos already set}';

    protected $description = 'Backfill wip_photos JSON column for existing Job Orders from Lark';

    public function handle(LarkApiClient $apiClient, JobOrderTransformer $transformer): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = JobOrder::whereNotNull('lark_record_id');
        if (!$force) {
            $query->whereNull('wip_photos');
        }

        $total = $query->count();
        $this->info("Found {$total} JOs to process (limit: {$limit})");

        if ($total === 0) {
            $this->info('Nothing to backfill. All JOs already have wip_photos.');
            return 0;
        }

        // Fetch target JOs from DB
        $jobs = $query
            ->select(['id', 'lark_record_id', 'wip_photos'])
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
                // Download photos (no _exists flag → full download)
                $paths = $transformer->normalizeWipPhotosPublic($dto->wipPhotoRaw);

                $jo->update(['wip_photos' => $paths]);
                $done++;
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
