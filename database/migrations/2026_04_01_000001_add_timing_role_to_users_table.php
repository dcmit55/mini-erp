<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Add 'timing' role to users.role ENUM.
     * Role 'timing' = Admin Timing — akses terbatas ke module Production, Timing, dan HR.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin',
            'admin_logistic',
            'admin_mascot',
            'admin_costume',
            'admin_finance',
            'admin_animatronic',
            'admin_procurement',
            'admin_hr',
            'admin',
            'timing',
            'general'
        ) NOT NULL");
    }

    /**
     * Remove 'timing' from ENUM (rollback).
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin',
            'admin_logistic',
            'admin_mascot',
            'admin_costume',
            'admin_finance',
            'admin_animatronic',
            'admin_procurement',
            'admin_hr',
            'admin',
            'general'
        ) NOT NULL");
    }
};
