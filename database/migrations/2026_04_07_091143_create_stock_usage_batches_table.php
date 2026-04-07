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
        Schema::create('stock_usage_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_out_id');
            $table->unsignedBigInteger('batch_id');
            $table->decimal('qty_used', 15, 4);
            $table->timestamps();

            $table->foreign('goods_out_id')->references('id')->on('goods_out')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->onDelete('cascade');

            $table->index(['goods_out_id']);
            $table->index(['batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_usage_batches');
    }
};
