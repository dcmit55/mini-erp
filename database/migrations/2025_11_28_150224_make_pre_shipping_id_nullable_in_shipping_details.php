<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('shipping_details', function (Blueprint $table) {
            // Make pre_shipping_id nullable
            $table->unsignedBigInteger('pre_shipping_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('shipping_details', function (Blueprint $table) {
            $table->unsignedBigInteger('pre_shipping_id')->nullable(false)->change();
        });
    }
};
