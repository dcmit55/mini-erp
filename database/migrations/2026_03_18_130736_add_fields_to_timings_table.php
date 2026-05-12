<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend status enum — add 'running' and 'stopped' ──────────────
        // MySQL requires the full enum list to be re-declared on MODIFY.
        DB::statement("
            ALTER TABLE timings
            MODIFY COLUMN status ENUM(
                'complete', 'on progress', 'pending', 'paused', 'frozen',
                'running', 'stopped'
            ) NOT NULL DEFAULT 'on progress'
        ");

        // ── 2. Add new columns ────────────────────────────────────────────────
        Schema::table('timings', function (Blueprint $table) {
            // FK to session shift (auto-detected from daily_attendances)
            $table->unsignedBigInteger('session_shift_id')->nullable()->after('department_specific_data');
            $table->foreign('session_shift_id')->references('id')->on('session_shifts')->nullOnDelete();

            // Dedicated lifecycle timestamps
            $table->timestamp('started_at')->nullable()->after('session_shift_id');
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->timestamp('stopped_at')->nullable()->after('paused_at');

            // Accumulated pause duration (sum of all pause/resume cycles in minutes)
            $table->unsignedInteger('total_paused_minutes')->default(0)->after('stopped_at');

            // Optional context for pause and stop events
            $table->string('pause_reason')->nullable()->after('total_paused_minutes');
            $table->string('stop_reason')->nullable()->after('pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropForeign(['session_shift_id']);
            $table->dropColumn([
                'session_shift_id',
                'started_at',
                'paused_at',
                'stopped_at',
                'total_paused_minutes',
                'pause_reason',
                'stop_reason',
            ]);
        });

        DB::statement("
            ALTER TABLE timings
            MODIFY COLUMN status ENUM(
                'complete', 'on progress', 'pending', 'paused', 'frozen'
            ) NOT NULL DEFAULT 'on progress'
        ");
    }
};
