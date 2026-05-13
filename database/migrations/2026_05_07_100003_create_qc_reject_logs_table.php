<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_reject_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->foreignId('qc_project_id')->constrained('qc_projects')->cascadeOnDelete();

            // Source: dari finishing checklist atau daily progress
            $table->enum('source', ['finishing', 'daily_progress'])->default('finishing');
            $table->integer('item_id')->nullable();          // untuk finishing checklist
            $table->string('daily_item_id', 10)->nullable(); // dp1–dp16
            $table->date('fail_date_str')->nullable();        // tanggal sesi daily

            $table->string('item_name');
            $table->string('defect_category');
            $table->enum('severity', ['Critical', 'Major'])->default('Major');
            $table->text('fail_note');

            $table->string('fail_operator')->nullable();      // nama operator yang melaporkan
            $table->string('rework_assigned_to')->nullable(); // nama operator yang mengerjakan rework
            $table->date('target_completion_date')->nullable();

            $table->enum('rework_status', ['OPEN', 'IN_REPAIR', 'REPAIRED-PQC', 'CLOSED'])->default('OPEN');
            $table->timestamp('closed_date')->nullable();

            // History rework disimpan sebagai JSON
            $table->json('rework_history')->nullable();

            $table->timestamps();

            $table->index(['qc_project_id', 'rework_status']);
            $table->index(['qc_project_id', 'fail_date_str']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_reject_logs');
    }
};
