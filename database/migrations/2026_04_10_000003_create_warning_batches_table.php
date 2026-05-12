<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warning_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('batch_name', 100);
            $table->text('incident_description')->nullable();
            $table->foreignId('violation_cat_id')->constrained('violation_categories')->onDelete('restrict');
            $table->date('incident_date');
            $table->integer('total_employees')->default(0);
            $table->string('evidence_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_batches');
    }
};
