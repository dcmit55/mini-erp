<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->foreignId('project_id')->constrained('projects');
            $table->decimal('used_quantity', 12, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('material_usages');
    }
};
