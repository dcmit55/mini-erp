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
        DB::statement("ALTER TABLE qc_projects MODIFY COLUMN mascot_type VARCHAR(60) NOT NULL DEFAULT 'Compress Foam'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE qc_projects MODIFY COLUMN mascot_type ENUM('Compress Foam','Inflatable') NOT NULL DEFAULT 'Compress Foam'");
    }
};
