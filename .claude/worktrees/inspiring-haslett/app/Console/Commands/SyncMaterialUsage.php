<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Helpers\MaterialUsageHelper;

class SyncMaterialUsage extends Command
{
    // php artisan material-usage:sync-all
    protected $signature = 'material-usage:sync-all';
    protected $description = 'Sync all material usage data based on current Goods Out and Goods In';

    public function handle()
    {
        $inventories = Inventory::all();
        $projects = Project::all();

        foreach ($inventories as $inventory) {
            foreach ($projects as $project) {
                MaterialUsageHelper::sync($inventory->id, $project->id);
            }
        }
        $this->info('Material usage sync completed.');
    }
}
