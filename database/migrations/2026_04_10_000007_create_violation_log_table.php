<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('violation_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignId('violation_cat_id')->constrained('violation_categories')->onDelete('restrict');
            $table->date('violation_date');
            $table->enum('source', ['manual', 'bulk'])->default('manual');
            $table->foreignId('warning_letter_id')->nullable()->constrained('warning_letters')->onDelete('set null');
            $table->foreignId('batch_id')->nullable()->constrained('warning_batches')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'violation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_log');
    }
};
