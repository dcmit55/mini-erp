<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan kapan seorang karyawan boleh tap IN berikutnya
     * berdasarkan aturan 10 jam istirahat minimum antar shift.
     */
    public function up(): void
    {
        Schema::create('next_day_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            // Tanggal sesi SEBELUMNYA (bukan hari berikutnya)
            $table->date('reference_date');
            // clock_out aktual dari sesi referensi
            $table->dateTime('actual_clock_out');
            // Waktu tap IN paling awal yang diizinkan (= actual_clock_out + min_rest)
            $table->dateTime('earliest_allowed_start');
            // Apakah sudah diblokir (true jika ada tap lebih awal yang ditolak)
            $table->boolean('blocked_tap_detected')->default(false);

            $table->timestamps();

            $table->unique(['employee_id', 'reference_date'], 'uniq_nds_emp_date');

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();

            $table->index(['employee_id', 'earliest_allowed_start'], 'idx_nds_emp_earliest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_day_schedules');
    }
};
