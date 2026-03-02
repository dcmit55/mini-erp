<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // KTP, NPWP, Kontrak, dll
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index(['employee_id', 'document_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_documents');
    }
};
