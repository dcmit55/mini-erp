<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE qc_projects MODIFY COLUMN mascot_type ENUM('Compress Foam','Inflatable') NOT NULL DEFAULT 'Compress Foam'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE qc_projects MODIFY COLUMN mascot_type ENUM('Mascot','Inflatable') NOT NULL DEFAULT 'Mascot'");
    }
};
