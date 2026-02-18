<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // Basic
            $table->string('po_number')->unique();
            $table->date('date');

            // Material
            $table->foreignId('material_id')
                  ->constrained('inventories')
                  ->cascadeOnDelete();

            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);

            // Department & Project
            $table->foreignId('department_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('project_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // =============================
            // 🔧 FIX JOB ORDER (VARCHAR)
            // =============================
            $table->string('job_order_id', 20)->nullable()->index();

            $table->foreign('job_order_id')
                  ->references('id')
                  ->on('job_orders')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            // Supplier & PIC
            $table->foreignId('supplier_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('pic_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            // Tracking
            $table->string('tracking_number')->nullable();

            // Pricing
            $table->decimal('total_price', 15, 2);
            $table->decimal('freight', 15, 2)->nullable()->default(0);
            $table->decimal('invoice_total', 15, 2);

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            $table->text('note')->nullable();
            $table->text('finance_notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Approval
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
