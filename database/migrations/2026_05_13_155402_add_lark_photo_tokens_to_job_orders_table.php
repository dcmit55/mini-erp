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
            // Stores the Lark file_token array for wip_photos from the last sync.
            // Used for incremental change detection: if tokens match → skip re-download.
            // Format: ["OFj7bxxx", "OFj7byyy"]  (one token per attachment)
            $table->json('lark_photo_tokens')->nullable()->after('wip_photos');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn('lark_photo_tokens');
        });
    }
};
