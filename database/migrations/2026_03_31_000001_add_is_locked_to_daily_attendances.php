<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('updated_by')
                ->comment('Jika true, pipeline auto-sync tidak akan menimpa record ini');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
