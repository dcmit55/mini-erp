<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'paused' to status ENUM
        DB::statement("ALTER TABLE timings MODIFY COLUMN status ENUM('complete','on progress','pending','paused') NOT NULL");
    }

    public function down(): void
    {
        // Revert 'paused' sessions to 'complete' before removing the enum value
        DB::statement("UPDATE timings SET status = 'complete' WHERE status = 'paused'");
        DB::statement("ALTER TABLE timings MODIFY COLUMN status ENUM('complete','on progress','pending') NOT NULL");
    }
};
