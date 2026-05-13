<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_packing_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->foreignId('qc_project_id')->constrained('qc_projects')->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['required', 'optional', 'custom'])->default('required');
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_hidden')->default(false); // optional items yang dihide user
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['qc_project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_packing_items');
    }
};
