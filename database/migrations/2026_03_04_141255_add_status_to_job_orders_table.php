<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds status column to track job order progress from Lark
     * Status values from Lark: 'Preparing', 'Delivered', etc.
     * Nullable to handle legacy records and failed syncs gracefully
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('delivery_date')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex(['status']); // Drop index first
            $table->dropColumn('status');
        });
    }
};
