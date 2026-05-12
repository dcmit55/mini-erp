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
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            // Actual received quantity filled by admin before approving.
            // This is the value used for inventory_batches.qty (not Lark quantity).
            $table->decimal('received_qty', 15, 2)->nullable()->after('quantity')->comment('Actual received qty entered by admin. Used for batch creation on approve.');

            // When true, Lark sync will skip overwriting this record.
            // Set to true automatically when the record is approved.
            $table->boolean('locked')->default(false)->after('processed')->comment('Locked records are skipped by Lark sync. Set to true on approve.');

            $table->index('locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->dropIndex(['locked']);
            $table->dropColumn(['received_qty', 'locked']);
        });
    }
};
