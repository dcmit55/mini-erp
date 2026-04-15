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
        $table = config('audit.drivers.database.table', 'audits');
        $connection = config('audit.drivers.database.connection', config('database.default'));

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->string('auditable_id', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = config('audit.drivers.database.table', 'audits');
        $connection = config('audit.drivers.database.connection', config('database.default'));

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            // Note: reverting may fail if string IDs exist in the table
            $table->unsignedBigInteger('auditable_id')->change();
        });
    }
};
