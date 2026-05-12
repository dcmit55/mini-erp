<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Add wip_photos as JSON array (stores multiple photo paths)
            // Keeps wip_photo (single) for backward compat
            $table->json('wip_photos')->nullable()->after('wip_photo');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn('wip_photos');
        });
    }
};
