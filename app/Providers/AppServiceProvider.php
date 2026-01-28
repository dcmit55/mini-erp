<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Admin\User;

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
