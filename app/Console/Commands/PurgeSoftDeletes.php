<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\GoodsIn;
use App\Models\Logistic\GoodsOut;
use App\Models\Production\Project;
use App\Models\Admin\User;
use App\Models\Logistic\MaterialUsage;
use App\Models\Logistic\MaterialRequest;
use App\Models\Finance\Currency;

class PurgeSoftDeletes extends Command
{
    // php artisan purge:softdeletes
    protected $signature = 'purge:softdeletes';
    protected $description = 'Permanently delete soft deleted records older than 30 days';

    public function handle()
    {
        $models = [
            Inventory::class,
            GoodsIn::class,
            GoodsOut::class,
            Project::class,
            User::class,
            MaterialUsage::class,
            MaterialRequest::class,
            Currency::class,
        ];

        $date = Carbon::now()->subDays(30);

        foreach ($models as $model) {
            $count = $model::onlyTrashed()->where('deleted_at', '<', $date)->forceDelete();
            $this->info("Purged $count records from {$model}");
        }

        $this->info('Soft deleted records older than 30 days have been purged.');
    }
}
