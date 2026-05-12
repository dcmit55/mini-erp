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
        // BT-SG Courier
        Schema::table('lark_bt_sg_courier_ids', function (Blueprint $table) {
            $table->renameColumn('courier_id', 'name');
        });

        // SG-BT Courier
        Schema::table('lark_sg_bt_courier_ids', function (Blueprint $table) {
            $table->renameColumn('courier_id', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_bt_sg_courier_ids', function (Blueprint $table) {
            $table->renameColumn('name', 'courier_id');
        });

        Schema::table('lark_sg_bt_courier_ids', function (Blueprint $table) {
            $table->renameColumn('name', 'courier_id');
        });
    }
};
