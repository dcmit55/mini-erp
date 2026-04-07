<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kasbon_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->unique()->constrained('departments');
            $table->decimal('max_amount', 15, 2)->default(5000000);
            $table->tinyInteger('max_tenor')->default(12);
            $table->tinyInteger('max_active')->default(1);
            $table->integer('cooldown_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_limits');
    }
};
