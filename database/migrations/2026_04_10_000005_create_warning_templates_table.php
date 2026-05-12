<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warning_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('sp_level'); // 1, 2, 3, 4
            $table->string('name', 100);
            $table->longText('content_html');
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['sp_level', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_templates');
    }
};
