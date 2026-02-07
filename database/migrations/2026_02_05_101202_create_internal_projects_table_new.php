<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check jika tabel sudah ada
        if (!Schema::hasTable('internal_projects')) {
            Schema::create('internal_projects', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->enum('project', ['Office', 'Machine', 'Testing', 'Facilities']);
                $table->text('job');
                $table->string('department', 100);
                $table->unsignedBigInteger('pic');
                $table->unsignedBigInteger('update_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->char('uid', 36)->unique();
                
                // Foreign key constraints
                $table->foreign('pic')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict');
                    
                $table->foreign('update_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_projects');
    }
};