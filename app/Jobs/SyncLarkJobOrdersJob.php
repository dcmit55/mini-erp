<?php

namespace App\Jobs;

use App\Services\Lark\LarkJobOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncLarkJobOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max attempts before failing */
    public int $tries = 1;

    /** Timeout in seconds (10 minutes) */
    public int $timeout = 600;

    public function handle(LarkJobOrderSyncService $syncService): void
    {
        Cache::put('lark_jo_sync_status', 'running', now()->addMinutes(15));

        try {
            $stats = $syncService->sync();

            Cache::put('lark_jo_sync_status', 'done', now()->addMinutes(30));
            Cache::put('lark_jo_sync_stats', $stats, now()->addMinutes(30));

            Log::info('SyncLarkJobOrdersJob completed', $stats);
        } catch (\Exception $e) {
            Cache::put('lark_jo_sync_status', 'error:' . $e->getMessage(), now()->addMinutes(30));
            Log::error('SyncLarkJobOrdersJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
