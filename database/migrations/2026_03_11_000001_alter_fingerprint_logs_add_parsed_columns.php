<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fingerprint_logs', function (Blueprint $table) {
            // employee_id dari mapping PIN fingerspot → employee
            $table->unsignedBigInteger('employee_id')->nullable()->after('cloud_id');
            // IN = tap masuk, OUT = tap keluar
            $table->enum('direction', ['IN', 'OUT'])->nullable()->after('employee_id');
            // ID perangkat fingerspot yang digunakan
            $table->string('device_id', 50)->nullable()->after('direction');
            // Diisi setelah parsing berhasil (idempoten guard)
            $table->timestamp('parsed_at')->nullable()->after('payload');

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->nullOnDelete();

            // Index untuk query session builder per karyawan per hari
            $table->index(['employee_id', 'event_time'], 'idx_fl_employee_event');
            $table->index('parsed_at', 'idx_fl_parsed_at');
        });
    }

    public function down(): void
    {
        Schema::table('fingerprint_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropIndex('idx_fl_employee_event');
            $table->dropIndex('idx_fl_parsed_at');
            $table->dropColumn(['employee_id', 'direction', 'device_id', 'parsed_at']);
        });
    }
};
