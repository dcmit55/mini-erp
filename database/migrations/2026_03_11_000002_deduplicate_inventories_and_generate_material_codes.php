<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 1. Deduplicate inventories by name (keep oldest record, reassign batches,
 *    merge goods-out/in references, soft-delete duplicates).
 * 2. Generate material_code (MAT-0001 …) for all records without one.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            // ── STEP 1: Deduplicate ──────────────────────────────────────────
            $duplicateGroups = DB::select("
                SELECT LOWER(name) AS lower_name,
                       GROUP_CONCAT(id ORDER BY created_at ASC, id ASC SEPARATOR ',') AS ids
                FROM inventories
                WHERE deleted_at IS NULL
                GROUP BY LOWER(name)
                HAVING COUNT(*) > 1
            ");

            foreach ($duplicateGroups as $group) {
                $ids = explode(',', $group->ids);
                $keepId = (int) array_shift($ids); // oldest = keep
                $dupIds = array_map('intval', $ids); // rest = remove

                // Reassign inventory_batches → keep record
                DB::table('inventory_batches')
                    ->whereIn('inventory_id', $dupIds)
                    ->update(['inventory_id' => $keepId]);

                // Reassign goods_out
                DB::table('goods_out')
                    ->whereIn('inventory_id', $dupIds)
                    ->update(['inventory_id' => $keepId]);

                // Reassign goods_in
                DB::table('goods_in')
                    ->whereIn('inventory_id', $dupIds)
                    ->update(['inventory_id' => $keepId]);

                // Reassign material_requests
                DB::table('material_requests')
                    ->whereIn('inventory_id', $dupIds)
                    ->update(['inventory_id' => $keepId]);

                // Reassign material_usages
                DB::table('material_usages')
                    ->whereIn('inventory_id', $dupIds)
                    ->update(['inventory_id' => $keepId]);

                // Soft-delete duplicates
                DB::table('inventories')
                    ->whereIn('id', $dupIds)
                    ->update(['deleted_at' => now()]);

                Log::info('Deduplicated inventory', [
                    'kept' => $keepId,
                    'removed' => $dupIds,
                    'name' => $group->lower_name,
                ]);
            }

            // ── STEP 2: Generate material_code ───────────────────────────────
            $items = DB::table('inventories')->whereNull('deleted_at')->whereNull('material_code')->orderBy('id')->select('id')->get();

            $seq = 1;
            foreach ($items as $item) {
                DB::table('inventories')
                    ->where('id', $item->id)
                    ->update(['material_code' => 'MAT-' . str_pad($seq, 4, '0', STR_PAD_LEFT)]);
                $seq++;
            }

            echo '  Deduplicated ' . count($duplicateGroups) . ' groups, generated material_code for ' . count($items) . " items.\n";
        });
    }

    public function down(): void
    {
        // Restore soft-deleted duplicates (manual if needed — data-destructive to reverse)
        // Clear generated material_codes
        DB::table('inventories')->update(['material_code' => null]);
    }
};
