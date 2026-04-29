<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill unit_id for all inventories that still only have unit string column.
 *
 * Strategy:
 *  - Case-insensitive match: units.name = inventories.unit  → use existing unit row
 *  - No match (e.g. '1','3','7','laura') → create new unit row first
 *  - Includes soft-deleted inventories (withTrashed) so nothing is left behind
 *
 * Safe to re-run: only touches rows where unit_id IS NULL and unit IS NOT NULL.
 */
return new class extends Migration {
    public function up(): void
    {
        // Step 1: collect distinct unit strings that have no unit_id
        $unmatched = DB::table('inventories')->whereNull('unit_id')->whereNotNull('unit')->where('unit', '!=', '')->select('unit')->distinct()->pluck('unit');

        foreach ($unmatched as $unitStr) {
            // Try case-insensitive match in units table
            $unit = DB::table('units')
                ->whereRaw('LOWER(name) = LOWER(?)', [$unitStr])
                ->first();

            if (!$unit) {
                // Create new unit so no data is lost
                $id = DB::table('units')->insertGetId([
                    'name' => $unitStr,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $unitId = $id;
            } else {
                $unitId = $unit->id;
            }

            // Fill unit_id for all inventories with this unit string (including soft-deleted)
            DB::table('inventories')
                ->whereNull('unit_id')
                ->whereRaw('LOWER(unit) = LOWER(?)', [$unitStr])
                ->update(['unit_id' => $unitId]);
        }
    }

    /**
     * Rollback: null out unit_id only for rows backfilled by this migration
     * (i.e. rows where unit_id was null before — we can't know for sure, so we skip rollback).
     * Data is still safe because unit string column is unchanged.
     */
    public function down(): void
    {
        // Not reversible without tracking which rows were changed.
        // The unit column is untouched, so rolling back the migration
        // and re-running up() is always safe.
    }
};
