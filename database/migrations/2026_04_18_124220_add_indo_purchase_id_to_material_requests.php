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
            // Reference to project_purchases when source is indo_purchase
            $table->unsignedBigInteger('indo_purchase_id')->nullable()->after('staging_inventory_id')->comment('FK to project_purchases when inventory_source = indo_purchase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropColumn('indo_purchase_id');
        });
    }
};
