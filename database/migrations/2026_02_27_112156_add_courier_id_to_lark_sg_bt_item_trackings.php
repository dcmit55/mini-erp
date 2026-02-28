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
        Schema::table('lark_sg_bt_item_trackings', function (Blueprint $table) {
            $table->unsignedBigInteger('courier_id')->nullable()->after('project_id');
            $table->foreign('courier_id')->references('id')->on('lark_sg_bt_courier_ids')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_sg_bt_item_trackings', function (Blueprint $table) {
            $table->dropForeign(['courier_id']);
            $table->dropColumn('courier_id');
        });
    }
};
