<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timings', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal');
        $table->foreignId('project_id')->constrained('projects');
        $table->string('department');
        $table->string('step');
        $table->string('parts');
        $table->foreignId('employee_id')->constrained('employees');
        $table->time('start_time');
        $table->time('end_time');
        $table->integer('output_qty');
        $table->enum('status', ['complete', 'on progress', 'pending']);
        $table->string('remarks')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timings');
    }
};
