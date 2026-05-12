<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add pending_contract to status enum
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','terminated','pending_contract') NOT NULL DEFAULT 'active'");

        // Convert auto-expired inactive employees to pending_contract
        DB::table('employees')
            ->whereNull('deleted_at')
            ->where('status', 'inactive')
            ->where('notes', 'like', '%[Auto-updated]%')
            ->update(['status' => 'pending_contract']);
    }

    public function down(): void
    {
        // Revert pending_contract back to inactive before shrinking the enum
        DB::table('employees')
            ->where('status', 'pending_contract')
            ->update(['status' => 'inactive']);

        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','terminated') NOT NULL DEFAULT 'active'");
    }
};
