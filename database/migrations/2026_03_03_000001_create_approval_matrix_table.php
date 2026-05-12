<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini menyimpan aturan approval per module.
     * Menggunakan kolom `role` (string) karena project ini tidak memiliki tabel roles terpisah.
     * Role diambil langsung dari kolom `users.role` (e.g. admin_hr, director, super_admin).
     */
    public function up(): void
    {
        Schema::create('approval_matrix', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('module');   // e.g. leave, overtime
            $table->integer('level');   // urutan approval: 1, 2, 3, ...
            $table->string('role');     // cocok dengan nilai users.role: admin_hr, director, dll
            $table->timestamps();

            $table->unique(['module', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_matrix');
    }
};
