<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employment_type` ENUM('PKWT','PKWTT','Daily Worker','Probation','Internship') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        // Hapus baris dengan 'Internship' sebelum rollback agar tidak truncated
        DB::statement("UPDATE `employees` SET `employment_type` = NULL WHERE `employment_type` = 'Internship'");
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employment_type` ENUM('PKWT','PKWTT','Daily Worker','Probation') NULL DEFAULT NULL");
    }
};
