<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Column already exists — just backfill nulls and enforce NOT NULL
        DB::statement("UPDATE employees SET uid = UUID() WHERE uid IS NULL");

        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->change();
        });
    }
};
