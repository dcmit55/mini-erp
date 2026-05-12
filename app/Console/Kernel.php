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
        // DISABLED: 2x24h auto goods-out has been turned off
        // Risk: auto goods-out used price=0 batches when inventory had no cost data yet
        // To re-enable, uncomment the block below
        /*
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
        */

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
        $schedule->command('hr:sync-fingerspot --days=2')->everyFiveMinutes()->timezone('Asia/Jakarta')->withoutOverlapping(4)->onFailure(fn() => \Log::error('hr:sync-fingerspot gagal'));
        // ────────────────────────────────────────────────────────────────────

        // ─── Auto Break Pause/Resume Timing (setiap menit) ──────────────────
        $schedule->command('timing:auto-break-pause')->everyMinute()->timezone('Asia/Jakarta')->withoutOverlapping(1)->onFailure(fn() => \Log::error('timing:auto-break-pause gagal'));
        // ────────────────────────────────────────────────────────────────────

        // ─── Auto Stop Timing saat Clock-Out (setiap 5 menit, safety net) ───
        // Safety net jika webhook fingerspot tidak terkirim (jaringan, downtime).
        // Primary trigger: WebhookController::autoStopTodayTimings() saat tap OUT.
        $schedule->command('timing:auto-stop-clockout')->everyFiveMinutes()->timezone('Asia/Jakarta')->withoutOverlapping(4)->onFailure(fn() => \Log::error('timing:auto-stop-clockout gagal'));
        // ────────────────────────────────────────────────────────────────────

        // ─── Expire Warning Letters (nightly 23:59 WIB) ──────────────────────
        // Tandai SP yang sudah melewati valid_until → status = expired
        // Cek recovery karyawan: jika semua SP expired → karyawan bisa mulai dari SP1 lagi
        $schedule->command('hr:expire-warning-letters')->dailyAt('23:59')->timezone('Asia/Jakarta')->withoutOverlapping(5)->onFailure(fn() => \Log::error('hr:expire-warning-letters gagal'))->onSuccess(fn() => \Log::info('hr:expire-warning-letters selesai'));
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
