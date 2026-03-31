<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM','SICK','MENSTRUATION','HAJJ','PATERNITY',
            'EARLY_LEAVE','PERMISSION_OUT'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM','SICK','MENSTRUATION','HAJJ','PATERNITY',
            'EARLY_LEAVE'
        ) NOT NULL");
    }
};
