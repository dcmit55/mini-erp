<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * PART 1 - DATABASE MIGRATION
     * - Add duration_minutes (integer) for standardized time storage
     * - Backfill from existing duration_hours
     * - Add performance indexes
     * - Keep duration_hours temporarily for safety
     */
    public function up(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // 1. Add duration_minutes column after end_time
            $table->unsignedInteger('duration_minutes')->default(0)->after('end_time')->comment('Duration in minutes - standardized time storage');

            // 2. Add performance indexes for productivity queries
            $table->index(['employee_id', 'job_order_id'], 'idx_employee_job_order');
            $table->index('measurement_type', 'idx_measurement_type');
        });

        // 3. Backfill data: Convert duration_hours to duration_minutes
        // Formula: duration_minutes = ROUND(duration_hours * 60)
        DB::statement('
            UPDATE timings
            SET duration_minutes = ROUND(COALESCE(duration_hours, 0) * 60)
            WHERE duration_hours IS NOT NULL
        ');

        // Log migration result
        $backfilledCount = DB::table('timings')->whereNotNull('duration_hours')->where('duration_hours', '>', 0)->count();

        \Log::info("Duration minutes migration completed. Backfilled {$backfilledCount} records.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_employee_job_order');
            $table->dropIndex('idx_measurement_type');

            // Drop duration_minutes column
            $table->dropColumn('duration_minutes');
        });
    }
};
