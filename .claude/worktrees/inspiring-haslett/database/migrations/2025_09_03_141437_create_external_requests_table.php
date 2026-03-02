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
        Schema::create('external_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['new_material', 'restock']);
            $table->string('material_name');
            $table->unsignedBigInteger('inventory_id')->nullable(); // untuk restock
            $table->decimal('required_quantity', 12, 2);
            $table->string('unit');
            $table->decimal('stock_level', 12, 2)->nullable();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('requested_by'); // user_id
            $table->timestamps();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_requests');
    }
};
