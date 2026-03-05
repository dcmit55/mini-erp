<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Admin\User;
use App\Models\Production\JobOrder;
use App\Observers\JobOrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'App\Models\User' => User::class,
        ]);

        // ===== RATE LIMITER: Webhook Fingerprint =====
        // 60 request per menit, dikelompokkan per IP
        // Untuk menyesuaikan batas, ubah angka 60 di bawah
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    Log::warning('Webhook: Rate limit exceeded', [
                        'ip'          => $request->ip(),
                        'uuid'        => $request->route('uuid'),
                        'user_agent'  => $request->userAgent(),
                        'retry_after' => $headers['Retry-After'] ?? null,
                    ]);

                    return response()->json([
                        'success'     => false,
                        'message'     => 'Too many requests. Please try again later.',
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                });
        });

        // Register JobOrder Observer for auto goods-out and delivery date notifications
        JobOrder::observe(JobOrderObserver::class);
        // $link = public_path('storage');
        // $target = storage_path('app/public');

        // if (file_exists($link) && !is_link($link)) {
        //     File::deleteDirectory($link);
        // }

        // if (!file_exists($link)) {
        //     Artisan::call('storage:link');
        // }
    }
}
