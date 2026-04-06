<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Snapshot of employee hourly rate (salary / 173) at the time the
            // timing was approved. This prevents rate drift when employee salary
            // is later updated — the historical cost stays locked.
            $table->decimal('rate_per_hour', 12, 2)->nullable()->default(null)->after('break_deducted_minutes');
        });

        // Backfill existing approved timings: rate_per_hour = employees.salary / 173
        // Using raw SQL for efficiency — avoids loading all rows into PHP memory.
        DB::statement("
            UPDATE timings t
            JOIN employees e ON e.id = t.employee_id
            SET t.rate_per_hour = ROUND(e.salary / 173, 2)
            WHERE t.status IN ('complete', 'frozen')
              AND t.approval_status = 'approved'
              AND e.salary > 0
              AND t.rate_per_hour IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropColumn('rate_per_hour');
        });
    }
};
