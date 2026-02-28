<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->integer('late_minutes')->default(0);
            $table->decimal('late_deduction', 10, 2)->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->decimal('early_leave_deduction', 10, 2)->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->enum('status', ['Present', 'Late', 'Excused', 'Sick Leave', 'Annual Leave', 'Alpha'])->default('alpha');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_attendances');
    }
};