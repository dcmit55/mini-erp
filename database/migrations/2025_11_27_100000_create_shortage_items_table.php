<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('shortage_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('goods_receive_detail_id');
            $table->unsignedBigInteger('purchase_request_id')->nullable(); // Nullable untuk safety

            $table->string('material_name');
            $table->decimal('purchased_qty', 15, 2);
            $table->decimal('received_qty', 15, 2);
            $table->decimal('shortage_qty', 15, 2);

            $table->enum('status', ['pending', 'reshipped', 'partially_reshipped', 'fully_reshipped', 'canceled'])->default('pending');

            $table->integer('resend_count')->default(0);

            $table->text('notes')->nullable();
            $table->string('old_domestic_wbl')->nullable(); // Tracking nomor resi lama

            $table->timestamps();

            $table->foreign('goods_receive_detail_id')->references('id')->on('goods_receive_details')->onDelete('cascade');

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onDelete('set null');

            $table->index('status', 'idx_shortage_status');
            $table->index('material_name', 'idx_shortage_material');
            $table->index(['status', 'created_at'], 'idx_shortage_status_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shortage_items');
    }
};
