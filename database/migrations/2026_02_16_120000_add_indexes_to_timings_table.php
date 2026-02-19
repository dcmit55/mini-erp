<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Add strategic indexes to timings table for optimal query performance
     * even with millions of records
     */
    public function up(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Composite index untuk query paling sering dipakai
            $table->index(['project_id', 'status', 'tanggal'], 'idx_project_status_date');

            // Index untuk job order tracking
            $table->index(['job_order_id', 'status'], 'idx_joborder_status');

            // Index untuk employee performance queries
            $table->index(['employee_id', 'tanggal'], 'idx_employee_date');

            // Index untuk daily reports
            $table->index(['tanggal', 'status'], 'idx_date_status');

            // Index untuk status queries (running sessions, etc)
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropIndex('idx_project_status_date');
            $table->dropIndex('idx_joborder_status');
            $table->dropIndex('idx_employee_date');
            $table->dropIndex('idx_date_status');
            $table->dropIndex('timings_status_index');
        });
    }
};
