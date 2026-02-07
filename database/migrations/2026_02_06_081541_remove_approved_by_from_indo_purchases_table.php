<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveApprovedByFromIndoPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropColumn('approved_by');
        });
    }

    public function down()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->bigInteger('approved_by')->unsigned()->nullable()->after('approved_at');
        });
    }
}