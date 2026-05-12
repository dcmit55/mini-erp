<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            // Nullable — existing records (planning_date = NULL) treated as legacy/unversioned plans
            $table->date('planning_date')->nullable()->after('job_order_id');
            // Index to speed up date-scoped queries
            $table->index(['job_order_id', 'planning_date'], 'idx_jo_planning_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->dropIndex('idx_jo_planning_date');
            $table->dropColumn('planning_date');
        });
    }
};
