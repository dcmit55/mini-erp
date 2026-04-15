<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warning_letter_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warning_letter_id')->unique()->constrained('warning_letters')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->dateTime('acknowledged_at');
            $table->enum('method', ['digital', 'manual'])->default('digital');
            $table->string('signature_path')->nullable();
            $table->foreignId('witness_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_letter_acknowledgments');
    }
};
