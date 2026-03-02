<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->foreignId('job_type_grade_id')
                  ->nullable()
                  ->after('department_id') // sesuaikan posisi
                  ->constrained('job_order_type_gradings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign(['job_type_grade_id']);
            $table->dropColumn('job_type_grade_id');
        });
    }
};