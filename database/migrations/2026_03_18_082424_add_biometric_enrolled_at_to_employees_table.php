<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Tanggal karyawan pertama kali berhasil scan biometric (sidik jari / wajah).
            // Di-set saat sync menemukan scan BARU (bukan duplikat) dari karyawan tsb.
            // Tidak ikut terhapus jika fingerprint_logs dikosongkan.
            $table->timestamp('biometric_enrolled_at')->nullable()->after('device_registered_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('biometric_enrolled_at');
        });
    }
};
