<?php

namespace App\Services\Logistic;

use App\Models\Lark\LarkStagingInventory;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StagingInventoryApprovalService
 *
 * Handles the promotion of a LarkStagingInventory record into:
 *   1. inventories  (deduplicated via material_code → name fallback)
 *   2. inventory_batches  (new stock batch)
 *
 * Deduplication order:
 *   Step 1 — match by material_code  (if available)
 *   Step 2 — match by name  (case-insensitive, as fallback)
 *   Step 3 — create new inventory record
 *
 * All DB writes run inside a single transaction (callers may wrap in their
 * own outer transaction; nested transactions are handled by savepoints).
 */
class StagingInventoryApprovalService
{
    /**
     * Process a single staging record.
     *
     * @param  LarkStagingInventory  $staging
     * @param  int|null              $reviewedBy   auth()->id()
     * @param  string|null           $reviewNote
     * @return array{inventory_id: int, batch_id: int|null, created: bool}
     */
    public function approve(LarkStagingInventory $staging, ?int $reviewedBy = null, ?string $reviewNote = null): array
    {
        return DB::transaction(function () use ($staging, $reviewedBy, $reviewNote) {
            // ── 0. Validate received_qty ──────────────────────────────────────
            if (is_null($staging->received_qty) || (float) $staging->received_qty <= 0) {
                throw new \InvalidArgumentException("Received Qty untuk item \"{$staging->name}\" harus diisi sebelum di-approve.");
            }

            try {
                // ── 1. Resolve (or create) the inventory record ──────────────────
                [$inventory, $created] = $this->resolveInventory($staging);

                // ── 2. Create batch for the incoming stock ───────────────────────
                // Uses received_qty (entered by admin), not Lark's quantity
                $batchId = null;
                if ((float) $staging->received_qty > 0) {
                    // Lock the batch_number sequence to prevent race conditions
                    // when multiple approvals happen simultaneously
                    DB::select('SELECT GET_LOCK(?, 5) as l', ['inventory_batch_number_lock']);
                    try {
                        $batchNumber = InventoryBatch::generateBatchNumber($inventory->id);
                        $batch = InventoryBatch::create([
                            'batch_number' => $batchNumber,
                            'inventory_id' => $inventory->id,
                            'qty' => $staging->received_qty,
                            'qty_remaining' => $staging->received_qty,
                            'unit_price' => $staging->price ?? 0,
                            'currency_id' => $staging->currency_id ?? ($inventory->currency_id ?? 6),
                            'received_date' => now()->toDateString(),
                            'source_type' => InventoryBatch::SOURCE_LARK,
                            'source_id' => $staging->id,
                        ]);
                        $batchId = $batch->id;
                    } finally {
                        DB::select('SELECT RELEASE_LOCK(?)', ['inventory_batch_number_lock']);
                    }
                }

                // ── 3. Mark staging as processed + approved + locked ───────────────────────
                // locked=true prevents Lark sync from overwriting this record in the future
                $staging->update([
                    'review_status' => 'approved',
                    'review_note' => $reviewNote,
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => now(),
                    'processed' => true,
                    'locked' => true,
                ]);

                Log::info('Staging inventory approved', [
                    'staging_id' => $staging->id,
                    'inventory_id' => $inventory->id,
                    'batch_id' => $batchId,
                    'created_new' => $created,
                    'name' => $staging->name,
                    'material_code' => $staging->material_code,
                ]);

                return [
                    'inventory_id' => $inventory->id,
                    'batch_id' => $batchId,
                    'created' => $created,
                ];
            } catch (\Illuminate\Database\QueryException $e) {
                // Catch UNIQUE constraint violations (e.g. lark_record_id duplicate)
                // and surface as a user-friendly message instead of a raw SQL error.
                if ($e->errorInfo[1] === 1062) {
                    throw new \InvalidArgumentException("Data item <strong>{$staging->name}</strong> sudah ada di Inventory Listing. " . 'Tidak dapat di-approve karena akan membuat data duplikat. ' . 'Silakan reset item ini dan sesuaikan Material Code / Nama terlebih dahulu.');
                }
                throw $e;
            }
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Find an existing Inventory or create a new one.
     * Deduplication order: material_code → name (case-insensitive) → create.
     *
     * @return array{0: Inventory, 1: bool}  [inventory, wasCreated]
     */
    private function resolveInventory(LarkStagingInventory $staging): array
    {
        $inventory = null;

        // ── Step 1: match by material_code ────────────────────────────────────
        if (!empty($staging->material_code)) {
            $inventory = Inventory::withTrashed()->where('material_code', $staging->material_code)->lockForUpdate()->first();

            if ($inventory) {
                Log::debug('Staging dedup: matched by material_code', [
                    'material_code' => $staging->material_code,
                    'inventory_id' => $inventory->id,
                ]);
            }
        }

        // ── Step 2: fallback — match by name (case-insensitive) ───────────────
        if (!$inventory) {
            $inventory = Inventory::withTrashed()
                ->whereRaw('LOWER(name) = LOWER(?)', [trim($staging->name)])
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                Log::debug('Staging dedup: matched by name', [
                    'name' => $staging->name,
                    'inventory_id' => $inventory->id,
                ]);
            }
        }

        // ── Step 3: restore or update existing record ────────────────────────
        if ($inventory) {
            if ($inventory->trashed()) {
                $inventory->restore();
            }

            $newLarkRecordId = $staging->source_record_ids ?: $staging->lark_record_id;

            // Only update lark_record_id if no OTHER inventory row already owns it
            $larkIdOwner = $newLarkRecordId ? Inventory::withTrashed()->where('lark_record_id', $newLarkRecordId)->where('id', '!=', $inventory->id)->exists() : false;

            $updateData = [
                'project_lark' => $staging->project_lark,
                'unit' => $staging->unit ?: ($inventory->unit ?: 'pcs'),
                'currency_id' => $staging->currency_id ?? ($inventory->currency_id ?? 6),
                'supplier_lark' => $staging->supplier_lark,
                'img' => $staging->img,
                'last_sync_at' => now(),
                'source' => 'lark',
            ];

            if (!$larkIdOwner && $newLarkRecordId) {
                $updateData['lark_record_id'] = $newLarkRecordId;
            }

            // Backfill material_code if existing record doesn't have one yet
            if (empty($inventory->material_code)) {
                $updateData['material_code'] = !empty($staging->material_code) ? $staging->material_code : Inventory::generateMaterialCode();
            }

            $inventory->update($updateData);

            return [$inventory, false];
        }

        // ── Step 4: create new inventory record ───────────────────────────────
        $inventory = Inventory::create([
            'name' => $staging->name,
            'material_code' => $staging->material_code ?: Inventory::generateMaterialCode(),
            'lark_record_id' => $staging->source_record_ids ?: $staging->lark_record_id,
            'project_lark' => $staging->project_lark,
            'unit' => $staging->unit ?: 'pcs',
            'currency_id' => $staging->currency_id ?? 6,
            'supplier_lark' => $staging->supplier_lark,
            'img' => $staging->img,
            'last_sync_at' => now(),
            'source' => 'lark',
        ]);

        Log::debug('Staging dedup: created new inventory', [
            'name' => $staging->name,
            'material_code' => $staging->material_code,
            'inventory_id' => $inventory->id,
        ]);

        return [$inventory, true];
    }
}
