<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('lark_record_id')->nullable()->after('created_by')->comment('Record ID dari Lark untuk tracking');
            $table->timestamp('last_sync_at')->nullable()->after('lark_record_id')->comment('Timestamp sync terakhir dari Lark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'last_sync_at']);
        });
    }
};
