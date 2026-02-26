<?php

namespace App\Services;

use App\Models\Logistic\GoodsMovement;
use App\Models\Logistic\GoodsMovementItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service untuk mapping dan sync data Lark ke Goods Movement
 * 
 * Design Principles:
 * 1. Explicit direction detection (no string parsing!)
 * 2. Full traceability ke Lark fields
 * 3. Idempotent sync (bisa di-run ulang tanpa duplicate)
 * 4. Comprehensive error handling
 */
class LarkGoodsMovementService
{
    /**
     * Map Lark record ke GoodsMovement
     * 
     * @param array $larkRecord Format:
     * [
     *   'record_id' => 'rec_xxxxx',
     *   'sg_bt_courier_id' => 'C001',
     *   'bt_sg_courier_id' => 'C002', 
     *   'sg_bt_item_tracking' => 'TRACK001',
     *   'bt_sg_item_tracking' => 'TRACK002',
     *   'item_name' => 'Material A',
     *   'quantity' => 10,
     *   'unit_cost' => 15.50,
     *   'currency' => 'SGD',
     *   'status' => 'Batam Received',
     *   // ... other fields
     * ]
     * 
     * @return GoodsMovement
     */
    public function syncFromLark(array $larkRecord): GoodsMovement
    {
        DB::beginTransaction();
        
        try {
            // 1. Detect shipment direction (ROBUST METHOD)
            $direction = $this->detectShipmentDirection($larkRecord);
            
            // 2. Extract location info
            [$origin, $destination] = $this->extractLocations($direction);
            
            // 3. Get active courier ID based on direction
            $activeCourierId = $this->getActiveCourierId($larkRecord, $direction);
            
            // 4. Create or update GoodsMovement (idempotent)
            $movement = GoodsMovement::updateOrCreate(
                ['lark_record_id' => $larkRecord['record_id']], // Unique constraint
                [
                    'shipment_direction' => $direction,
                    'origin_location' => $origin,
                    'destination_location' => $destination,
                    
                    // Keep both courier IDs for traceability
                    'lark_courier_sg_bt' => $larkRecord['sg_bt_courier_id'] ?? null,
                    'lark_courier_bt_sg' => $larkRecord['bt_sg_courier_id'] ?? null,
                    
                    // Movement type mapping
                    'movement_type' => $larkRecord['movement_type'] ?? 'Courier',
                    'movement_type_value' => $activeCourierId,
                    
                    // Dates and status
                    'movement_date' => $larkRecord['movement_date'] ?? now(),
                    'status' => $larkRecord['status'] ?? 'pending',
                    
                    // Origin & destination info
                    'origin' => $origin,
                    'destination' => $destination,
                    'sender' => $larkRecord['sender'] ?? null,
                    'receiver' => $larkRecord['receiver'] ?? null,
                    
                    // Sync metadata
                    'lark_synced_at' => now(),
                    'lark_sync_status' => 'synced',
                    'lark_raw_data' => json_encode($larkRecord), // Full audit trail
                    
                    'notes' => $larkRecord['notes'] ?? null,
                ]
            );
            
            // 5. Sync items
            $this->syncItems($movement, $larkRecord);
            
            DB::commit();
            
            Log::info("Lark sync successful", [
                'lark_record_id' => $larkRecord['record_id'],
                'movement_id' => $movement->id,
                'direction' => $direction,
            ]);
            
            return $movement;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Lark sync failed", [
                'lark_record_id' => $larkRecord['record_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Detect shipment direction dengan logic yang ROBUST
     * 
     * Priority:
     * 1. Check explicit direction field (if exists in Lark)
     * 2. Check which courier ID is filled
     * 3. Fallback to status parsing (LAST RESORT)
     */
    private function detectShipmentDirection(array $larkRecord): string
    {
        // Method 1: Explicit direction dari Lark (BEST)
        if (!empty($larkRecord['shipment_direction'])) {
            return $this->normalizeDirection($larkRecord['shipment_direction']);
        }
        
        // Method 2: Check which courier field is filled (GOOD)
        $hasSgBtCourier = !empty($larkRecord['sg_bt_courier_id']);
        $hasBtSgCourier = !empty($larkRecord['bt_sg_courier_id']);
        
        if ($hasSgBtCourier && !$hasBtSgCourier) {
            return 'SG_TO_BT';
        }
        
        if ($hasBtSgCourier && !$hasSgBtCourier) {
            return 'BT_TO_SG';
        }
        
        // Method 3: Parse status (FALLBACK - not ideal but necessary)
        $status = strtolower($larkRecord['status'] ?? '');
        
        if (str_contains($status, 'batam received') || str_contains($status, 'bt received')) {
            return 'SG_TO_BT';
        }
        
        if (str_contains($status, 'sg received') || str_contains($status, 'singapore received')) {
            return 'BT_TO_SG';
        }
        
        // Default
        Log::warning("Unable to detect shipment direction, defaulting to INTERNAL", [
            'lark_record_id' => $larkRecord['record_id'] ?? 'unknown',
        ]);
        
        return 'INTERNAL';
    }
    
    /**
     * Normalize direction string
     */
    private function normalizeDirection(string $direction): string
    {
        $normalized = strtoupper(str_replace(['-', ' ', '→'], '_', $direction));
        
        $validDirections = ['SG_TO_BT', 'BT_TO_SG', 'INTERNAL', 'OTHER'];
        
        if (in_array($normalized, $validDirections)) {
            return $normalized;
        }
        
        // Map common variations
        $mapping = [
            'SGBT' => 'SG_TO_BT',
            'SG2BT' => 'SG_TO_BT',
            'BTSG' => 'BT_TO_SG',
            'BT2SG' => 'BT_TO_SG',
        ];
        
        return $mapping[$normalized] ?? 'OTHER';
    }
    
    /**
     * Extract origin & destination dari direction
     */
    private function extractLocations(string $direction): array
    {
        $locations = [
            'SG_TO_BT' => ['Singapore', 'Batam'],
            'BT_TO_SG' => ['Batam', 'Singapore'],
            'INTERNAL' => ['Internal', 'Internal'],
            'OTHER' => [null, null],
        ];
        
        return $locations[$direction] ?? [null, null];
    }
    
    /**
     * Get active courier ID based on direction
     */
    private function getActiveCourierId(array $larkRecord, string $direction): ?string
    {
        if ($direction === 'SG_TO_BT') {
            return $larkRecord['sg_bt_courier_id'] ?? null;
        }
        
        if ($direction === 'BT_TO_SG') {
            return $larkRecord['bt_sg_courier_id'] ?? null;
        }
        
        // Fallback: use whichever is filled
        return $larkRecord['sg_bt_courier_id'] 
            ?? $larkRecord['bt_sg_courier_id'] 
            ?? null;
    }
    
    /**
     * Sync movement items from Lark
     */
    private function syncItems(GoodsMovement $movement, array $larkRecord): void
    {
        // Clear existing items (untuk re-sync)
        $movement->items()->delete();
        
        // Get item data (bisa array atau single item)
        $items = $larkRecord['items'] ?? [$larkRecord]; // Support both formats
        
        foreach ($items as $itemData) {
            $this->createItem($movement, $itemData);
        }
    }
    
    /**
     * Create single movement item
     */
    private function createItem(GoodsMovement $movement, array $itemData): GoodsMovementItem
    {
        $direction = $movement->shipment_direction;
        
        return GoodsMovementItem::create([
            'goods_movement_id' => $movement->id,
            
            // Lark tracking codes (keep both!)
            'lark_item_tracking_sg_bt' => $itemData['sg_bt_item_tracking'] ?? null,
            'lark_item_tracking_bt_sg' => $itemData['bt_sg_item_tracking'] ?? null,
            
            // Item details
            'lark_item_name' => $itemData['item_name'] ?? $itemData['name'] ?? null,
            'new_material_name' => $itemData['item_name'] ?? null, // Keep existing field
            
            // Quantity
            'quantity' => $itemData['quantity'] ?? 0,
            'unit' => $itemData['unit'] ?? 'pcs',
            
            // Cost data (untuk costing report)
            'lark_unit_cost' => $itemData['unit_cost'] ?? 0,
            'lark_total_cost' => ($itemData['quantity'] ?? 0) * ($itemData['unit_cost'] ?? 0),
            'lark_currency' => $itemData['currency'] ?? 'SGD',
            
            // Project relation (if exists)
            'project_id' => $itemData['project_id'] ?? null,
            
            'notes' => $itemData['notes'] ?? null,
        ]);
    }
    
    /**
     * Get items untuk costing report by project
     */
    public function getCostingDataByProject(int $projectId): array
    {
        $items = GoodsMovementItem::where('project_id', $projectId)
            ->whereNotNull('lark_item_name')
            ->with(['goodsMovement'])
            ->get();
        
        // Group by item name
        $grouped = $items->groupBy('lark_item_name')->map(function ($groupedItems, $itemName) {
            $totalQty = $groupedItems->sum('quantity');
            $avgUnitCost = $groupedItems->avg('lark_unit_cost');
            $totalCost = $groupedItems->sum('lark_total_cost');
            $currency = $groupedItems->first()->lark_currency ?? 'SGD';
            
            return [
                'item_name' => $itemName,
                'total_quantity' => $totalQty,
                'unit' => $groupedItems->first()->unit ?? 'pcs',
                'avg_unit_cost' => round($avgUnitCost, 4),
                'total_cost' => round($totalCost, 2),
                'currency' => $currency,
                'transactions_count' => $groupedItems->count(),
                'shipment_directions' => $groupedItems->pluck('goodsMovement.shipment_direction')->unique()->values(),
            ];
        })->values();
        
        return [
            'total_items' => $grouped->count(),
            'total_cost' => $grouped->sum('total_cost'),
            'items' => $grouped,
        ];
    }
}
