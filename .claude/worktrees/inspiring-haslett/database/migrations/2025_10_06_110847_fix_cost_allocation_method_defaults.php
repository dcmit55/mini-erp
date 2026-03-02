<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Update existing records from 'quantity' to 'value' as default
        DB::table('pre_shippings')
            ->where('cost_allocation_method', 'quantity')
            ->whereNull('updated_at') // Only update records that haven't been manually changed
            ->update([
                'cost_allocation_method' => 'value',
                'updated_at' => now()
            ]);
    }

    public function down()
    {
        // Rollback to 'quantity' if needed
        DB::table('pre_shippings')
            ->where('cost_allocation_method', 'value')
            ->update(['cost_allocation_method' => 'quantity']);
    }
};
