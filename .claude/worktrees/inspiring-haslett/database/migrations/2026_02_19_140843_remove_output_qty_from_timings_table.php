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
            // Drop output_qty column (replaced by measurement_value)
            $table->dropColumn('output_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            // Restore output_qty column if needed
            $table->integer('output_qty')->default(0)->after('parts');
        });
    }
};
