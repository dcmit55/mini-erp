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
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Add currency_id after unit_price. Default = 2 (IDR) because
            // indo_purchases is a domestic Indonesian purchase module.
            $table->unsignedBigInteger('currency_id')->nullable()->default(2)->after('unit_price');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });

        // Backfill all existing rows with IDR (id = 2)
        \Illuminate\Support\Facades\DB::table('indo_purchases')
            ->whereNull('currency_id')
            ->update(['currency_id' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
