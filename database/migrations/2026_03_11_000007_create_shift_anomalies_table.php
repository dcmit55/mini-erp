<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_anomalies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('anomaly_date');

            $table->enum('anomaly_type', [
                'SHORT_HOURS',    // < 7 jam kerja
                'NO_BREAKS',      // tidak ada istirahat pada shift >= 6 jam
                'LONG_ABSENCE',   // gap antar tap > 90 menit
                'EARLY_LEAVE',    // clock_out sebelum shift_end
                'PATTERN',        // actual_work_hours tepat 8.00 selama 5+ hari berturut-turut
                'EARLY_CHECKIN',  // tap IN sebelum earliest_allowed_start
                'MISSING_OUT',    // tidak ada tap OUT sama sekali
                'DUPLICATE_TAP',  // tap dobel dalam 1 menit
            ]);

            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH'])->default('LOW');

            // Detail konteks anomali (JSON)
            $table->json('context')->nullable();
            // contoh context:
            // {"actual_hours": 6.5, "expected": 8, "gap_mins": 120}
            // {"streak_days": 5, "dates": ["2026-03-01", ...]}

            $table->enum('resolution_status', ['OPEN', 'ACKNOWLEDGED', 'RESOLVED', 'DISMISSED'])
                  ->default('OPEN');

            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['employee_id', 'anomaly_date'], 'idx_sa_emp_date');
            $table->index(['anomaly_type', 'resolution_status'], 'idx_sa_type_status');
            $table->index('severity', 'idx_sa_severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_anomalies');
    }
};
