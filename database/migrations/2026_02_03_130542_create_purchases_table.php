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
            $table->string('po_number')->unique();
            $table->date('date');
            $table->foreignId('material_id')->constrained('inventories');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->foreignId('department_id')->constrained();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('job_order_id')->nullable()->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('pic_id')->constrained('employees');
            $table->string('tracking_number')->nullable();
            $table->decimal('total_price', 15, 2);
            $table->decimal('freight', 15, 2)->nullable()->default(0);
            $table->decimal('invoice_total', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->text('finance_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};