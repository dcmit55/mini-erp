<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change from VARCHAR(255) to LONGTEXT to store base64-encoded file content
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `mc_document` LONGTEXT NULL");
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `doctor_letter` LONGTEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `mc_document` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `doctor_letter` VARCHAR(255) NULL");
    }
};
