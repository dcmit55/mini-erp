<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_daily_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            $table->foreignId('qc_daily_progress_id')->constrained('qc_daily_progress')->cascadeOnDelete();

            $table->string('item_id', 10); // dp1–dp16

            $table->enum('status', ['PASS', 'FAIL'])->nullable();
            $table->text('note')->nullable();

            // Operator yang mengerjakan item ini
            $table->json('operators')->nullable();

            // Data per operator+part: { "NamaOp|namaPart": { status, note } }
            $table->json('parts_data')->nullable();

            // Finalisasi item
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalize_ts')->nullable();

            $table->timestamps();

            $table->unique(['qc_daily_progress_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_daily_items');
    }
};
