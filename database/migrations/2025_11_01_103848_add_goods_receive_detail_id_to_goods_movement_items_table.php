<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
    {
        Schema::table('goods_movement_items', function (Blueprint $table) {
            $table->foreignId('goods_receive_detail_id')->nullable()
                ->after('inventory_id')
                ->constrained('goods_receive_details')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('goods_movement_items', function (Blueprint $table) {
            $table->dropForeign(['goods_receive_detail_id']);
            $table->dropColumn('goods_receive_detail_id');
        });
    }
};
