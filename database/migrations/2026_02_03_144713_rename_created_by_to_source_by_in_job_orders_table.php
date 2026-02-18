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
        Schema::table('job_orders', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['created_by']);

            // Rename column and make it nullable with string type
            $table->renameColumn('created_by', 'source_by');
        });

        // Modify column type in separate statement
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('source_by', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Change back to bigInteger
            $table->unsignedBigInteger('source_by')->nullable(false)->change();
        });

        Schema::table('job_orders', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('source_by', 'created_by');

            // Re-add foreign key
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};
