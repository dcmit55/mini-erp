<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            // Guard: tambahkan kolom hanya jika belum ada (idempoten)
            // Situasi ini bisa terjadi jika migration sebelumnya gagal di tengah jalan
            if (! Schema::hasColumn('daily_attendances', 'clock_in_datetime')) {
                $table->dateTime('clock_in_datetime')->nullable()->after('date');
            }
            if (! Schema::hasColumn('daily_attendances', 'clock_out_datetime')) {
                $table->dateTime('clock_out_datetime')->nullable()->after('clock_in_datetime');
            }
            if (! Schema::hasColumn('daily_attendances', 'total_break_mins')) {
                $table->unsignedInteger('total_break_mins')->default(0)->after('clock_out_datetime');
            }
            if (! Schema::hasColumn('daily_attendances', 'hours_status')) {
                $table->enum('hours_status', ['FULL', 'SHORT', 'INCOMPLETE', 'OT', 'LEAVE'])
                      ->default('INCOMPLETE')
                      ->after('total_break_mins');
            }
            if (! Schema::hasColumn('daily_attendances', 'exception_type')) {
                $table->enum('exception_type', ['NONE', 'BUSINESS_TRIP', 'MEDICAL', 'APPROVED_ERRAND', 'OTHER'])
                      ->default('NONE')
                      ->after('hours_status');
            }
            if (! Schema::hasColumn('daily_attendances', 'supervisor_approved')) {
                $table->boolean('supervisor_approved')->default(false)->after('exception_type');
            }
            // FK shift_id ditambahkan di migration 000003b setelah tabel shifts dibuat
            if (! Schema::hasColumn('daily_attendances', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('supervisor_approved');
            }
        });

        // Index — cek dulu apakah sudah ada
        $indexes = array_column(
            DB::select("SHOW INDEX FROM daily_attendances WHERE Key_name IN ('idx_da_hours_status', 'idx_da_emp_date_status')"),
            'Key_name'
        );

        Schema::table('daily_attendances', function (Blueprint $table) use ($indexes) {
            if (! in_array('idx_da_hours_status', $indexes)) {
                $table->index('hours_status', 'idx_da_hours_status');
            }
            if (! in_array('idx_da_emp_date_status', $indexes)) {
                $table->index(['employee_id', 'date', 'hours_status'], 'idx_da_emp_date_status');
            }
        });

        // Generated column — cek apakah sudah ada
        if (! Schema::hasColumn('daily_attendances', 'actual_work_hours')) {
            DB::statement("
                ALTER TABLE daily_attendances
                ADD COLUMN actual_work_hours DECIMAL(5,2) GENERATED ALWAYS AS (
                    CASE
                        WHEN clock_in_datetime IS NOT NULL AND clock_out_datetime IS NOT NULL
                        THEN ROUND(
                            (TIMESTAMPDIFF(MINUTE, clock_in_datetime, clock_out_datetime) - total_break_mins) / 60,
                            2
                        )
                        ELSE NULL
                    END
                ) STORED
                AFTER total_break_mins
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_attendances', 'actual_work_hours')) {
            DB::statement("ALTER TABLE daily_attendances DROP COLUMN actual_work_hours");
        }

        Schema::table('daily_attendances', function (Blueprint $table) {
            // Drop index jika ada
            $indexes = array_column(
                DB::select("SHOW INDEX FROM daily_attendances WHERE Key_name IN ('idx_da_hours_status', 'idx_da_emp_date_status')"),
                'Key_name'
            );
            if (in_array('idx_da_hours_status', $indexes)) {
                $table->dropIndex('idx_da_hours_status');
            }
            if (in_array('idx_da_emp_date_status', $indexes)) {
                $table->dropIndex('idx_da_emp_date_status');
            }

            $toDrop = array_filter([
                'clock_in_datetime', 'clock_out_datetime', 'total_break_mins',
                'hours_status', 'exception_type', 'supervisor_approved', 'shift_id',
            ], fn($col) => Schema::hasColumn('daily_attendances', $col));

            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};
