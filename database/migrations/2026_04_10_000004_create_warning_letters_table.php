<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warning_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('letter_number', 50)->unique();

            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->unsignedTinyInteger('sp_level'); // 1, 2, 3, 4
            $table->foreignId('violation_cat_id')->constrained('violation_categories')->onDelete('restrict');
            $table->date('violation_date');
            $table->text('reason');

            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'acknowledged',
                'rejected',
                'expired',
            ])->default('draft');

            $table->unsignedBigInteger('template_id')->nullable(); // FK ke warning_templates (no constraint — tabel dibuat setelah ini)
            $table->string('pdf_path')->nullable();

            $table->date('issued_date')->nullable();
            $table->date('valid_until')->nullable(); // issued_date + 180 days

            // null = individual, filled = part of bulk batch
            $table->foreignId('batch_id')->nullable()->constrained('warning_batches')->onDelete('set null');

            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->enum('trigger_source', ['manual', 'bulk'])->default('manual');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'valid_until']);
            $table->index(['sp_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_letters');
    }
};
