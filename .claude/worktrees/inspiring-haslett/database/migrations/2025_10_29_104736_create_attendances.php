<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->time('recorded_time');
            $table->unsignedBigInteger('recorded_by');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['employee_id', 'date']); // Prevent duplicate attendance per day
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
