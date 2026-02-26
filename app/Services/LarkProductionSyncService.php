<?php

namespace App\Services;

use App\Models\Logistic\GoodsMovement;
use App\Models\Logistic\GoodsMovementItem;
use App\Models\Production\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Production-Grade Lark Sync Service
 * 
 * Handles bidirectional sync from multiple Lark views/tables with:
 * - Idempotent operations (can re-run safely)
 * - Merge strategy for SG-BT and BT-SG data
 * - Project name mapping to project_id
 * - Comprehensive error handling and logging
 * - Transaction safety
 * 
 * @author Your Team
 * @version 2.0 (Production)
 */
class LarkProductionSyncService
{
    const SYNC_SOURCE_SG_BT = 'SG_BT';
    const SYNC_SOURCE_BT_SG = 'BT_SG';
    
    const STATUS_PENDING = 'pending';
    const STATUS_SYNCED = 'synced';
    const STATUS_MERGED = 'merged';
    const STATUS_ERROR = 'error';
    
    /**
     * Sync from Lark SG-BT View
     * 
     * @param array $larkData Raw data from Lark SG-BT view
     * @return array Result with success status and details
     */
    public function syncFromSgToBt(array $larkData): array
    {
        return $this->syncShipment($larkData, self::SYNC_SOURCE_SG_BT);
    }
    
    /**
     * Sync from Lark BT-SG View
     * 
     * @param array $larkData Raw data from Lark BT-SG view
     * @return array Result with success status and details
     */
    public function syncFromBtToSg(array $larkData): array
    {
        return $this->syncShipment($larkData, self::SYNC_SOURCE_BT_SG);
    }
    
    /**
     * Core sync logic with merge strategy
     * 
     * Strategy:
     * 1. Check if record exists from opposite direction
     * 2. If exists, MERGE data into existing record
     * 3. If not exists, CREATE new record
     * 4. Always maintain audit trail in lark_raw_data
     * 
     * @param array $larkData
     * @param string $syncSource
     * @return array
     */
    protected function syncShipment(array $larkData, string $syncSource): array
    {
        DB::beginTransaction();
        
        try {
            $recordId = $larkData['record_id'] ?? null;
            $courierId = $larkData['courier_id'] ?? null;
            
            if (!$recordId) {
                throw new \Exception('Missing record_id in Lark data');
            }
            
            // ===== STEP 1: Check for existing shipment =====
            // Look for existing record from EITHER direction
            $existingMovement = $this->findExistingMovement($recordId, $courierId, $syncSource);
            
            if ($existingMovement) {
                // ===== MERGE Strategy =====
                $result = $this->mergeShipmentData($existingMovement, $larkData, $syncSource);
            } else {
                // ===== CREATE Strategy =====
                $result = $this->createNewShipment($larkData, $syncSource);
            }
            
            // ===== STEP 2: Sync Items =====
            $itemsData = $larkData['items'] ?? [];
            $syncedItems = $this->syncItems($result['movement'], $itemsData, $syncSource);
            
            DB::commit();
            
            Log::info("Lark sync successful", [
                'source' => $syncSource,
                'record_id' => $recordId,
                'movement_id' => $result['movement']->id,
                'items_count' => count($syncedItems),
                'action' => $result['action'], // 'created' or 'merged'
            ]);
            
            return [
                'success' => true,
                'action' => $result['action'],
                'movement_id' => $result['movement']->id,
                'items_synced' => count($syncedItems),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Lark sync failed", [
                'source' => $syncSource,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $larkData,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Find existing movement record
     * 
     * Search strategy:
     * 1. Search by lark_record_id for current direction
     * 2. Search by courier_id (if same courier used in both directions, it's same shipment)
     * 3. Search by opposite direction's record_id (for manual matching)
     */
    protected function findExistingMovement(string $recordId, ?string $courierId, string $syncSource): ?GoodsMovement
    {
        $query = GoodsMovement::query();
        
        if ($syncSource === self::SYNC_SOURCE_SG_BT) {
            // Look for SG-BT record OR matching courier in BT-SG
            $query->where(function($q) use ($recordId, $courierId) {
                $q->where('lark_record_id_sg_bt', $recordId);
                if ($courierId) {
                    $q->orWhere('courier_id_sg_bt', $courierId)
                      ->orWhere('courier_id_bt_sg', $courierId);
                }
            });
        } else {
            // Look for BT-SG record OR matching courier in SG-BT
            $query->where(function($q) use ($recordId, $courierId) {
                $q->where('lark_record_id_bt_sg', $recordId);
                if ($courierId) {
                    $q->orWhere('courier_id_bt_sg', $courierId)
                      ->orWhere('courier_id_sg_bt', $courierId);
                }
            });
        }
        
        return $query->first();
    }
    
    /**
     * Merge new data into existing movement
     * 
     * Merge rules:
     * - Keep existing primary data
     * - Add data from opposite direction
     * - Update costs if more complete
     * - Append to lark_raw_data for audit
     */
    protected function mergeShipmentData(GoodsMovement $movement, array $larkData, string $syncSource): array
    {
        $updates = [];
        
        if ($syncSource === self::SYNC_SOURCE_SG_BT) {
            $updates['courier_id_sg_bt'] = $larkData['courier_id'] ?? null;
            $updates['lark_record_id_sg_bt'] = $larkData['record_id'] ?? null;
        } else {
            $updates['courier_id_bt_sg'] = $larkData['courier_id'] ?? null;
            $updates['lark_record_id_bt_sg'] = $larkData['record_id'] ?? null;
        }
        
        // Update costs if provided and not already set
        if (isset($larkData['transport_cost']) && !$movement->transport_cost) {
            $updates['transport_cost'] = $larkData['transport_cost'];
        }
        if (isset($larkData['baggage_cost']) && !$movement->baggage_cost) {
            $updates['baggage_cost'] = $larkData['baggage_cost'];
        }
        if (isset($larkData['gst_cost']) && !$movement->gst_cost) {
            $updates['gst_cost'] = $larkData['gst_cost'];
        }
        
        // Update movement type from Lark if not set
        if (isset($larkData['movement_type']) && !$movement->lark_movement_type) {
            $updates['lark_movement_type'] = $larkData['movement_type'];
        }
        
        // Merge raw data (keep history from both directions)
        $existingRawData = $movement->lark_raw_data ?? [];
        $mergedRawData = array_merge($existingRawData, [
            $syncSource . '_' . now()->timestamp => $larkData
        ]);
        $updates['lark_raw_data'] = $mergedRawData;
        
        // Update sync metadata
        $updates['lark_synced_at'] = now();
        $updates['lark_sync_status'] = self::STATUS_MERGED;
        
        // If this is opposite direction, mark as merged source
        if ($movement->lark_sync_source !== $syncSource) {
            $updates['lark_sync_source'] = 'BOTH'; // Indicates data from both directions
        }
        
        $movement->update($updates);
        
        return [
            'action' => 'merged',
            'movement' => $movement->fresh(),
        ];
    }
    
    /**
     * Create new shipment record
     */
    protected function createNewShipment(array $larkData, string $syncSource): array
    {
        $data = [
            'department_id' => $this->getDepartmentId($larkData),
            'movement_date' => $this->parseDate($larkData['date'] ?? now()),
            'movement_type' => 'Courier', // Default for Lark imports
            'movement_type_value' => $larkData['courier_name'] ?? 'Lark Import',
            'origin' => $this->mapLocation($larkData['origin'] ?? 'SG'),
            'destination' => $this->mapLocation($larkData['destination'] ?? 'BT'),
            'sender' => $larkData['sender'] ?? 'Unknown',
            'receiver' => $larkData['receiver'] ?? 'Unknown',
            'status' => $this->mapStatus($larkData['status'] ?? 'Pending'),
            'sender_status' => 'Sent by Shipping',
            'receiver_status' => $this->mapReceiverStatus($larkData['status'] ?? 'Pending'),
            'notes' => $larkData['notes'] ?? null,
            
            // Lark specific fields
            'lark_movement_type' => $larkData['movement_type'] ?? null,
            'transport_cost' => $larkData['transport_cost'] ?? null,
            'baggage_cost' => $larkData['baggage_cost'] ?? null,
            'gst_cost' => $larkData['gst_cost'] ?? null,
            'qty_total' => $larkData['qty_total'] ?? null,
            'cost_per_item' => $this->calculateCostPerItem($larkData),
            
            'lark_sync_source' => $syncSource,
            'lark_synced_at' => now(),
            'lark_sync_status' => self::STATUS_SYNCED,
            'lark_raw_data' => [$syncSource => $larkData],
        ];
        
        // Set direction-specific fields
        if ($syncSource === self::SYNC_SOURCE_SG_BT) {
            $data['courier_id_sg_bt'] = $larkData['courier_id'] ?? null;
            $data['lark_record_id_sg_bt'] = $larkData['record_id'] ?? null;
        } else {
            $data['courier_id_bt_sg'] = $larkData['courier_id'] ?? null;
            $data['lark_record_id_bt_sg'] = $larkData['record_id'] ?? null;
        }
        
        $movement = GoodsMovement::create($data);
        
        return [
            'action' => 'created',
            'movement' => $movement,
        ];
    }
    
    /**
     * Sync items for a movement
     */
    protected function syncItems(GoodsMovement $movement, array $itemsData, string $syncSource): array
    {
        $syncedItems = [];
        
        foreach ($itemsData as $itemData) {
            $itemRecordId = $itemData['item_record_id'] ?? null;
            
            // Find existing item or create new
            $item = GoodsMovementItem::where('goods_movement_id', $movement->id)
                ->where('lark_item_record_id', $itemRecordId)
                ->first();
            
            $data = [
                'goods_movement_id' => $movement->id,
                'material_type' => 'New Material', // Default for Lark
                'quantity' => $itemData['quantity'] ?? 0,
                'unit' => $itemData['unit'] ?? 'pcs',
                'notes' => $itemData['notes'] ?? null,
                
                // Lark specific fields
                'project_lark' => $itemData['project'] ?? null,
                'status' => $itemData['status'] ?? null,
                'sgd_cost' => $itemData['sgd_cost'] ?? null,
                'lark_item_record_id' => $itemRecordId,
                'lark_sync_source' => $syncSource,
                'lark_synced_at' => now(),
                'lark_raw_data' => $itemData,
            ];
            
            // Set direction-specific item names
            if ($syncSource === self::SYNC_SOURCE_SG_BT) {
                $data['item_name_sg_bt'] = $itemData['item_name'] ?? null;
            } else {
                $data['item_name_bt_sg'] = $itemData['item_name'] ?? null;
            }
            
            // Try to map project_lark to project_id
            if (!empty($data['project_lark'])) {
                $projectId = $this->mapProjectName($data['project_lark']);
                if ($projectId) {
                    $data['project_id'] = $projectId;
                }
            }
            
            if ($item) {
                $item->update($data);
            } else {
                $item = GoodsMovementItem::create($data);
            }
            
            $syncedItems[] = $item;
        }
        
        return $syncedItems;
    }
    
    /**
     * Map project name from Lark to project_id
     * 
     * Strategy:
     * 1. Exact match on project name
     * 2. Fuzzy match (similar_text)
     * 3. Return null if no match (manual mapping required)
     */
    protected function mapProjectName(string $larkProjectName): ?int
    {
        // Exact match
        $project = Project::where('name', $larkProjectName)->first();
        if ($project) {
            return $project->id;
        }
        
        // Fuzzy match (85% similarity threshold)
        $projects = Project::all();
        $bestMatch = null;
        $bestSimilarity = 0;
        
        foreach ($projects as $project) {
            similar_text(
                strtolower($larkProjectName),
                strtolower($project->name),
                $similarity
            );
            
            if ($similarity > 85 && $similarity > $bestSimilarity) {
                $bestMatch = $project;
                $bestSimilarity = $similarity;
            }
        }
        
        if ($bestMatch) {
            Log::info("Fuzzy matched project", [
                'lark_name' => $larkProjectName,
                'matched_name' => $bestMatch->name,
                'similarity' => $bestSimilarity,
            ]);
            return $bestMatch->id;
        }
        
        // No match found - log for manual review
        Log::warning("Could not map Lark project to existing project", [
            'lark_project_name' => $larkProjectName,
        ]);
        
        return null;
    }
    
    /**
     * Helper: Calculate cost per item
     */
    protected function calculateCostPerItem(array $larkData): ?float
    {
        $totalCost = ($larkData['transport_cost'] ?? 0) 
                   + ($larkData['baggage_cost'] ?? 0) 
                   + ($larkData['gst_cost'] ?? 0);
        
        $qtyTotal = $larkData['qty_total'] ?? 0;
        
        if ($qtyTotal > 0) {
            return round($totalCost / $qtyTotal, 2);
        }
        
        return null;
    }
    
    /**
     * Helper: Get department ID (default to first department)
     */
    protected function getDepartmentId(array $larkData): int
    {
        // You can add logic to map department from Lark data
        return $larkData['department_id'] ?? 1; // Default department
    }
    
    /**
     * Helper: Parse date
     */
    protected function parseDate($date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }
        
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
    
    /**
     * Helper: Map location to enum
     */
    protected function mapLocation(string $location): string
    {
        $map = [
            'Singapore' => 'SG',
            'SG' => 'SG',
            'Batam' => 'BT',
            'BT' => 'BT',
            'China' => 'CN',
            'CN' => 'CN',
        ];
        
        return $map[$location] ?? 'Other';
    }
    
    /**
     * Helper: Map status
     */
    protected function mapStatus(string $larkStatus): string
    {
        if (stripos($larkStatus, 'received') !== false) {
            return 'Received';
        }
        
        return 'Pending';
    }
    
    /**
     * Helper: Map receiver status
     */
    protected function mapReceiverStatus(string $larkStatus): string
    {
        if (stripos($larkStatus, 'received') !== false) {
            return 'Received';
        }
        
        if (stripos($larkStatus, 'shipped') !== false || stripos($larkStatus, 'sent') !== false) {
            return 'Sent by Shipping';
        }
        
        return 'Pending';
    }
    
    /**
     * Get unmapped projects (for manual review)
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getUnmappedProjects()
    {
        return GoodsMovementItem::whereNotNull('project_lark')
            ->whereNull('project_id')
            ->select('project_lark')
            ->distinct()
            ->pluck('project_lark');
    }
    
    /**
     * Manually map project_lark to project_id
     * 
     * @param string $larkProjectName
     * @param int $projectId
     * @return int Number of updated records
     */
    public function manualMapProject(string $larkProjectName, int $projectId): int
    {
        return GoodsMovementItem::where('project_lark', $larkProjectName)
            ->whereNull('project_id')
            ->update(['project_id' => $projectId]);
    }
}
