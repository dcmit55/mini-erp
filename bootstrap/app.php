<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withSchedule(function (Schedule $schedule) {
        // Auto goods-out for approved material requests (midnight & noon)
        $schedule->command('material-request:auto-goods-out')
            ->twiceDaily(0, 12)
            ->timezone('Asia/Singapore')
            ->onFailure(fn() => \Log::error('Failed to run auto-goods-out scheduler'))
            ->onSuccess(fn() => \Log::info('Auto-goods-out scheduler completed'));

        // Delivery date alerts daily at 8 AM
        $schedule->command('job-orders:check-delivery-alerts')
            ->dailyAt('08:00')
            ->timezone('Asia/Singapore')
            ->withoutOverlapping(10)
            ->onFailure(fn() => \Log::error('Failed to run delivery alerts scheduler'))
            ->onSuccess(fn() => \Log::info('Delivery alerts scheduler completed'));

        // Cleanup old audit logs weekly (Sunday 2 AM)
        $schedule->call(function () {
            \DB::table('audits')->where('created_at', '<', now()->subMonths(6))->delete();
        })->weekly()->sundays()->at('02:00')->timezone('Asia/Singapore');

        // Auto Sync Fingerspot setiap 5 menit
        $schedule->command('hr:sync-fingerspot --days=2')
            ->everyFiveMinutes()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(4)
            ->onFailure(fn() => \Log::error('hr:sync-fingerspot gagal'));

        // Auto-pause timing saat jam break, auto-resume setelah break selesai
        $schedule->command('timing:auto-break-pause')
            ->everyMinute()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(1)
            ->onFailure(fn() => \Log::error('timing:auto-break-pause gagal'));
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.token'     => \App\Http\Middleware\ApiToken::class,
            'webhook.token' => \App\Http\Middleware\WebhookToken::class,
            'webhook.hmac'  => \App\Http\Middleware\VerifyWebhookHMAC::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
