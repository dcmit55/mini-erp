<?php

namespace App\Services\Lark;

use App\DTO\LarkJobOrderDTO;
use App\Jobs\DownloadJobOrderPhotosJob;
use App\Transformers\JobOrderTransformer;
use App\Models\Production\JobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Job Order Sync Service — Incremental Architecture
 *
 * sync() fetches ALL metadata from Lark (fast — text fields only).
 * Image downloads are queued per-job-order ONLY when photo tokens changed.
 *
 * Change detection uses Lark attachment file_token (stable, unique per file):
 *   - Same tokens → skip download entirely
 *   - New/changed tokens → dispatch DownloadJobOrderPhotosJob (background)
 *
 * With QUEUE_CONNECTION=sync the job runs via app()->terminating() — after HTTP response.
 * With a real queue driver it runs in a background worker.
 */
class LarkJobOrderSyncService
{
    private LarkApiClient $apiClient;
    private JobOrderTransformer $transformer;
    private string $appToken;
    private string $tableId;
    private ?string $viewId;

    public function __construct(LarkApiClient $apiClient, JobOrderTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        // Load from config
        $this->appToken = config('lark.base_id'); // Same base_id as projects
        $this->tableId = config('lark.job_orders.table_id');
        $this->viewId = config('lark.job_orders.view_id');
    }

    /**
     * Incremental sync — metadata fast pass + photo change detection.
     *
     * Steps:
     *  1. Fetch all records from Lark (metadata + attachment tokens, no binary download)
     *  2. Pre-load existing DB records into memory (1 query)
     *  3. For each Lark record:
     *     a. Upsert metadata (name, status, project, department, delivery_date)
     *     b. Compare wip_photos file_tokens vs stored lark_photo_tokens
     *     c. If tokens CHANGED or missing → dispatch DownloadJobOrderPhotosJob (queued)
     *     d. If tokens SAME → skip image entirely (zero API calls for photos)
     *  4. Soft-delete records no longer in Lark
     *
     * @return array Sync statistics
     */
    public function sync(): array
    {
        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'photos_queued' => 0,
            'photos_skipped' => 0,
            'deactivated' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        DB::beginTransaction();

        try {
            Log::info('LarkJobOrderSyncService: starting incremental sync');

            // ── Step 1: Fetch all records (metadata + attachment metadata, no binary) ──────
            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId, 'name');
            $stats['fetched'] = count($rawRecords);

            Log::info('LarkJobOrderSyncService: records fetched from Lark', [
                'count' => $stats['fetched'],
            ]);

            // ── Step 2: Pre-load all departments + existing job orders ────────────────────
            $this->transformer->preloadDepartments();

            // Load existing records keyed by lark_record_id (1 query, no N+1)
            $existingMap = JobOrder::whereNotNull('lark_record_id')
                ->get([
                    'id',
                    'lark_record_id',
                    'final_image',
                    'wip_photos',
                    'project_images',
                    'latest_designs',
                    'final_images',
                    'lark_photo_tokens', // stored file_tokens from last successful download
                ])
                ->keyBy('lark_record_id');

            // Jobs to dispatch AFTER the DB transaction commits
            $photoJobs = [];
            $larkRecordIds = [];

            // ── Step 3: Process each record ───────────────────────────────────────────────
            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkJobOrderDTO($rawRecord);
                    $larkRecordIds[] = $dto->recordId;

                    $existing = $existingMap[$dto->recordId] ?? null;

                    // Build existingImages with only LOCAL paths (Lark URLs are treated as undownloaded)
                    $existingImages = $existing
                        ? [
                            'final_image' => $existing->final_image,
                            'wip_photos' => $existing->wip_photos ?? [],
                            'project_images' => $existing->project_images,
                            'latest_designs' => $existing->latest_designs,
                            'final_images' => $existing->final_images,
                        ]
                        : [];

                    // Transform metadata ONLY — zero HTTP downloads, fast
                    $data = $this->transformer->transform($dto, $existingImages, downloadImages: false);

                    $this->transformer->validate($data);

                    $departmentIds = $data['_department_ids'] ?? [];
                    unset($data['_department_ids']);

                    $jobOrder = JobOrder::updateOrCreate(['lark_record_id' => $dto->recordId], array_merge($data, ['source_by' => 'Sync from Lark', 'last_sync_at' => now()]));

                    if (!empty($departmentIds)) {
                        $jobOrder->departments()->sync($departmentIds);
                    } else {
                        $jobOrder->departments()->detach();
                    }

                    $jobOrder->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

                    // ── Photo change detection ─────────────────────────────────────────────
                    // Extract file_tokens from this Lark record's wip_photos attachments
                    $currentTokens = $this->extractPhotoTokens($dto->wipPhotoRaw);

                    // Stored tokens from last successful download
                    $storedTokens = $existing?->lark_photo_tokens ?? [];

                    // Compare: skip if tokens identical AND local paths already exist
                    $hasLocalPhotos = !empty($existingImages['wip_photos']) && is_array($existingImages['wip_photos']) && !str_starts_with((string) ($existingImages['wip_photos'][0] ?? ''), 'http');

                    $tokensChanged = $currentTokens !== $storedTokens;
                    $needsDownload = !empty($dto->wipPhotoRaw) && ($tokensChanged || !$hasLocalPhotos);

                    if ($needsDownload) {
                        // Queue download — runs background (or post-response on sync driver)
                        $photoJobs[] = [
                            'job_order_id' => $jobOrder->id,
                            'lark_record_id' => $dto->recordId,
                            'wip_photo_raw' => $dto->wipPhotoRaw,
                            'new_tokens' => $currentTokens,
                        ];
                        $stats['photos_queued']++;

                        Log::debug('LarkJobOrderSyncService: photo download queued', [
                            'jo_id' => $jobOrder->id,
                            'tokens_changed' => $tokensChanged,
                            'had_local' => $hasLocalPhotos,
                        ]);
                    } else {
                        $stats['photos_skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $dto->recordId ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('LarkJobOrderSyncService: record failed', [
                        'record_id' => $dto->recordId ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ── Step 4: Soft-delete job orders no longer in Lark ─────────────────────────
            $deactivated = JobOrder::whereNotNull('lark_record_id')->whereNotIn('lark_record_id', $larkRecordIds)->whereNull('deleted_at')->get();

            foreach ($deactivated as $jobOrder) {
                $jobOrder->delete();
                $stats['deactivated']++;
                Log::info('LarkJobOrderSyncService: deactivated', ['id' => $jobOrder->lark_record_id]);
            }

            DB::commit();

            // ── Dispatch photo jobs AFTER commit ──────────────────────────────────────────
            // With real queue: runs in background worker immediately
            // With QUEUE_CONNECTION=sync: runs via app()->terminating() (after HTTP response)
            foreach ($photoJobs as $jobData) {
                $job = new DownloadJobOrderPhotosJob($jobData['job_order_id'], $jobData['lark_record_id'], $jobData['wip_photo_raw'], $jobData['new_tokens']);

                if (config('queue.default') === 'sync') {
                    // Defer until after HTTP response — never block the UI
                    app()->terminating(fn() => dispatch_sync($job));
                } else {
                    dispatch($job);
                }
            }

            Log::info('LarkJobOrderSyncService: incremental sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LarkJobOrderSyncService: sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract an ordered array of file_tokens from a Lark attachment array.
     * Tokens are stable identifiers — they don't change unless the file is replaced.
     * Sorting ensures comparison is order-independent.
     *
     * @param array|null $attachments  Raw attachment array from Lark DTO
     * @return array  Sorted array of file_token strings
     */
    private function extractPhotoTokens(?array $attachments): array
    {
        if (empty($attachments)) {
            return [];
        }

        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp'];
        $tokens = [];

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            // Skip videos by mime type
            $mime = $attachment['mime_type'] ?? ($attachment['type'] ?? '');
            if ($mime && str_starts_with($mime, 'video/')) {
                continue;
            }

            // Skip videos by filename extension
            $ext = strtolower(pathinfo($attachment['name'] ?? '', PATHINFO_EXTENSION));
            if (in_array($ext, $videoExtensions)) {
                continue;
            }

            $token = $attachment['file_token'] ?? null;
            if ($token) {
                $tokens[] = $token;
            }
        }

        sort($tokens); // order-independent comparison
        return $tokens;
    }

    /**
     * Get raw response untuk debugging/audit
     */
    public function getRawResponse(): array
    {
        return $this->apiClient->fetchRawResponse($this->appToken, $this->tableId, $this->viewId, 'name');
    }

    /**
     * Sync single job order by lark_record_id
     */
    public function syncSingle(string $larkRecordId): JobOrder
    {
        // Fetch all and find specific record
        $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId, 'name');

        $rawRecord = collect($rawRecords)->firstWhere('record_id', $larkRecordId);

        if (!$rawRecord) {
            throw new \Exception("Job Order with lark_record_id {$larkRecordId} not found in Lark");
        }

        DB::beginTransaction();

        try {
            $dto = new LarkJobOrderDTO($rawRecord);

            // Find existing to preserve images if needed (though transformer handles it)
            $existing = JobOrder::where('lark_record_id', $larkRecordId)->first();
            $existingImages = $existing
                ? [
                    'final_image' => $existing->final_image,
                    'wip_photos' => $existing->wip_photos ?? [],
                    'project_images' => $existing->project_images,
                    'latest_designs' => $existing->latest_designs,
                    'final_images' => $existing->final_images,
                ]
                : [];

            $data = $this->transformer->transform($dto, $existingImages, downloadImages: true);
            $this->transformer->validate($data);

            // Extract department IDs for pivot sync
            $departmentIds = $data['_department_ids'] ?? [];
            unset($data['_department_ids']);

            $jobOrder = JobOrder::updateOrCreate(
                ['lark_record_id' => $larkRecordId],
                array_merge($data, [
                    'source_by' => 'Sync from Lark',
                    'last_sync_at' => now(),
                ]),
            );

            // Sync departments via pivot table
            if (!empty($departmentIds)) {
                $jobOrder->departments()->sync($departmentIds);
            }

            DB::commit();

            return $jobOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
