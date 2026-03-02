<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom role dari enum ke string dulu untuk fleksibilitas
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum lama (tanpa 'admin')
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin',
            'admin_logistic',
            'admin_mascot',
            'admin_costume',
            'admin_finance',
            'admin_animatronic',
            'admin_procurement',
            'general'
        ) NOT NULL");
    }
};