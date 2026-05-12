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
        // Add lark_record_id and last_sync_at to all 4 staging tables

        Schema::table('lark_bt_sg_courier_ids', function (Blueprint $table) {
            $table->string('lark_record_id', 100)->nullable()->after('id')->index();
            $table->timestamp('last_sync_at')->nullable()->after('cost_per_item');
        });

        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            $table->string('lark_record_id', 100)->nullable()->after('id')->index();
            $table->timestamp('last_sync_at')->nullable()->after('sgd_cost');
        });

        Schema::table('lark_sg_bt_courier_ids', function (Blueprint $table) {
            $table->string('lark_record_id', 100)->nullable()->after('id')->index();
            $table->timestamp('last_sync_at')->nullable()->after('cost_per_item');
        });

        Schema::table('lark_sg_bt_item_trackings', function (Blueprint $table) {
            $table->string('lark_record_id', 100)->nullable()->after('id')->index();
            $table->timestamp('last_sync_at')->nullable()->after('sgd_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_bt_sg_courier_ids', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'last_sync_at']);
        });

        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'last_sync_at']);
        });

        Schema::table('lark_sg_bt_courier_ids', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'last_sync_at']);
        });

        Schema::table('lark_sg_bt_item_trackings', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'last_sync_at']);
        });
    }
};
