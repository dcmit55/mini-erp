<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master shift bersifat fleksibel dan dikonfigurasi via database.
     * Shift malam (overnight) didukung dengan flag is_overnight.
     * Break window membantu classifier membedakan istirahat vs pulang awal.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->string('name', 100);           // contoh: "Shift Pagi", "Shift Malam"
            $table->string('code', 20)->unique();   // contoh: "PAGI", "SIANG", "MALAM"
            $table->time('shift_start');            // jam mulai shift
            $table->time('shift_end');              // jam selesai shift
            $table->boolean('is_overnight')->default(false); // shift melewati tengah malam
            $table->decimal('expected_hours', 4, 2)->default(8.00); // jam kerja yang diharapkan
            $table->decimal('min_hours_full', 4, 2)->default(8.00); // >= ini = FULL
            $table->decimal('min_hours_short', 4, 2)->default(7.00); // >= ini = SHORT, < ini = INCOMPLETE
            $table->decimal('ot_threshold_hours', 4, 2)->default(9.00); // >= ini = OT

            // Break window: periode di mana tap OUT dianggap "istirahat" bukan "pulang"
            // Jika NULL, hanya mengandalkan aturan 90 menit
            $table->time('break_window_start')->nullable(); // contoh: 12:00
            $table->time('break_window_end')->nullable();   // contoh: 14:00

            // Minimum gap (menit) agar tap OUT diklasifikasi sebagai pulang, bukan istirahat
            // Default 90 menit sesuai aturan bisnis
            $table->unsignedSmallInteger('break_max_duration_mins')->default(90);

            // Minimum rest antar shift (menit). Default 600 = 10 jam
            $table->unsignedSmallInteger('min_rest_between_shifts_mins')->default(600);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
