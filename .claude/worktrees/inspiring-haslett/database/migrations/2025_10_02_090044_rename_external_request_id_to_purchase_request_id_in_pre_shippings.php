<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pre_shippings', function (Blueprint $table) {
            $table->renameColumn('external_request_id', 'purchase_request_id');
        });
    }
    public function down()
    {
        Schema::table('pre_shippings', function (Blueprint $table) {
            $table->renameColumn('purchase_request_id', 'external_request_id');
        });
    }
};
