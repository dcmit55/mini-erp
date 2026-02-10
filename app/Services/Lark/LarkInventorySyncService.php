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
 * - Filter berdasarkan Destination dan Status
 *
 * FLOW:
 * 1. Fetch data dari Lark API (view_id: vewEW56Qcr)
 * 2. Filter: Destination = "BATAM" AND Status = "Sent Out"
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
     * Main sync method - Called from controller/job
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
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Fetch raw data dari Lark dengan view filter
            Log::info('Starting Lark inventory sync', [
                'view_id' => $this->viewId,
                'filter' => 'Destination = BATAM AND Status = Sent Out',
            ]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

            $stats['fetched'] = count($rawRecords);

            // 2. Process each record with filter
            $larkRecordIds = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    // Convert to DTO
                    $dto = new LarkInventoryDTO($rawRecord);

                    // Filter: Destination = "BATAM" AND Status = "Sent Out"
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

                    // Transform to database format
                    $data = $this->transformer->transform($dto);

                    // Validate
                    $this->transformer->validate($data);

                    // Upsert to database
                    $inventory = Inventory::updateOrCreate(['lark_record_id' => $dto->recordId], $data);

                    if ($inventory->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Inventory synced', [
                        'lark_record_id' => $dto->recordId,
                        'inventory_id' => $inventory->id,
                        'action' => $inventory->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync inventory', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. Soft delete inventories yang tidak ada lagi di Lark
            // (hanya yang punya lark_record_id dan tidak ada di hasil fetch)
            if (!empty($larkRecordIds)) {
                $deactivated = Inventory::whereNotNull('lark_record_id')->whereNotIn('lark_record_id', $larkRecordIds)->whereNull('deleted_at')->get();

                foreach ($deactivated as $inventory) {
                    $inventory->delete(); // Soft delete
                    $stats['deactivated'] = ($stats['deactivated'] ?? 0) + 1;

                    Log::info('Inventory deactivated (removed from Lark)', [
                        'inventory_id' => $inventory->id,
                        'lark_record_id' => $inventory->lark_record_id,
                        'name' => $inventory->name,
                    ]);
                }
            }

            DB::commit();

            Log::info('Lark inventory sync completed', $stats);

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
