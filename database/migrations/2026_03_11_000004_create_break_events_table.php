<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Setiap baris = satu sesi "keluar sementara" dalam satu hari kerja.
     * Session utama (IN pertama → OUT terakhir) ada di daily_attendances.
     * Break events adalah semua pasangan IN-OUT di antara sesi utama.
     */
    public function up(): void
    {
        Schema::create('break_events', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->unsignedBigInteger('daily_attendance_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('work_date');

            // Tap OUT yang memulai istirahat
            $table->dateTime('break_out');
            // Tap IN yang mengakhiri istirahat (nullable jika belum kembali)
            $table->dateTime('break_in')->nullable();

            // Durasi istirahat dalam menit (generated)
            // NULL jika break_in belum terisi

            // Klasifikasi hasil analisa
            $table->enum('classification', [
                'BREAK',        // istirahat normal (<= 90 menit atau dalam break window)
                'LONG_ABSENCE', // gap > 90 menit, perlu penjelasan
                'UNMATCHED',    // OUT tanpa IN berikutnya (pulang awal atau tap error)
            ])->default('BREAK');

            // Apakah tap OUT terjadi dalam jendela istirahat shift
            $table->boolean('within_break_window')->default(false);

            // Flag anomali untuk ditinjau HR
            $table->boolean('flagged')->default(false);
            $table->text('flag_reason')->nullable();

            $table->timestamps();

            $table->foreign('daily_attendance_id')
                  ->references('id')
                  ->on('daily_attendances')
                  ->cascadeOnDelete();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();

            $table->index(['employee_id', 'work_date'], 'idx_be_emp_date');
            $table->index('daily_attendance_id', 'idx_be_da_id');
            $table->index('classification', 'idx_be_classification');
        });

        // Generated column untuk durasi (menit)
        DB::statement("
            ALTER TABLE break_events
            ADD COLUMN duration_mins INT UNSIGNED GENERATED ALWAYS AS (
                CASE
                    WHEN break_in IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, break_out, break_in)
                    ELSE NULL
                END
            ) STORED
            AFTER break_in
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('break_events');
    }
};
