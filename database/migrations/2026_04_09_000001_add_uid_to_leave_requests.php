<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        DB::statement("UPDATE leave_requests SET uid = UUID() WHERE uid IS NULL");

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
