<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `daily_attendances` MODIFY COLUMN `status` ENUM(
            'Present','Late','Excused','Sick Leave','Annual Leave','Alpha','Early Leave','Permission Out'
        ) NOT NULL DEFAULT 'Alpha'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `daily_attendances` MODIFY COLUMN `status` ENUM(
            'Present','Late','Excused','Sick Leave','Annual Leave','Alpha','Early Leave'
        ) NOT NULL DEFAULT 'Alpha'");
    }
};
