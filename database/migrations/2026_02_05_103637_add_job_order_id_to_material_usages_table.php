<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            // Add job_order_id column (nullable for backward compatibility)
            // Existing records without job_order represent general project usage
            $table->string('job_order_id', 50)->nullable()->after('project_id');

            // Add foreign key constraint
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('set null');

            // Add index for better query performance
            $table->index('job_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['job_order_id']);

            // Drop index
            $table->dropIndex(['job_order_id']);

            // Drop column
            $table->dropColumn('job_order_id');
        });
    }
};
