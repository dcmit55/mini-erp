<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            // Tambah 2 kolom saja
            $table->dateTime('revision_at')->nullable()->after('purchase_id');
            $table->boolean('is_current')->default(true)->after('revision_at');
            
            // Index untuk performa query
            $table->index(['po_number', 'is_current']);
            
            // Kolom revision_number tidak perlu, kita pakai created_at
        });
    }

    public function down()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            $table->dropIndex(['po_number', 'is_current']);
            $table->dropColumn(['revision_at', 'is_current']);
        });
    }
};