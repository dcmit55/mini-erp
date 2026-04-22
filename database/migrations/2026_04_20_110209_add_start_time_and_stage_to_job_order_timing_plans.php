<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('employee_id');
            $table->string('stage')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'stage']);
        });
    }
};
