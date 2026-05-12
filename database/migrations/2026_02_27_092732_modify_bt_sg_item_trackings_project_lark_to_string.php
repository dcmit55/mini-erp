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
        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            // Change project_lark from JSON to VARCHAR
            $table->string('project_lark', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_bt_sg_item_trackings', function (Blueprint $table) {
            // Revert back to JSON
            $table->json('project_lark')->nullable()->change();
        });
    }
};
