<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // PENTING: Backup data dulu jika ada data existing
        DB::statement("
            UPDATE leave_requests
            SET type = CASE
                WHEN UPPER(TRIM(type)) LIKE '%ANNUAL%' THEN 'ANNUAL'
                WHEN UPPER(TRIM(type)) LIKE '%MATERNITY%' THEN 'MATERNITY'
                WHEN UPPER(TRIM(type)) LIKE '%WEDDING%' OR UPPER(TRIM(type)) LIKE '%MARRIAGE%' THEN 'WEDDING'
                WHEN UPPER(TRIM(type)) LIKE '%SON%' OR UPPER(TRIM(type)) LIKE '%DAUGHTER%' THEN 'SONWED'
                WHEN UPPER(TRIM(type)) LIKE '%BIRTH%' OR UPPER(TRIM(type)) LIKE '%MISCARRIAGE%' THEN 'BIRTHCHILD'
                WHEN UPPER(TRIM(type)) LIKE '%UNPAID%' THEN 'UNPAID'
                WHEN UPPER(TRIM(type)) LIKE '%BAPTISM%' OR UPPER(TRIM(type)) LIKE '%CIRCUMCISION%' THEN 'BAPTISM'
                WHEN UPPER(TRIM(type)) LIKE '%DEATH%' AND (UPPER(TRIM(type)) LIKE '%SPOUSE%' OR UPPER(TRIM(type)) LIKE '%CHILD%' OR UPPER(TRIM(type)) LIKE '%PARENT%') THEN 'DEATH_2'
                WHEN UPPER(TRIM(type)) LIKE '%DEATH%' THEN 'DEATH'
                ELSE 'ANNUAL'
            END
            WHERE type IS NOT NULL
        ");

        // Ubah kolom menjadi ENUM
        DB::statement("
            ALTER TABLE leave_requests
            MODIFY COLUMN `type` ENUM(
                'ANNUAL',
                'MATERNITY',
                'WEDDING',
                'SONWED',
                'BIRTHCHILD',
                'UNPAID',
                'DEATH',
                'DEATH_2',
                'BAPTISM'
            ) NOT NULL DEFAULT 'ANNUAL'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Kembalikan ke VARCHAR jika rollback
        DB::statement("
            ALTER TABLE leave_requests
            MODIFY COLUMN `type` VARCHAR(255) NOT NULL DEFAULT 'Annual Leave'
        ");
    }
};
