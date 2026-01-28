<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    function up()
    {
        Schema::table('goods_in', function (Blueprint $table) {
            $table->text('remark')->nullable();
        });
        Schema::table('goods_out', function (Blueprint $table) {
            $table->text('remark')->nullable();
        });
    }

    public function down()
    {
        Schema::table('goods_in', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
        Schema::table('goods_out', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};