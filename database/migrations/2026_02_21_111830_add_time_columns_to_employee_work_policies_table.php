<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_work_policies', function (Blueprint $table) {
            // Weekday
            $table->time('weekday_start')->nullable()->after('weekday_hours');
            $table->time('weekday_end')->nullable()->after('weekday_start');
            // Saturday
            $table->time('saturday_start')->nullable()->after('saturday_hours');
            $table->time('saturday_end')->nullable()->after('saturday_start');
            // Sunday
            $table->decimal('sunday_hours', 5, 2)->nullable()->after('saturday_end');
            $table->time('sunday_start')->nullable()->after('sunday_hours');
            $table->time('sunday_end')->nullable()->after('sunday_start');
        });
    }

    public function down()
    {
        Schema::table('employee_work_policies', function (Blueprint $table) {
            $table->dropColumn([
                'weekday_start', 'weekday_end',
                'saturday_start', 'saturday_end',
                'sunday_hours', 'sunday_start', 'sunday_end'
            ]);
        });
    }
};