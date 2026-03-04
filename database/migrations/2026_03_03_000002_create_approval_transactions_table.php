<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel audit trail untuk setiap proses approval yang terjadi.
     * Setiap baris merepresentasikan satu level approval dari satu reference.
     */
    public function up(): void
    {
        Schema::create('approval_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('module');                          // e.g. leave, overtime
            $table->unsignedBigInteger('reference_id');        // id dari leave_requests / overtime_requests
            $table->integer('level');                          // level approval ini
            $table->unsignedBigInteger('approved_by')->nullable(); // user yang melakukan aksi
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['module', 'reference_id']);
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_transactions');
    }
};
