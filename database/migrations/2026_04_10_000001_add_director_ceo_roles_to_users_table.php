<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Tambahkan 'director' dan 'ceo' ke ENUM users.role
     * untuk mendukung approval flow Warning Letter SP2–SP4.
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
            'director',
            'ceo',
            'general'
        ) NOT NULL");
    }

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
            'timing',
            'general'
        ) NOT NULL");
    }
};
