<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->tinyInteger('menstruation_leave_approved')->default(0)->after('biometric_enrolled_at');
            $table->dateTime('menstruation_leave_approved_at')->nullable()->after('menstruation_leave_approved');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['menstruation_leave_approved', 'menstruation_leave_approved_at']);
        });
    }
};
