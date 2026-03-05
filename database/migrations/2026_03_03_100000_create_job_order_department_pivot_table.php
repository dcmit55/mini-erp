<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Create pivot table for many-to-many relationship between job_orders and departments
     * This allows a job order to be associated with multiple departments from Lark sync
     *
     * STRATEGY:
     * - Keep existing department_id as "primary department" for backward compatibility
     * - Add pivot table for additional departments
     * - Migrate existing department_lark comma-separated values
     */
    public function up(): void
    {
        Schema::create('job_order_department', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->string('job_order_id', 20);
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');

            // Prevent duplicates
            $table->unique(['job_order_id', 'department_id'], 'job_order_department_unique');

            // Foreign key to job_orders
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');

            $table->timestamps();

            // Indexes for performance
            $table->index('job_order_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_department');
    }
};
