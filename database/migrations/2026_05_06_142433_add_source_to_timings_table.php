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
        Schema::table('timings', function (Blueprint $table) {
            // Track which timing module created this session (mascot/costume/animatronics/across)
            $table->string('source', 30)->nullable()->after('session_type');
        });
    }

    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
