<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master shift per department.
 *
 * type_of_shift: A9, B9, A12, B12
 *   - A9  : 08:00–17:00 (local)
 *   - B9  : 10:00–19:00 (local)
 *   - A12 : 08:00–20:00 (WNA/Filipina)
 *   - B12 : 10:00–22:00 (WNA/Filipina)
 *
 * Auto-detect shift: detect_from ≤ clock_in < detect_until + filter is_wna.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom citizenship ke employees untuk membedakan WNA vs WNI
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('citizenship', ['WNI', 'WNA'])->default('WNI')->after('employment_type');
        });

        Schema::create('session_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->unsignedBigInteger('department_id')->nullable(); // NULL = default semua dept
            $table->string('type_of_shift', 10);    // A9, B9, A12, B12
            $table->time('start_time');              // jam mulai shift
            $table->time('end_time');                // jam selesai shift
            $table->time('break_start')->nullable();  // istirahat 1 (siang)
            $table->time('break_end')->nullable();
            $table->time('break2_start')->nullable(); // istirahat 2 (malam, untuk shift panjang)
            $table->time('break2_end')->nullable();
            $table->boolean('for_wna')->default(false); // true = khusus WNA
            // Window clock-in untuk auto-detect
            $table->time('detect_from');
            $table->time('detect_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')
                  ->references('id')->on('departments')
                  ->nullOnDelete();

            $table->unique(['department_id', 'type_of_shift'], 'uniq_ss_dept_type');
            $table->index(['department_id', 'is_active'], 'idx_ss_dept_active');
        });

        // Tambah kolom session_shift_id ke daily_attendances
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('session_shift_id')
                  ->nullable()
                  ->after('uid');

            $table->foreign('session_shift_id')
                  ->references('id')->on('session_shifts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropForeign(['session_shift_id']);
            $table->dropColumn('session_shift_id');
        });

        Schema::dropIfExists('session_shifts');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('citizenship');
        });
    }
};
