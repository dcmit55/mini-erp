<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_job_order_type_grading', function (Blueprint $table) {
            $table->id();

            // Kolom unsigned integer
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('job_order_type_grading_id');

            // Foreign key dengan nama pendek
            $table->foreign('department_id', 'fk_dept_jotg_dept')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('cascade');

            $table->foreign('job_order_type_grading_id', 'fk_dept_jotg_grading')
                  ->references('id')
                  ->on('job_order_type_gradings')
                  ->onDelete('cascade');

            $table->timestamps();

            // Unique constraint
            $table->unique(['department_id', 'job_order_type_grading_id'], 'dept_jotg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_job_order_type_grading');
    }
};