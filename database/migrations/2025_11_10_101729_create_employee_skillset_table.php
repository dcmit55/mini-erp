<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_skillset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('skillset_id')->constrained()->onDelete('cascade');
            $table->enum('proficiency_level', ['basic', 'intermediate', 'advanced'])->default('basic');
            $table->date('acquired_date')->nullable();
            $table->date('last_used_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'skillset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skillset');
    }
};
