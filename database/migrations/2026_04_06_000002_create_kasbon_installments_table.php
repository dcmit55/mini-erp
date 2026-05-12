<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kasbon_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasbon_id')->constrained('kasbon_requests')->cascadeOnDelete();
            $table->tinyInteger('bulan_ke');
            $table->date('due_date');
            $table->decimal('jumlah_cicilan', 15, 2);
            $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'partial'])->default('pending');
            $table->enum('metode', ['cash', 'payroll_deduction'])->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_installments');
    }
};
