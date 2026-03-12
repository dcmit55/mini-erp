<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [Commands\FetchLarkJobOrders::class, Commands\TestLarkConnection::class];

    /**
     * Define the application's command schedule.
     *
     * cPanel Cron Job Setup:
     * ----------------------
     * * * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
     *
     * OR for logging:
     * * * * * * cd /home/username/public_html && php artisan schedule:run >> /home/username/storage/logs/scheduler.log 2>&1
     *
     * IMPORTANT: Replace /home/username/public_html with your actual path
     * Get path: run `pwd` in cPanel Terminal
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Auto goods-out for approved material requests
        // Runs twice daily at midnight and noon
        $schedule
            ->command('material-request:auto-goods-out')
            ->twiceDaily(0, 12)
            ->timezone('Asia/Singapore')
            ->onFailure(function () {
                \Log::error('Failed to run auto-goods-out scheduler');
            })
            ->onSuccess(function () {
                \Log::info('Auto-goods-out scheduler completed');
            });

        // Check delivery date alerts daily at 8 AM Singapore time
        // Sends Pusher notifications for job orders with delivery_date = today + 2 days
        $schedule
            ->command('job-orders:check-delivery-alerts')
            ->dailyAt('08:00')
            ->timezone('Asia/Singapore')
            ->withoutOverlapping(10) // Prevent concurrent runs, 10 min expiry
            ->onFailure(function () {
                \Log::error('Failed to run delivery alerts scheduler');
            })
            ->onSuccess(function () {
                \Log::info('Delivery alerts scheduler completed');
            });

        // Cleanup old logs weekly (Sunday 2 AM)
        $schedule
            ->call(function () {
                \DB::table('audits')
                    ->where('created_at', '<', now()->subMonths(6))
                    ->delete();
            })
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->timezone('Asia/Singapore');

        // ─── Auto Sync Fingerspot (setiap 5 menit) ──────────────────────────
        $schedule->command('hr:sync-fingerspot --days=2')
            ->everyFiveMinutes()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(4)
            ->onFailure(fn() => \Log::error('hr:sync-fingerspot gagal'));
        // ────────────────────────────────────────────────────────────────────

        // ─── Timing Module Pipeline ─────────────────────────────────────────
        // Jalankan setelah sync fingerspot selesai (misal sync dijadwal jam 23:30)
        // Pipeline: Parse → Build Sessions → Classify Breaks → Next Schedule → Anomalies

        // Tahap 1: Parse fingerprint logs (setiap hari 23:45)
        $schedule->command('hr:parse-fingerprint --date=' . now()->toDateString())
            ->dailyAt('23:45')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(15)
            ->onFailure(fn() => \Log::error('hr:parse-fingerprint gagal'));

        // Tahap 2: Build sessions (setiap hari 23:50)
        $schedule->command('hr:build-sessions --date=' . now()->toDateString())
            ->dailyAt('23:50')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(15)
            ->onFailure(fn() => \Log::error('hr:build-sessions gagal'));

        // Tahap 3: Classify breaks (setiap hari 23:55)
        $schedule->command('hr:classify-breaks --date=' . now()->toDateString())
            ->dailyAt('23:55')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(15)
            ->onFailure(fn() => \Log::error('hr:classify-breaks gagal'));

        // Tahap 4 & 5: Next schedule + deteksi anomali (dini hari keesokan)
        $schedule->command('hr:calc-next-schedule --date=' . now()->toDateString())
            ->dailyAt('00:05')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(10)
            ->onFailure(fn() => \Log::error('hr:calc-next-schedule gagal'));

        $schedule->command('hr:detect-anomalies --date=' . now()->subDay()->toDateString())
            ->dailyAt('00:10')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(10)
            ->onFailure(fn() => \Log::error('hr:detect-anomalies gagal'));
        // ────────────────────────────────────────────────────────────────────
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
