<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add waybill column to inventory_batches.
 * When a Lark Staging Inventory is approved, the international_waybill
 * is copied here so batch-level traceability is preserved.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->string('waybill', 255)->nullable()->after('notes')->comment('International waybill number, copied from lark_staging_inventories on approve');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropColumn('waybill');
        });
    }
};
