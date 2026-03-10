<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add 'source' column to inventories table.
     * Used to distinguish lark-synced items from manually created items.
     * Values: 'lark' | null (manual)
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('source')->nullable()->after('last_sync_at')->comment('Data source: lark = from Lark sync, null = manual entry');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
