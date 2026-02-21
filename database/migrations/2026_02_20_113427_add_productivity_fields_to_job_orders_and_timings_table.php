<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Add Standard Minutes Structure to job_orders ONLY
     * - job_orders.total_standard_minutes (for progress-based calculation)
     * - job_orders.standard_time_per_unit (for qty-based calculation)
     *
     * Standard minutes are determined from job_order, NOT from individual timings
     */
    public function up(): void
    {
        // Add to job_orders table
        Schema::table('job_orders', function (Blueprint $table) {
            // For progress-based measurement (percentage)
            // Example: If job takes 2400 minutes total and employee completes 50%,
            // standard_minutes = (50/100) * 2400 = 1200
            $table->unsignedInteger('total_standard_minutes')->nullable()->after('end_date')->comment('Total standard minutes for the entire job order (for progress-based calculation)');

            // For quantity-based measurement (qty, pcs, etc)
            // Example: If standard time is 30 min/unit and output is 10 units,
            // standard_minutes = 10 * 30 = 300
            $table->decimal('standard_time_per_unit', 8, 2)->nullable()->after('total_standard_minutes')->comment('Standard time per unit in minutes (for qty-based calculation)');

            // Index for performance
            $table->index('total_standard_minutes', 'idx_total_standard_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex('idx_total_standard_minutes');
            $table->dropColumn(['total_standard_minutes', 'standard_time_per_unit']);
        });
    }
};
