<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('goods_in', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_out_id')->nullable()->constrained('goods_out')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->string('returned_by');
            $table->timestamp('returned_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('goods_in');
    }
};