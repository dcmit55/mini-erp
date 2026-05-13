<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Relasi ke sistem existing
            $table->string('job_order_id', 20)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            // Snapshot data dari job order (agar tetap terbaca jika JO dihapus)
            $table->string('job_number');
            $table->string('project_name');

            $table->enum('mascot_type', ['Mascot', 'Inflatable'])->default('Mascot');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->date('inspection_date');
            $table->date('deadline')->nullable();
            $table->integer('total_unit')->default(1);

            $table->enum('status', ['WIP', 'Delivered', 'Rejected'])->default('WIP');
            $table->string('cover_gradient')->nullable();
            $table->string('cover_image_path')->nullable();

            $table->boolean('packing_verified')->default(false);

            // JSON columns untuk data yang tidak perlu di-query langsung
            $table->json('final_decision')->nullable();
            $table->json('custom_parts')->nullable();
            $table->json('packing_config')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_order_id')->references('id')->on('job_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_projects');
    }
};
