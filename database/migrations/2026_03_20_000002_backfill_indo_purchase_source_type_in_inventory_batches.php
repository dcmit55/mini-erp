<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: any inventory_batch row with source_type = 'goods_in'
 * whose source_id points to a record in the indo_purchases table
 * should have source_type = 'indo_purchase' instead.
 *
 * This corrects historical data created before the indo_purchase source
 * type constant was introduced.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('inventory_batches')
            ->where('source_type', 'goods_in')
            ->whereNotNull('source_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))->from('indo_purchases')->whereColumn('indo_purchases.id', 'inventory_batches.source_id')->whereNull('indo_purchases.deleted_at');
            })
            ->update([
                'source_type' => 'indo_purchase',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Revert: change indo_purchase back to goods_in for rows
        // whose source_id still exists in indo_purchases.
        DB::table('inventory_batches')
            ->where('source_type', 'indo_purchase')
            ->whereNotNull('source_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))->from('indo_purchases')->whereColumn('indo_purchases.id', 'inventory_batches.source_id')->whereNull('indo_purchases.deleted_at');
            })
            ->update([
                'source_type' => 'goods_in',
                'updated_at' => now(),
            ]);
    }
};
