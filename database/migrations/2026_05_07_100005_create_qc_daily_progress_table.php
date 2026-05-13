<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_daily_progress', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->foreignId('qc_project_id')->constrained('qc_projects')->cascadeOnDelete();

            $table->date('date');
            $table->string('session_note')->nullable();

            // Operator yang hadir di sesi ini (array nama employee)
            $table->json('operators')->nullable();

            $table->timestamps();

            $table->unique(['qc_project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_daily_progress');
    }
};
