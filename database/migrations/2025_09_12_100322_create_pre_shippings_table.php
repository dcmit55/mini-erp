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
        Schema::create('pre_shippings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_request_id')->unique();
            $table->string('domestic_waybill_no')->nullable();
            $table->boolean('same_supplier_selection')->default(false);
            $table->decimal('percentage_if_same_supplier', 5, 2)->nullable();
            $table->decimal('domestic_cost', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('external_request_id')->references('id')->on('external_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_shippings');
    }
};
