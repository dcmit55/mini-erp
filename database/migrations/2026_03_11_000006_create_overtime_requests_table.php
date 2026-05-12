<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CATATAN: overtime_requests sudah digunakan oleh modul production (lembur job order).
        // Tabel ini menggunakan nama berbeda: attendance_overtime_links
        // untuk menghubungkan daily_attendance dengan overtime_requests yang sudah disetujui.
        Schema::create('attendance_overtime_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_attendance_id');
            // Link ke overtime_requests yang sudah ada (modul production/HR)
            $table->unsignedBigInteger('overtime_request_id');
            // Menit OT yang disetujui untuk sesi kehadiran ini
            $table->unsignedInteger('approved_ot_mins')->default(0);
            $table->timestamps();

            $table->unique(['daily_attendance_id', 'overtime_request_id'], 'uniq_aol_da_ot');

            $table->foreign('daily_attendance_id')
                  ->references('id')->on('daily_attendances')->cascadeOnDelete();
            $table->foreign('overtime_request_id')
                  ->references('id')->on('overtime_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_overtime_links');
    }
};
