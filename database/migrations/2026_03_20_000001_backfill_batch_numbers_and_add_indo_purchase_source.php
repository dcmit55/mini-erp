<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Backfill null/empty batch numbers & link Indo Purchase batches.
 *
 * 1. Any existing inventory_batches row with a NULL or empty batch_number
 *    gets an auto-generated BATCH-XXXX code (sequential, unique, no gaps).
 *
 * 2. Existing inventory_batches rows that were created from indo_purchases
 *    (source_type = 'purchase' with a source_id pointing to indo_purchases)
 *    are NOT auto-converted here — that distinction lives only in newly
 *    created rows going forward. This migration purely fills blank batch codes.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── Step 1: Determine current max sequence ────────────────────────────
        $trueMax = DB::table('inventory_batches')->whereNotNull('batch_number')->where('batch_number', 'like', 'BATCH-%')->selectRaw('MAX(CAST(SUBSTRING(batch_number, 7) AS UNSIGNED)) as max_seq')->value('max_seq') ?? 0;

        // ── Step 2: Fetch all rows with null or empty batch_number ────────────
        $rows = DB::table('inventory_batches')
            ->where(function ($q) {
                $q->whereNull('batch_number')->orWhere('batch_number', '');
            })
            ->orderBy('id')
            ->pluck('id');

        // ── Step 3: Assign sequential BATCH-XXXX codes ───────────────────────
        foreach ($rows as $id) {
            $trueMax++;
            $batchNumber = 'BATCH-' . str_pad($trueMax, 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness even if table already has this code somehow
            while (DB::table('inventory_batches')->where('batch_number', $batchNumber)->exists()) {
                $trueMax++;
                $batchNumber = 'BATCH-' . str_pad($trueMax, 4, '0', STR_PAD_LEFT);
            }

            DB::table('inventory_batches')
                ->where('id', $id)
                ->update(['batch_number' => $batchNumber, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Reverting auto-generated batch numbers is intentionally a no-op;
        // we cannot know which rows were null vs. legitimate BATCH-XXXX values.
    }
};
