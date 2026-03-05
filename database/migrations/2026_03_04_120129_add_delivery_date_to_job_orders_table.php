<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Add delivery_date column to job_orders table
     * Replace countdown_days with delivery_date for deadline tracking
     *
     * BUSINESS LOGIC:
     * - Sync from Lark "Delivery Date" field (format: "2025-12-31")
     * - Scheduler will check daily for jobs with delivery_date = today + 2 days
     * - Trigger Pusher notification to department admins H-2 before delivery
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Remove countdown_days (not used anymore)
            if (Schema::hasColumn('job_orders', 'countdown_days')) {
                $table->dropIndex('idx_countdown_days');
                $table->dropColumn('countdown_days');
            }

            // Add delivery_date column
            $table->date('delivery_date')->nullable()->after('end_date')->comment('Delivery deadline date from Lark (YYYY-MM-DD format)');

            // Add index for efficient daily scheduler queries
            $table->index('delivery_date', 'idx_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex('idx_delivery_date');
            $table->dropColumn('delivery_date');

            // Restore countdown_days
            $table->unsignedInteger('countdown_days')->nullable()->after('last_sync_at');
            $table->index('countdown_days', 'idx_countdown_days');
        });
    }
};
