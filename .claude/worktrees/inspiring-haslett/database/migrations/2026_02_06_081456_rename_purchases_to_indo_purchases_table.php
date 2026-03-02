<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RenamePurchasesToIndoPurchasesTable extends Migration
{
    public function up()
    {
        Schema::rename('purchases', 'indo_purchases');
    }

    public function down()
    {
        Schema::rename('indo_purchases', 'purchases');
    }
}