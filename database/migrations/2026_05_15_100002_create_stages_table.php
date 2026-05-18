<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_type_id')->constrained('stage_types')->onDelete('cascade');
            $table->string('name');
            $table->unsignedSmallInteger('sequence')->nullable()->comment('Display order within stage type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('stage_type_id');
            $table->index(['stage_type_id', 'is_active', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
