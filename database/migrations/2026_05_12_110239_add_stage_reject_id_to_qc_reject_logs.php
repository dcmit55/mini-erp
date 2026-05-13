<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_reject_logs', function (Blueprint $table) {
            $table->string('reject_id', 20)->nullable()->after('uid');
            $table->string('stage', 20)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('qc_reject_logs', function (Blueprint $table) {
            $table->dropColumn(['reject_id', 'stage']);
        });
    }
};
