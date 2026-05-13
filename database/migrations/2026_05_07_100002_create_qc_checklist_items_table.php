<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->foreignId('qc_project_id')->constrained('qc_projects')->cascadeOnDelete();

            $table->integer('section_id');          // 1–10 (sesuai SECTIONS const)
            $table->integer('item_id');              // 1–39 (ID item dari checklist)

            $table->enum('status', ['PASS', 'FAIL'])->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['qc_project_id', 'item_id']);
            $table->index(['qc_project_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_checklist_items');
    }
};
