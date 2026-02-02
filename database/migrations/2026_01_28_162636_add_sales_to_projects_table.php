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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sales')->nullable()->after('name')->comment('Sales person from Lark');

            // Add index for lark_record_id if not exists
            if (!Schema::hasColumn('projects', 'lark_record_id')) {
                $table->string('lark_record_id')->nullable()->unique()->after('created_by')->comment('Unique identifier from Lark');
                $table->timestamp('last_sync_at')->nullable()->after('lark_record_id')->comment('Last sync timestamp from Lark');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sales');
        });
    }
};
