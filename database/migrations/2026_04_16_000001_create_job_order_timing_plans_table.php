<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_order_timing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('job_order_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('user.id who set this plan');
            $table->timestamps();

            $table->unique(['job_order_id', 'employee_id'], 'uq_jo_employee_plan');

            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('job_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_timing_plans');
    }
};
