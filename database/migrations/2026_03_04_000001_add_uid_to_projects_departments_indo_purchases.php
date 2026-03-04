<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===== PROJECTS =====
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        // Direct SQL UPDATE — MySQL UUID() generates unique value per row
        DB::statement("UPDATE projects SET uid = UUID() WHERE uid IS NULL");

        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });

        // ===== DEPARTMENTS =====
        Schema::table('departments', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        DB::statement("UPDATE departments SET uid = UUID() WHERE uid IS NULL");

        Schema::table('departments', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });

        // ===== INDO_PURCHASES =====
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        DB::statement("UPDATE indo_purchases SET uid = UUID() WHERE uid IS NULL");

        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->dropColumn('uid');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->dropColumn('uid');
        });

        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->dropColumn('uid');
        });
    }
};
