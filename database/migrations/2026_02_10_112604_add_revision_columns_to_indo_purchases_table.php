<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_revision_columns_to_indo_purchases_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisionColumnsToIndoPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->timestamp('revision_at')->nullable()->after('received_by');
            $table->boolean('is_current')->default(true)->after('revision_at');
            
            // Index untuk performa query
            $table->index(['is_current']);
            $table->index(['revision_at']);
        });
    }

    public function down()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropColumn(['revision_at', 'is_current']);
        });
    }
}