<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Minutes of break time automatically deducted from duration_minutes.
            // 0  = no break deducted (session outside break window, or no shift configured).
            // >0 = break was auto-applied (scheduler freeze/unfreeze or fallback on stop).
            $table->unsignedSmallInteger('break_deducted_minutes')
                  ->default(0)
                  ->after('total_paused_minutes')
                  ->comment('Minutes of break automatically excluded from duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropColumn('break_deducted_minutes');
        });
    }
};
