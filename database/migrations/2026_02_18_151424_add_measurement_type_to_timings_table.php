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
            // Add measurement_type: 'qty' or 'percentage'
            $table
                ->enum('measurement_type', ['qty', 'percentage'])
                ->default('qty')
                ->after('output_qty');

            // Add measurement_value to store the actual value (replaces output_qty usage)
            $table->decimal('measurement_value', 10, 2)->nullable()->after('measurement_type');

            // Add duration_hours for calculated total hours worked
            $table->decimal('duration_hours', 8, 2)->nullable()->after('measurement_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timings', function (Blueprint $table) {
            $table->dropColumn(['measurement_type', 'measurement_value', 'duration_hours']);
        });
    }
};
