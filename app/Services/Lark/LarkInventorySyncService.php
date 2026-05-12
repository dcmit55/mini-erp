<?php

namespace App\Services\Lark;

use App\DTO\LarkInventoryDTO;
use App\Models\Logistic\Inventory;
use App\Transformers\InventoryTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Inventory Sync Service
 *
 * ORCHESTRATOR utama untuk sinkronisasi Lark → MySQL (Inventory Listing)
 * Following iSyment pattern (sama seperti ProjectSyncService):
 * - Database transactions
 * - Error handling & logging
 * - Filter berdasarkan Destination, Status, dan DEPT (IMPORTED)
 *
 * FLOW:
 * 1. Fetch data dari Lark API (view_id: vewEW56Qcr)
 * 2. Filter: Destination = "BATAM" AND Status = "Sent Out" AND DEPT (IMPORTED) != "Stock"
 * 3. Convert ke DTO
 * 4. Transform ke database format
 * 5. Upsert ke database
 * 6. Soft delete records yang tidak ada lagi
 */
class LarkInventorySyncService
{
    private LarkApiClient $apiClient;
    private InventoryTransformer $transformer;

    // Lark credentials dari config
    private string $appToken;
    private string $tableId;
    private string $viewId;

    public function __construct(LarkApiClient $apiClient, InventoryTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        // Load from config (sama seperti job orders, menggunakan base_id yang sama)
        $this->appToken = config('lark.base_id');
        $this->tableId = config('lark.inventory.table_id');
        $this->viewId = config('lark.inventory.view_id');
    }

    /**
     * Main sync method with REAL-TIME AGGREGATION
     *
     * STRATEGY (FINAL - CORRECT FOR ADMIN LOGISTIC):
     * - Lark data = Multiple records per material (different projects/shipments)
     * - Inventory Listing = AGGREGATED real-time stock view
     * - Group by: material name (same material = sum all quantities)
     * - This gives admin TOTAL AVAILABLE STOCK per material in real-time
     *
     * @return array Sync statistics
     * @throws \Exception
     */
    public function sync(): array
    {
        $stats = [
            'fetched' => 0,
            'filtered' => 0,
            'created' => 0,
            'updated' => 0,
            'aggregated_groups' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Fetch raw data dari Lark dengan view filter
            Log::info('Starting Lark inventory sync with real-time aggregation', [
                'view_id' => $this->viewId,
                'filter' => 'Destination = BATAM AND Status = Sent Out AND DEPT (IMPORTED) != Stock',
            ]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);
            $stats['fetched'] = count($rawRecords);

            // 2. Process and filter records, convert to DTOs
            $validDTOs = [];
            $larkRecordIds = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkInventoryDTO($rawRecord);

                    // Filter: Destination = "BATAM" AND Status = "Sent Out" AND DEPT (IMPORTED) != "Stock"
                    if (!$dto->passesFilter()) {
                        $stats['skipped']++;
                        Log::debug('Record skipped (filter not passed)', [
                            'record_id' => $dto->recordId,
                            'destination' => $dto->destinationRaw,
                            'status' => $dto->statusRaw,
                        ]);
                        continue;
                    }

                    $stats['filtered']++;
                    $larkRecordIds[] = $dto->recordId;
                    $validDTOs[] = $dto;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to process DTO', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. AGGREGATE by material name - Sum quantities for same material
            $aggregated = [];

            foreach ($validDTOs as $dto) {
                $data = $this->transformer->transform($dto);
                $materialKey = strtolower(trim($data['name'] ?? ''));

                if (!isset($aggregated[$materialKey])) {
                    // First occurrence of this material
                    $aggregated[$materialKey] = $data;
                    $aggregated[$materialKey]['lark_record_ids'] = [$dto->recordId];
                } else {
                    // Material already exists - AGGREGATE
                    // Sum quantities
                    $aggregated[$materialKey]['quantity'] += $data['quantity'] ?? 0;

                    // Track all lark_record_ids for this aggregated material
                    $aggregated[$materialKey]['lark_record_ids'][] = $dto->recordId;

                    // Use latest price (or could average)
                    $aggregated[$materialKey]['price'] = $data['price'] ?? $aggregated[$materialKey]['price'];

                    // Merge project lists (if multiple projects)
                    if (!empty($data['project_lark']) && $aggregated[$materialKey]['project_lark'] !== $data['project_lark']) {
                        $existingProjects = explode(', ', $aggregated[$materialKey]['project_lark'] ?? '');
                        if (!in_array($data['project_lark'], $existingProjects)) {
                            $aggregated[$materialKey]['project_lark'] .= ', ' . $data['project_lark'];
                        }
                    }
                }
            }

            $stats['aggregated_groups'] = count($aggregated);

            // 4. Upsert aggregated data to inventory
            foreach ($aggregated as $materialKey => $data) {
                try {
                    // Store comma-separated lark_record_ids untuk tracking
                    $larkRecordIdsArray = $data['lark_record_ids'] ?? [];
                    $larkRecordIdsStr = implode(',', $larkRecordIdsArray);
                    unset($data['lark_record_ids']); // Remove temporary field

                    // Extract qty/price before upsert (not DB columns anymore)
                    $syncQty = (float) ($data['quantity'] ?? 0);
                    $syncPrice = (float) ($data['price'] ?? 0);
                    unset($data['quantity'], $data['price']);

                    // Upsert by name (or name+supplier if you want more granularity)
                    $inventory = Inventory::updateOrCreate(
                        [
                            'name' => $data['name'],
                            'source' => 'lark', // Only aggregate lark-sourced items
                        ],
                        array_merge($data, [
                            'lark_record_id' => $larkRecordIdsStr, // Store all source record IDs
                        ]),
                    );

                    // Create a batch for this sync (stock addition from Lark)
                    if ($syncQty > 0) {
                        \App\Models\Logistic\InventoryBatch::create([
                            'batch_number' => \App\Models\Logistic\InventoryBatch::generateBatchNumber($inventory->id),
                            'inventory_id' => $inventory->id,
                            'qty' => $syncQty,
                            'qty_remaining' => $syncQty,
                            'unit_price' => $syncPrice,
                            'currency_id' => $inventory->currency_id,
                            'received_date' => now()->toDateString(),
                            'source_type' => \App\Models\Logistic\InventoryBatch::SOURCE_LARK,
                            'source_id' => null,
                        ]);
                    }

                    if ($inventory->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Aggregated inventory synced', [
                        'name' => $data['name'],
                        'inventory_id' => $inventory->id,
                        'total_quantity' => $inventory->quantity,
                        'source_records' => count($larkRecordIdsArray),
                        'action' => $inventory->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'material' => $data['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to sync aggregated inventory', [
                        'data' => $data,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 5. Clean up lark-sourced items that no longer exist in Lark
            // Find all lark-sourced inventories
            $existingLarkInventories = Inventory::where('source', 'lark')->whereNull('deleted_at')->get();

            $currentMaterialKeys = array_keys($aggregated);

            foreach ($existingLarkInventories as $inventory) {
                $materialKey = strtolower(trim($inventory->name));

                if (!in_array($materialKey, $currentMaterialKeys)) {
                    // This material no longer exists in filtered Lark data
                    $inventory->delete();
                    $stats['deactivated'] = ($stats['deactivated'] ?? 0) + 1;

                    Log::info('Lark inventory deactivated (no longer in filtered data)', [
                        'inventory_id' => $inventory->id,
                        'name' => $inventory->name,
                    ]);
                }
            }

            DB::commit();

            Log::info('Lark inventory sync completed with real-time aggregation', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark inventory sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get raw Lark data for debugging (super admin only)
     *
     * @return array
     */
    public function getRawData(): array
    {
        try {
            $records = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

            return [
                'total_records' => count($records),
                'view_id' => $this->viewId,
                'records' => $records,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch raw Lark data', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
