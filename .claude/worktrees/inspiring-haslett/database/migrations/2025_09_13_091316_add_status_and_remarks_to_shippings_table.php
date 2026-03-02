<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->string('shipment_status')->default('On Process');
            $table->string('remarks')->nullable();
        });
    }
    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropColumn(['shipment_status', 'remarks']);
        });
    }
};
