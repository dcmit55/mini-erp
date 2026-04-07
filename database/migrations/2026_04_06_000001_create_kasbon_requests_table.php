<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kasbon_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_number', 30)->unique();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('nama_lengkap', 100);
            $table->string('nik_karyawan', 30);
            $table->foreignId('department_id')->constrained('departments');
            $table->string('no_wa', 20);
            $table->decimal('jumlah_diminta', 15, 2);
            $table->decimal('jumlah_disetujui', 15, 2)->nullable();
            $table->tinyInteger('tenor_bulan');
            $table->text('alasan');
            $table->string('dokumen_url', 255)->nullable();
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'disbursed',
                'repaying',
                'settled',
            ])->default('pending');
            $table->string('token', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_admin')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_requests');
    }
};
