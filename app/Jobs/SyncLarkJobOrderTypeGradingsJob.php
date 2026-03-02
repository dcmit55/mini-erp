<?php

namespace App\Jobs;

use App\Services\Lark\LarkJobOrderTypeGradingSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncLarkJobOrderTypeGradingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1;

    public const CACHE_KEY    = 'lark_type_grading_sync_status';
    public const CACHE_TTL    = 300; // 5 menit

    public function handle(LarkJobOrderTypeGradingSyncService $service): void
    {
        Cache::put(self::CACHE_KEY, ['status' => 'running', 'started_at' => now()->toIso8601String()], self::CACHE_TTL);

        try {
            $stats = $service->sync();

            Cache::put(self::CACHE_KEY, [
                'status'     => 'done',
                'stats'      => $stats,
                'finished_at' => now()->toIso8601String(),
            ], self::CACHE_TTL);

            Log::info('SyncLarkJobOrderTypeGradingsJob completed', $stats);
        } catch (\Exception $e) {
            Cache::put(self::CACHE_KEY, [
                'status' => 'failed',
                'error'  => $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
            ], self::CACHE_TTL);

            Log::error('SyncLarkJobOrderTypeGradingsJob failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }
}
