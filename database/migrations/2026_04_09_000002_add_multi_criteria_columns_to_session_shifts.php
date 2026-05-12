<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_shifts', function (Blueprint $table) {
            // Hari berlaku: null = semua hari, [1,2,3,4,5] = Sen-Jum, [6] = Sabtu (ISO: 1=Mon ... 7=Sun)
            $table->json('applicable_days')->nullable()->after('is_active');

            // Filter posisi: null = semua posisi, ["operator","sewing"] = cek substring (case-insensitive)
            $table->json('position_keywords')->nullable()->after('applicable_days');

            // Shift khusus per-karyawan (misal Emilia Finance)
            $table->foreignId('employee_id')->nullable()->after('position_keywords')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_shifts', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['applicable_days', 'position_keywords', 'employee_id']);
        });
    }
};
