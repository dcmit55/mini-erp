<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodsOutTable extends Migration
{
    public function up()
    {
        Schema::create('goods_out', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->nullable()->constrained('material_requests')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->string('requested_by');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('goods_out');
    }
}
