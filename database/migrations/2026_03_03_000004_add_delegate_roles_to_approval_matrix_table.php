<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom delegate_roles (JSON) ke approval_matrix.
     * Kolom ini menyimpan daftar role tambahan yang dapat menggantikan
     * role utama pada level tertentu (misal: admin_hr bisa menggantikan director).
     *
     * Contoh nilai: ["admin_hr", "admin"]
     * Null berarti tidak ada delegate.
     */
    public function up(): void
    {
        Schema::table('approval_matrix', function (Blueprint $table) {
            $table->json('delegate_roles')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('approval_matrix', function (Blueprint $table) {
            $table->dropColumn('delegate_roles');
        });
    }
};
