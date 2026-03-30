<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add new leave types
        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM','SICK','MENSTRUATION','HAJJ','PATERNITY'
        ) NOT NULL");

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('mc_document', 255)->nullable()->after('reason');
            $table->string('doctor_letter', 255)->nullable()->after('mc_document');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['mc_document', 'doctor_letter']);
        });

        DB::statement("ALTER TABLE `leave_requests` MODIFY COLUMN `type` ENUM(
            'ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID',
            'DEATH','DEATH_2','BAPTISM'
        ) NOT NULL");
    }
};
