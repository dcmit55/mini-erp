<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_reject_logs', function (Blueprint $table) {
            $table->text('root_cause')->nullable()->after('fail_operator');
            $table->text('corrective_action')->nullable()->after('root_cause');
            $table->unsignedSmallInteger('qty_reject')->nullable()->after('fail_note');
        });
    }

    public function down(): void
    {
        Schema::table('qc_reject_logs', function (Blueprint $table) {
            $table->dropColumn(['root_cause', 'corrective_action', 'qty_reject']);
        });
    }
};
