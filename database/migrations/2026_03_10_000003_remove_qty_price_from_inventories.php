<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Drop qty and price columns from inventories.
     * Stock is now sourced from inventory_batches.
     * unit_price (cost reference) is retained in inventory_batches.
     *
     * NOTE: Run AFTER 2026_03_10_000002_seed_initial_inventory_batches
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'price']);
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->default(0)->after('unit_id');
            $table->decimal('price', 15, 4)->nullable()->after('quantity');
        });
    }
};
