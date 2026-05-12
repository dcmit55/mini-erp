<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi device_registered_at untuk employee yang sudah pernah scan
     * tapi kolom tersebut masih NULL.
     * Gunakan waktu scan pertama sebagai tanggal registrasi device.
     */
    public function up(): void
    {
        // Normalisasi: hapus prefix 'DCM-' lalu strip leading zeros
        // MySQL: TRIM(LEADING '0' FROM col) untuk strip leading zeros
        DB::statement("
            UPDATE employees e
            JOIN (
                SELECT
                    cloud_id,
                    MIN(event_time) AS first_scan
                FROM fingerprint_logs
                GROUP BY cloud_id
            ) fl
                ON TRIM(LEADING '0' FROM REPLACE(e.employee_no, 'DCM-', ''))
                 = TRIM(LEADING '0' FROM fl.cloud_id)
            SET e.device_registered_at = fl.first_scan
            WHERE e.device_registered_at IS NULL
              AND e.deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        // Tidak di-rollback — data registrasi tidak boleh hilang
    }
};
