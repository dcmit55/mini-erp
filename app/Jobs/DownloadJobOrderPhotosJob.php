<?php

namespace App\Jobs;

use App\Models\Production\JobOrder;
use App\Services\Lark\LarkApiClient;
use App\Transformers\JobOrderTransformer;
use App\DTO\LarkJobOrderDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DownloadJobOrderPhotosJob
 *
 * Downloads WIP photos for a single Job Order from Lark to local storage.
 * Dispatched by LarkJobOrderSyncService only when photo tokens have changed.
 *
 * Architecture:
 *  - sync() (web request) = metadata only, fast, no downloads
 *  - This job = actual image download, queued/background
 *
 * When QUEUE_CONNECTION=sync, the controller dispatches this via app()->terminating()
 * so it runs AFTER the HTTP response is sent — UI is never blocked.
 */
class DownloadJobOrderPhotosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    /**
     * @param string     $jobOrderId      Local JobOrder->id
     * @param string     $larkRecordId    Lark record_id (for re-fetch if needed)
     * @param array      $wipPhotoRaw     Raw attachment array from Lark (already fetched during sync)
     * @param array      $newTokens       file_token list that triggered this job
     */
    public function __construct(public readonly string $jobOrderId, public readonly string $larkRecordId, public readonly array $wipPhotoRaw, public readonly array $newTokens) {}

    public function handle(LarkApiClient $apiClient, JobOrderTransformer $transformer): void
    {
        $jobOrder = JobOrder::find($this->jobOrderId);

        if (!$jobOrder) {
            Log::warning('DownloadJobOrderPhotosJob: JobOrder not found', ['id' => $this->jobOrderId]);
            return;
        }

        Log::info('DownloadJobOrderPhotosJob: starting download', [
            'job_order_id' => $this->jobOrderId,
            'photo_count' => count($this->wipPhotoRaw),
        ]);

        // Download all non-video WIP photos to local storage
        $paths = $transformer->normalizeWipPhotosPublic($this->wipPhotoRaw);

        if (empty($paths)) {
            Log::warning('DownloadJobOrderPhotosJob: no photos downloaded', [
                'job_order_id' => $this->jobOrderId,
            ]);
            // Still update tokens so we don't retry endlessly
            $jobOrder->update(['lark_photo_tokens' => $this->newTokens]);
            return;
        }

        // Delete old local files that are no longer referenced
        $oldPaths = $jobOrder->wip_photos ?? [];
        foreach ($oldPaths as $oldPath) {
            if ($oldPath && !str_starts_with($oldPath, 'http') && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
                Log::debug('DownloadJobOrderPhotosJob: deleted old file', ['path' => $oldPath]);
            }
        }

        $jobOrder->update([
            'wip_photos' => $paths,
            'lark_photo_tokens' => $this->newTokens,
        ]);

        Log::info('DownloadJobOrderPhotosJob: completed', [
            'job_order_id' => $this->jobOrderId,
            'paths' => $paths,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DownloadJobOrderPhotosJob: failed', [
            'job_order_id' => $this->jobOrderId,
            'error' => $e->getMessage(),
        ]);
    }
}
