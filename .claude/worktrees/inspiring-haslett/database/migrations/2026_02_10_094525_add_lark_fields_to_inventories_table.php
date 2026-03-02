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
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('project_lark')->nullable()->after('img')->comment('Link Project dari Lark (staging data)');
            $table->string('supplier_lark')->nullable()->after('project_lark')->comment('Supplier Name dari Lark (staging data)');
            $table->string('lark_record_id')->nullable()->unique()->after('supplier_lark')->comment('Lark record ID untuk sync tracking');
            $table->timestamp('last_sync_at')->nullable()->after('lark_record_id')->comment('Last sync timestamp dari Lark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['project_lark', 'supplier_lark', 'lark_record_id', 'last_sync_at']);
        });
    }
};
