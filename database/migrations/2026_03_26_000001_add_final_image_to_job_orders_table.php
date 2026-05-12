<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Stores URL(s) of final image(s) from Lark "Final Image (Before Delivery)" field
            // Can be comma-separated if Lark returns multiple attachment URLs
            $table->text('final_image')->nullable()->after('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn('final_image');
        });
    }
};
