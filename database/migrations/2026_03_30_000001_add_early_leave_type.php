<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah EARLY_LEAVE ke enum leave_requests.type
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM','SICK','MENSTRUATION','HAJJ','PATERNITY',
            'EARLY_LEAVE'
        ) NOT NULL");

        // Tambah 'Early Leave' ke enum daily_attendances.status
        DB::statement("ALTER TABLE `daily_attendances` MODIFY COLUMN `status` ENUM(
            'Present','Late','Excused','Sick Leave','Annual Leave','Alpha','Early Leave'
        ) NOT NULL DEFAULT 'Alpha'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `daily_attendances` MODIFY COLUMN `status` ENUM(
            'Present','Late','Excused','Sick Leave','Annual Leave','Alpha'
        ) NOT NULL DEFAULT 'Alpha'");

        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM','SICK','MENSTRUATION','HAJJ','PATERNITY'
        ) NOT NULL");
    }
};
