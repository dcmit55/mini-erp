<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            // Parts column: stores the part name (varchar, not FK) for flexibility
            // Values come from timing_parts master table but stored as plain text
            // so historical records remain readable even if master data changes
            $table->string('parts', 100)->nullable()->after('task');
        });
    }

    public function down(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->dropColumn('parts');
        });
    }
};
