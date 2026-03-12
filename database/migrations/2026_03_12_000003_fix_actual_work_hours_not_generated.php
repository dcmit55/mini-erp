<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti kolom actual_work_hours dari GENERATED ALWAYS AS (STORED)
 * menjadi kolom DECIMAL biasa yang dihitung oleh aplikasi.
 *
 * Alasan: TIMESTAMPDIFF() / ROUND() / CASE WHEN tidak didukung
 * di dalam GENERATED ALWAYS AS pada sebagian versi MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('daily_attendances', 'actual_work_hours')) {
            DB::statement("
                ALTER TABLE daily_attendances
                ADD COLUMN actual_work_hours DECIMAL(5,2) NULL
                AFTER total_break_mins
            ");
            return;
        }

        // Cek apakah kolom saat ini masih GENERATED — jika iya, konversi
        $col = DB::selectOne("
            SELECT EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'daily_attendances'
              AND COLUMN_NAME  = 'actual_work_hours'
        ");

        if ($col && str_contains(strtolower($col->EXTRA ?? ''), 'generated')) {
            // Hapus generated column, tambah kembali sebagai kolom biasa
            DB::statement("ALTER TABLE daily_attendances DROP COLUMN actual_work_hours");
            DB::statement("
                ALTER TABLE daily_attendances
                ADD COLUMN actual_work_hours DECIMAL(5,2) NULL
                AFTER total_break_mins
            ");

            // Backfill dari data yang sudah ada
            DB::statement("
                UPDATE daily_attendances
                SET actual_work_hours = ROUND(
                    (TIMESTAMPDIFF(MINUTE, clock_in_datetime, clock_out_datetime) - total_break_mins) / 60,
                    2
                )
                WHERE clock_in_datetime IS NOT NULL
                  AND clock_out_datetime IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback — kolom biasa lebih kompatibel
    }
};
