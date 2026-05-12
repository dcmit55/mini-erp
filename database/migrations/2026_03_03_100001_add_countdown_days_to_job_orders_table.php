<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Add countdown_days column to job_orders table
     * This will store parsed integer from Lark countdown field (e.g., "2 days left" → 2)
     *
     * BUSINESS LOGIC:
     * - Database stores INTEGER only for efficient querying/sorting
     * - UI displays as: $countdown_days . ' days left'
     * - Future: Trigger Pusher notification when countdown_days = 1
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Add countdown_days column after last_sync_at
            $table->unsignedInteger('countdown_days')->nullable()->after('last_sync_at')->comment('Number of days remaining until deadline (parsed from Lark countdown field)');

            // Add index for efficient filtering (e.g., WHERE countdown_days = 1)
            $table->index('countdown_days', 'idx_countdown_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex('idx_countdown_days');
            $table->dropColumn('countdown_days');
        });
    }
};
