<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->string('parts')->nullable()->change();
        });
    }
    public function down()
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->string('parts')->nullable(false)->change();
        });
    }
};