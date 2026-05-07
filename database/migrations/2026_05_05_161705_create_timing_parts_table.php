<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timing_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g. 'Head', 'Body'
            $table->string('department_type', 50)->default('general'); // mascot|costume|general
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default parts
        $parts = ['Head', 'Body', 'Hand', 'Legs', 'Wings', 'Tail', 'Accessories', 'Shoe', 'Arm', 'Gloves', 'Shirt', 'Pants'];
        foreach ($parts as $i => $name) {
            \DB::table('timing_parts')->insert([
                'name' => $name,
                'department_type' => 'general',
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timing_parts');
    }
};
