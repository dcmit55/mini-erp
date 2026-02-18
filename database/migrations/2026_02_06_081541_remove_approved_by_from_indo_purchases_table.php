<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveApprovedByFromIndoPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {

            // Drop FK pakai nama lama (sebelum rename table)
            $table->dropForeign('purchases_approved_by_foreign');

            // Baru drop column
            $table->dropColumn('approved_by');
        });
    }

    public function down()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {

            $table->unsignedBigInteger('approved_by')
                  ->nullable()
                  ->after('approved_at');

            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }
}
