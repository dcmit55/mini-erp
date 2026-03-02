<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('goods_receive_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receive_id');
            $table->unsignedBigInteger('shipping_detail_id');
            $table->string('purchase_type');
            $table->string('project_name')->nullable();
            $table->string('material_name');
            $table->string('supplier_name')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('domestic_waybill_no')->nullable();
            $table->decimal('purchased_qty', 15, 2)->nullable();
            $table->string('received_qty')->nullable(); // varchar sesuai permintaan
            $table->timestamps();

            $table->foreign('goods_receive_id')->references('id')->on('goods_receives')->onDelete('cascade');
            $table->foreign('shipping_detail_id')->references('id')->on('shipping_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_details');
    }
};
