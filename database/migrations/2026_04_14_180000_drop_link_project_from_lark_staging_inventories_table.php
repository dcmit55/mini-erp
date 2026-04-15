<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Drop the redundant link_project column from lark_staging_inventories.
     *
     * The "Link Project" field from Lark is already captured in project_lark column.
     * This column was added by mistake and is no longer needed.
     */
    public function up(): void
    {
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->dropColumn('link_project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->text('link_project')->nullable()->after('project_lark')->comment('URL/link dari field "Link Project" di Lark Base');
        });
    }
};
