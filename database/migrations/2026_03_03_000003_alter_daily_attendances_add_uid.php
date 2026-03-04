<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Menambahkan kolom uid ke tabel daily_attendances yang sudah ada.
     * Proses:
     *   1. Tambah uid sebagai nullable dulu (agar data lama tidak error)
     *   2. Backfill UUID untuk semua baris existing (chunk 500 agar aman di production)
     *   3. Ubah uid menjadi NOT NULL setelah semua baris terisi
     *
     * Catatan: unique(['employee_id', 'date']) sudah ada dari migration awal, tidak diulang.
     */
    public function up(): void
    {
        // Step 1: Tambah kolom nullable dulu
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        // Step 2: Backfill UUID untuk data lama
        DB::table('daily_attendances')
            ->whereNull('uid')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('daily_attendances')
                        ->where('id', $row->id)
                        ->update(['uid' => (string) Str::uuid()]);
                }
            });

        // Step 3: Jadikan NOT NULL setelah backfill selesai
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->dropColumn('uid');
        });
    }
};
