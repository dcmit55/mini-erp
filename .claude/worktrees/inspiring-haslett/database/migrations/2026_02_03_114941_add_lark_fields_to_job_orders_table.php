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
            // Lark sync fields
            $table->string('project_lark')->nullable()->after('project_id')->comment('Raw project name dari Lark "Project List"');
            $table->string('department_lark')->nullable()->after('department_id')->comment('Raw department name dari Lark "Dept-in-charge"');
            $table->string('lark_record_id')->nullable()->after('id')->unique()->comment('Unique record ID dari Lark API');
            $table->timestamp('last_sync_at')->nullable()->after('updated_at')->comment('Waktu terakhir sync dari Lark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['project_lark', 'department_lark', 'lark_record_id', 'last_sync_at']);
        });
    }
};
