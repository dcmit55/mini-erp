<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('goods_movement_items', function (Blueprint $table) {
            $table->boolean('transferred_to_inventory')->default(false)->after('notes');
            $table->timestamp('transferred_at')->nullable()->after('transferred_to_inventory');
            $table->unsignedBigInteger('transferred_by')->nullable()->after('transferred_at');
            
            $table->foreign('transferred_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('goods_movement_items', function (Blueprint $table) {
            $table->dropForeign(['transferred_by']);
            $table->dropColumn(['transferred_to_inventory', 'transferred_at', 'transferred_by']);
        });
    }
};