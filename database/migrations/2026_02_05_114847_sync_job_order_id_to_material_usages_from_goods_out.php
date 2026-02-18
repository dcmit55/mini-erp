<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Helpers\MaterialUsageHelper;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * This migration will:
     * 1. Re-sync ALL material_usages by calling MaterialUsageHelper for each unique combination
     * 2. This ensures job_order_id is properly populated from goods_out/goods_in data
     */
    public function up(): void
    {
        echo "Starting material usage re-sync with job_order_id...\n";

        // Get all unique combinations of inventory_id, project_id, job_order_id from goods_out
        $combinations = DB::table('goods_out')->select('inventory_id', 'project_id', 'job_order_id')->whereNull('deleted_at')->groupBy('inventory_id', 'project_id', 'job_order_id')->get();

        $count = 0;
        foreach ($combinations as $combo) {
            MaterialUsageHelper::sync($combo->inventory_id, $combo->project_id, $combo->job_order_id);
            $count++;
        }

        echo "Re-synced {$count} material usage records with job_order_id.\n";

        // Also sync combinations from goods_in that might not be in goods_out
        $combinationsIn = DB::table('goods_in')
            ->select('inventory_id', 'project_id', 'job_order_id')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('goods_out')->whereColumn('goods_in.inventory_id', 'goods_out.inventory_id')->whereColumn('goods_in.project_id', 'goods_out.project_id')->whereColumn('goods_in.job_order_id', 'goods_out.job_order_id');
            })
            ->groupBy('inventory_id', 'project_id', 'job_order_id')
            ->get();

        $countIn = 0;
        foreach ($combinationsIn as $combo) {
            MaterialUsageHelper::sync($combo->inventory_id, $combo->project_id, $combo->job_order_id);
            $countIn++;
        }

        echo "Re-synced {$countIn} additional material usage records from goods_in.\n";
        echo 'Total records processed: ' . ($count + $countIn) . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as it's a data sync operation
        echo "This migration cannot be reversed. Material usages retain their synced data.\n";
    }
};
