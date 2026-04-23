<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table
                ->enum('session_type', ['mass_production', 'repair'])
                ->default('mass_production')
                ->after('stage');
        });
    }

    public function down(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->dropColumn('session_type');
        });
    }
};
