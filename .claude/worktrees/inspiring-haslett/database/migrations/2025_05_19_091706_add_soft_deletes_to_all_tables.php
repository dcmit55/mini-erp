<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('goods_in', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('goods_out', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('material_usages', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('material_requests', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('currencies', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('goods_in', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('goods_out', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
