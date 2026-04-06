<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->enum('type', ['free', 'paid_leave_deduction', 'unpaid'])
                  ->default('free')
                  ->comment('free=libur tanpa potong, paid_leave_deduction=potong saldo cuti, unpaid=tidak dibayar');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};
