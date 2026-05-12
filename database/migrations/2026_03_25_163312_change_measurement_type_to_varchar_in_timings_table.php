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
        // Change measurement_type from ENUM to VARCHAR(50) to support any unit from the units table
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE timings MODIFY COLUMN measurement_type VARCHAR(50) DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE timings MODIFY COLUMN measurement_type ENUM('qty','pcs','unit','piece','item','set','meter','cm','kg','gram','percentage') DEFAULT 'pcs'");
    }
};
