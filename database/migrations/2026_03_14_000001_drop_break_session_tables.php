<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus tabel shift dan break session.
 *
 * Tabel yang di-drop:
 *   - shift_anomalies
 *   - break_events
 *   - next_day_schedules
 *   - shifts
 *
 * Kolom yang di-drop dari daily_attendances:
 *   - shift_id (FK ke shifts yang sudah dihapus)
 *
 * Tidak menyentuh kolom lain di daily_attendances maupun fingerprint_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop tabel dependen terlebih dahulu
        Schema::dropIfExists('shift_anomalies');
        Schema::dropIfExists('break_events');
        Schema::dropIfExists('next_day_schedules');

        // 2. Lepas FK shift_id di daily_attendances sebelum drop tabel shifts
        if (Schema::hasColumn('daily_attendances', 'shift_id')) {
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'daily_attendances'
                  AND CONSTRAINT_NAME = 'daily_attendances_shift_id_foreign'
            ");
            Schema::table('daily_attendances', function (Blueprint $table) use ($fkExists) {
                if ($fkExists) {
                    $table->dropForeign(['shift_id']);
                }
                $table->dropColumn('shift_id');
            });
        }

        // 3. Drop tabel shifts
        Schema::dropIfExists('shifts');
    }

    public function down(): void
    {
        // Tabel-tabel ini tidak di-restore saat rollback.
        // Untuk restore, jalankan ulang migration original 000001–000007.
    }
};
