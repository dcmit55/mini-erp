<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert all terminated records to inactive
        DB::table('employees')->where('status', 'terminated')->update(['status' => 'inactive']);

        // Remove terminated from enum
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','pending_contract') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','terminated','pending_contract') NOT NULL DEFAULT 'active'");
    }
};
