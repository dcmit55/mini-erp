<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->decimal('quantity', 15, 2)->change();
            $table->decimal('actual_quantity', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('actual_quantity')->nullable()->change();
        });
    }
};
