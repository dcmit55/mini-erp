<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_overtime_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments');
            
            // Job Order ID - sesuaikan tipe dengan kolom id di tabel job_orders
            // Asumsi: job_orders.id menggunakan varchar(255)
            $table->string('job_order_id');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            
            $table->text('reason');
            $table->enum('ot_code', ['Normal Day', 'Sunday', 'Public Holiday']);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('total_hours', 5, 2);
            $table->decimal('break_deduction', 5, 2)->default(0);
            $table->decimal('net_hours', 5, 2);

            // Approval HR
            $table->enum('hr_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hr_approved_at')->nullable();

            // Approval Direktur
            $table->enum('director_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('director_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('director_approved_at')->nullable();

            // Status keseluruhan
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('employee_id');
            $table->index('department_id');
            $table->index('job_order_id');
            $table->index('ot_code');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('overtime_requests');
    }
};