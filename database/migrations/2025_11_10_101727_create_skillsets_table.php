<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skillsets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->nullable(); // e.g., 'Production', 'Technical', 'Quality Control'
            $table->text('description')->nullable();
            $table->enum('proficiency_required', ['basic', 'intermediate', 'advanced'])->default('basic');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skillsets');
    }
};
