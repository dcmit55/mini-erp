<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add stage_type_id and stage_id FK columns to job_order_timing_plans.
     * Both are nullable for backward compatibility — existing rows keep stage
     * as a plain text string in the legacy `stage` column.
     */
    public function up(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->foreignId('stage_type_id')->nullable()->after('stage')->constrained('stage_types')->onDelete('set null');

            $table->foreignId('stage_id')->nullable()->after('stage_type_id')->constrained('stages')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('job_order_timing_plans', function (Blueprint $table) {
            $table->dropForeign(['stage_type_id']);
            $table->dropForeign(['stage_id']);
            $table->dropColumn(['stage_type_id', 'stage_id']);
        });
    }
};
