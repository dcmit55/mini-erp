<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambahkan 'admin_hr' ke enum role
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum tanpa 'admin_hr'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin',
            'admin_logistic',
            'admin_mascot',
            'admin_costume',
            'admin_finance',
            'admin_animatronic',
            'admin_procurement',
            'admin',
            'general'
        ) NOT NULL");
    }
};
