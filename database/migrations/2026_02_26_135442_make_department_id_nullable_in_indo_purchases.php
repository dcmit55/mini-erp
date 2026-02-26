<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });
    }
};