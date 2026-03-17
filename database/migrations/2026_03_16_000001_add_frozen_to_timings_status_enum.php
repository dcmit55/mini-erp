<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'frozen' to status ENUM (timer paused, stays in monitor, NOT sent to approval)
        DB::statement("ALTER TABLE timings MODIFY COLUMN status ENUM('complete','on progress','pending','paused','frozen') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE timings SET status = 'on progress' WHERE status = 'frozen'");
        DB::statement("ALTER TABLE timings MODIFY COLUMN status ENUM('complete','on progress','pending','paused') NOT NULL");
    }
};
