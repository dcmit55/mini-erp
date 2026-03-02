<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::rename('external_requests', 'purchase_requests');
    }
    public function down()
    {
        Schema::rename('purchase_requests', 'external_requests');
    }
};
