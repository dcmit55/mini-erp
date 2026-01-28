<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pre_shippings', function (Blueprint $table) {
            // Add grouping identifier
            $table->string('group_key')->nullable()->after('purchase_request_id');

            // Add cost allocation method
            $table
                ->enum('cost_allocation_method', ['quantity', 'percentage', 'value'])
                ->default('quantity')
                ->after('domestic_cost');

            // Add percentage for manual allocation
            $table->decimal('allocation_percentage', 5, 2)->nullable()->after('cost_allocation_method');

            // Add calculated allocated cost
            $table->decimal('allocated_cost', 15, 2)->nullable()->after('allocation_percentage');

            // Add index for better performance
            $table->index(['group_key']);
        });
    }

    public function down()
    {
        Schema::table('pre_shippings', function (Blueprint $table) {
            $table->dropIndex(['group_key']);
            $table->dropColumn(['group_key', 'cost_allocation_method', 'allocation_percentage', 'allocated_cost']);
        });
    }
};
