<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_photos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Polymorphic: bisa ke qc_checklist_items, qc_packing_items, qc_reject_logs, qc_daily_items
            $table->morphs('photoable');

            $table->string('path');          // path di storage (public disk)
            $table->string('disk')->default('public');

            // Context foto untuk keperluan display yang tepat
            $table->enum('context', [
                'item',           // foto checklist item / daily item
                'packing_item',   // foto per packing item (bukti per item)
                'packing_verify', // foto verifikasi keseluruhan packing
                'reject',         // foto bukti defect
                'part',           // foto per operator+part di daily progress
                'finalize',       // foto finalisasi item daily
            ])->default('item');

            // Meta untuk context spesifik, misal: "NamaOp|namaPart" untuk context=part
            $table->string('meta')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['photoable_type', 'photoable_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_photos');
    }
};
