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
        Schema::table('job_orders', function (Blueprint $table) {
            $table->text('project_images')->nullable()->after('final_image');
            $table->text('latest_designs')->nullable()->after('project_images');
            $table->text('final_images')->nullable()->after('latest_designs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['project_images', 'latest_designs', 'final_images']);
        });
    }
};
