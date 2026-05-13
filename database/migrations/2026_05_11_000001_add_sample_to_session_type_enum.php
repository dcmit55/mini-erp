<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE timings MODIFY COLUMN session_type ENUM('mass_production', 'repair', 'sample') NOT NULL DEFAULT 'mass_production'");
        DB::statement("ALTER TABLE job_order_timing_plans MODIFY COLUMN session_type ENUM('mass_production', 'repair', 'sample') NOT NULL DEFAULT 'mass_production'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE timings MODIFY COLUMN session_type ENUM('mass_production', 'repair') NOT NULL DEFAULT 'mass_production'");
        DB::statement("ALTER TABLE job_order_timing_plans MODIFY COLUMN session_type ENUM('mass_production', 'repair') NOT NULL DEFAULT 'mass_production'");
    }
};
