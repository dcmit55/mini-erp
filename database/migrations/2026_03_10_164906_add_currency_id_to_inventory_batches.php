<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add currency_id column
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('unit_price');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });

        // 2. Backfill currency_id from inventories
        DB::statement('
            UPDATE inventory_batches ib
            JOIN inventories i ON i.id = ib.inventory_id
            SET ib.currency_id = i.currency_id
            WHERE i.currency_id IS NOT NULL
        ');

        // 3. Rename BATCH-YYYYMMDD-{inventoryId}-{seq} → simple BATCH-{id padded}
        DB::statement("
            UPDATE inventory_batches
            SET batch_number = CONCAT('BATCH-', LPAD(id, 4, '0'))
            WHERE batch_number LIKE 'BATCH-%'
        ");
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
