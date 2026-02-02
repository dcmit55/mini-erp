<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Format: JO-30012601
            
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('department_id')->constrained('departments');
            
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};