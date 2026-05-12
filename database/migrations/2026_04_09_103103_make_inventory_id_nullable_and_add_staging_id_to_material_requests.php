<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Make inventory_id nullable so Incoming source MRs don't need a batch inventory link
            $table->unsignedBigInteger('inventory_id')->nullable()->change();

            // Add staging_inventory_id to track which Lark staging item this MR is for
            $table->unsignedBigInteger('staging_inventory_id')->nullable()->after('inventory_id');
            $table->foreign('staging_inventory_id')->references('id')->on('lark_staging_inventories')->nullOnDelete();

            // Track the source type: stock or incoming
            $table->string('inventory_source', 20)->default('stock')->after('staging_inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropForeign(['staging_inventory_id']);
            $table->dropColumn(['staging_inventory_id', 'inventory_source']);
            $table->unsignedBigInteger('inventory_id')->nullable(false)->change();
        });
    }
};
