<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('overtime_pay_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained('overtime_requests')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('ot_code', ['Normal Day', 'Sunday', 'Public Holiday']);
            $table->decimal('net_hours', 5, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_pay', 12, 2);
            $table->json('breakdown')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('overtime_request_id');
            $table->index('employee_id');
            $table->index('calculated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('overtime_pay_details');
    }
};