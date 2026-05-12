<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Create 4 staging tables untuk data mentah dari Lark
     * Tujuan: Memisahkan data Lark dari ERP utama untuk avoid conflicts
     */
    public function up(): void
    {
        // 1. BT-SG Courier IDs (Batam to Singapore)
        Schema::create('lark_bt_sg_courier_ids', function (Blueprint $table) {
            $table->id();
            $table->string('courier_id', 255)->nullable()->index();
            $table->string('type_movement', 100)->nullable();
            $table->date('date')->nullable()->index();
            $table->string('project_lark', 255)->nullable()->index();
            $table->decimal('transport_cost', 15, 2)->nullable();
            $table->decimal('baggage_cost', 15, 2)->nullable();
            $table->decimal('gst_cost', 15, 2)->nullable();
            $table->integer('qty_total')->nullable();
            $table->decimal('cost_per_item', 15, 2)->nullable();
            $table->timestamps();
        });

        // 2. BT-SG Item Tracking
        Schema::create('lark_bt_sg_item_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 255)->nullable()->index();
            $table->string('status', 100)->nullable()->index();
            $table->integer('qty')->nullable();
            $table->decimal('sgd_cost', 15, 2)->nullable();
            $table->timestamps();
        });

        // 3. SG-BT Courier IDs (Singapore to Batam)
        Schema::create('lark_sg_bt_courier_ids', function (Blueprint $table) {
            $table->id();
            $table->string('courier_id', 255)->nullable()->index();
            $table->string('type_movement', 100)->nullable();
            $table->date('date')->nullable()->index();
            $table->string('project_lark', 255)->nullable()->index();
            $table->decimal('transport_cost', 15, 2)->nullable();
            $table->decimal('baggage_cost', 15, 2)->nullable();
            $table->decimal('gst_cost', 15, 2)->nullable();
            $table->integer('qty_total')->nullable();
            $table->decimal('cost_per_item', 15, 2)->nullable();
            $table->timestamps();
        });

        // 4. SG-BT Item Tracking
        Schema::create('lark_sg_bt_item_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 255)->nullable()->index();
            $table->string('status', 100)->nullable()->index();
            $table->integer('qty')->nullable();
            $table->decimal('sgd_cost', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lark_sg_bt_item_trackings');
        Schema::dropIfExists('lark_sg_bt_courier_ids');
        Schema::dropIfExists('lark_bt_sg_item_trackings');
        Schema::dropIfExists('lark_bt_sg_courier_ids');
    }
};
