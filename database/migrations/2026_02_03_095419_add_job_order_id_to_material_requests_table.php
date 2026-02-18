<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Add job_order_id column as nullable (optional)
            // Material request bisa langsung ke project atau via job order
            $table->string('job_order_id')->nullable()->after('project_id');
            
            // Add foreign key constraint
            $table->foreign('job_order_id')
                  ->references('id')
                  ->on('job_orders')
                  ->onDelete('set null'); // Jika job order dihapus, set null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['job_order_id']);
            
            // Then drop column
            $table->dropColumn('job_order_id');
        });
    }
};
