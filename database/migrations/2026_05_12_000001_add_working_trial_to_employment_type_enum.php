<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employment_type` ENUM('PKWT','PKWTT','Daily Worker','Probation','Internship','Working Trial') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE `employees` SET `employment_type` = NULL WHERE `employment_type` = 'Working Trial'");
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employment_type` ENUM('PKWT','PKWTT','Daily Worker','Probation','Internship') NULL DEFAULT NULL");
    }
};
