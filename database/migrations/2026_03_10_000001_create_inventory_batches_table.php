<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('qty', 15, 4);
            $table->decimal('qty_remaining', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->date('received_date')->nullable();
            $table->string('source_type')->nullable()->comment('initial_stock | goods_in | purchase | goods_movement | manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_id')->references('id')->on('inventories')->cascadeOnDelete();

            $table->index('inventory_id');
            $table->index('source_type');
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
