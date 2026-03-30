<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom approval_dept ke leave_requests
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('approval_dept', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('doctor_letter');
        });

        // 2. Shift level leave di approval_matrix: 1→2, 2→3
        //    Lakukan dari level terbesar dulu agar tidak conflict unique constraint
        DB::table('approval_matrix')
            ->where('module', 'leave')
            ->where('level', 2)
            ->update(['level' => 3]);

        DB::table('approval_matrix')
            ->where('module', 'leave')
            ->where('level', 1)
            ->update(['level' => 2]);

        // 3. Tambah level 1 baru: dept admins
        //    role utama: admin_mascot, delegate: admin_logistic, admin_costume
        DB::table('approval_matrix')->insert([
            'uid'            => \Illuminate\Support\Str::uuid(),
            'module'         => 'leave',
            'level'          => 1,
            'role'           => 'admin_mascot',
            'delegate_roles' => json_encode(['admin_logistic', 'admin_costume']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        // Hapus level 1 yang baru ditambah
        DB::table('approval_matrix')
            ->where('module', 'leave')
            ->where('level', 1)
            ->delete();

        // Shift balik: 2→1, 3→2
        DB::table('approval_matrix')
            ->where('module', 'leave')
            ->where('level', 2)
            ->update(['level' => 1]);

        DB::table('approval_matrix')
            ->where('module', 'leave')
            ->where('level', 3)
            ->update(['level' => 2]);

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('approval_dept');
        });
    }
};
