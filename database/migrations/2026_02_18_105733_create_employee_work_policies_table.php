<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_work_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique(); // unique identifier untuk keperluan API/external
            $table->unsignedBigInteger('employee_id');
            $table->string('employee_no'); // denormalized, diambil dari employees.employee_no
            $table->decimal('weekday_hours', 5, 2)->default(8.00); // jam kerja Senin-Jumat
            $table->decimal('saturday_hours', 5, 2)->default(5.00); // jam kerja Sabtu
            $table->timestamps();
            $table->softDeletes(); // optional

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index('employee_no');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_work_policies');
    }
};