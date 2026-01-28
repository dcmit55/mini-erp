<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('goods_in', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->after('goods_out_id')->constrained('inventories');
            $table->foreignId('project_id')->nullable()->after('inventory_id')->constrained('projects');
        });
    }
    public function down()
    {
        Schema::table('goods_in', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['inventory_id', 'project_id']);
        });
    }
};
