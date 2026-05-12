<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_payroll_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('type', ['SP4_ACTIVE', 'TERMINATION_PROCESS'])->default('SP4_ACTIVE');
            $table->foreignId('warning_letter_id')->nullable()->constrained('warning_letters')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['employee_id', 'type', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_flags');
    }
};
