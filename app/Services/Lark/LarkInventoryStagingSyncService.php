<?php

namespace App\Services\Lark;

use App\DTO\LarkInventoryDTO;
use App\Models\Lark\LarkStagingInventory;
use App\Transformers\InventoryTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Inventory Staging Sync Service
 *
 * Sync data dari Lark API ke tabel STAGING (lark_staging_inventories),
 * BUKAN langsung ke tabel inventories.
 *
 * FLOW BARU:
 * Lark API → lark_staging_inventories (review/filter) → inventories (setelah approved)
 *
 * Berbeda dari LarkInventorySyncService yang langsung ke inventories,
 * service ini hanya mengisi staging table agar admin bisa review sebelum publish.
 *
 * AGGREGATION tetap dilakukan:
 * - Lark data = Multiple records per material (different projects/shipments)
 * - Staging = AGGREGATED raw data view
 * - Group by: material name (same material = sum all quantities)
 */
class LarkInventoryStagingSyncService
{
    private LarkApiClient $apiClient;
    private InventoryTransformer $transformer;

    private string $appToken;
    private string $tableId;
    private string $viewId;

    public function __construct(LarkApiClient $apiClient, InventoryTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        $this->appToken = config('lark.base_id');
        $this->tableId = config('lark.inventory.table_id');
        $this->viewId = config('lark.inventory.view_id');
    }

    /**
     * Sync data dari Lark ke tabel staging (lark_staging_inventories)
     *
     * Data yang masuk ke staging TIDAK otomatis masuk ke inventories.
     * Admin harus approve/reject masing-masing item di halaman staging.
     *
     * @return array Sync statistics
     * @throws \Exception
     */
    public function syncToStaging(): array
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
            Log::info('Starting Lark inventory STAGING sync', [
                'view_id' => $this->viewId,
                'filter' => 'Destination = BATAM AND Status = Sent Out AND DEPT (IMPORTED) != Stock',
                'target' => 'lark_staging_inventories (NOT inventories)',
            ]);

            // 1. Fetch raw data dari Lark
            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);
            $stats['fetched'] = count($rawRecords);

            // 2. Process, filter, convert to DTOs
            $validDTOs = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkInventoryDTO($rawRecord);

                    if (!$dto->passesFilter()) {
                        $stats['skipped']++;
                        Log::debug('Staging sync: record skipped (filter not passed)', [
                            'record_id' => $dto->recordId,
                            'destination' => $dto->destinationRaw,
                            'status' => $dto->statusRaw,
                            'dept' => $dto->deptImportedRaw,
                        ]);
                        continue;
                    }

                    $stats['filtered']++;
                    $validDTOs[] = $dto;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Staging sync: failed to process DTO', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. AGGREGATE by material name
            $aggregated = [];

            foreach ($validDTOs as $dto) {
                $data = $this->transformer->transform($dto);
                $materialKey = strtolower(trim($data['name'] ?? ''));

                if (!isset($aggregated[$materialKey])) {
                    $aggregated[$materialKey] = $data;
                    $aggregated[$materialKey]['lark_record_ids'] = [$dto->recordId];
                    $aggregated[$materialKey]['destination'] = $dto->destinationRaw;
                    $aggregated[$materialKey]['status_lark'] = $dto->statusRaw;
                    $aggregated[$materialKey]['dept_imported'] = $dto->deptImportedRaw;
                } else {
                    // Aggregate: sum quantities
                    $aggregated[$materialKey]['quantity'] += $data['quantity'] ?? 0;

                    // Track all source records
                    $aggregated[$materialKey]['lark_record_ids'][] = $dto->recordId;

                    // Use latest price
                    $aggregated[$materialKey]['price'] = $data['price'] ?? $aggregated[$materialKey]['price'];

                    // Merge projects (comma-separated)
                    if (!empty($data['project_lark']) && $aggregated[$materialKey]['project_lark'] !== $data['project_lark']) {
                        $existingProjects = explode(', ', $aggregated[$materialKey]['project_lark'] ?? '');
                        if (!in_array($data['project_lark'], $existingProjects)) {
                            $aggregated[$materialKey]['project_lark'] .= ', ' . $data['project_lark'];
                        }
                    }
                }
            }

            $stats['aggregated_groups'] = count($aggregated);

            // 4. Upsert aggregated data ke lark_staging_inventories
            foreach ($aggregated as $materialKey => $data) {
                try {
                    $larkRecordIdsStr = implode(',', $data['lark_record_ids'] ?? []);
                    $recordCount = count($data['lark_record_ids'] ?? [1]);

                    // Upsert ke staging (BUKAN inventories)
                    // Update data yang sudah ada, tapi JANGAN ubah review_status jika sudah diset admin
                    $existing = LarkStagingInventory::where('name', $data['name'])->first();

                    if ($existing) {
                        // Update data Lark terbaru, tapi preserve review_status & review_note
                        $existing->update([
                            'lark_record_id' => $data['lark_record_id'],
                            'project_lark' => $data['project_lark'],
                            'quantity' => $data['quantity'],
                            'unit' => $data['unit'] ?? null,
                            'price' => $data['price'],
                            'currency_id' => $data['currency_id'] ?? 6,
                            'supplier_lark' => $data['supplier_lark'] ?? null,
                            'img' => $data['img'] ?? null,
                            'destination' => $data['destination'] ?? null,
                            'status' => $data['status_lark'] ?? null,
                            'dept_imported' => $data['dept_imported'] ?? null,
                            'source_record_ids' => $larkRecordIdsStr,
                            'source_record_count' => $recordCount,
                            'last_sync_at' => now(),
                            // review_status & review_note NOT reset - preserve admin input
                        ]);

                        $stats['updated']++;

                        Log::debug('Staging inventory updated', [
                            'name' => $data['name'],
                            'staging_id' => $existing->id,
                            'review_status' => $existing->review_status,
                        ]);
                    } else {
                        // New record → default pending
                        $staging = LarkStagingInventory::create([
                            'lark_record_id' => $data['lark_record_id'],
                            'name' => $data['name'],
                            'project_lark' => $data['project_lark'],
                            'quantity' => $data['quantity'],
                            'unit' => $data['unit'] ?? null,
                            'price' => $data['price'],
                            'currency_id' => $data['currency_id'] ?? 6,
                            'supplier_lark' => $data['supplier_lark'] ?? null,
                            'img' => $data['img'] ?? null,
                            'destination' => $data['destination'] ?? null,
                            'status' => $data['status_lark'] ?? null,
                            'dept_imported' => $data['dept_imported'] ?? null,
                            'source_record_ids' => $larkRecordIdsStr,
                            'source_record_count' => $recordCount,
                            'review_status' => 'pending',
                            'last_sync_at' => now(),
                        ]);

                        $stats['created']++;

                        Log::debug('Staging inventory created', [
                            'name' => $data['name'],
                            'staging_id' => $staging->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'material' => $data['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Staging sync: failed to upsert item', [
                        'data' => $data,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info(
                'Lark inventory STAGING sync completed',
                array_merge($stats, [
                    'target' => 'lark_staging_inventories',
                ]),
            );

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark inventory staging sync FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get raw Lark data for debugging
     */
    public function getRawData(): array
    {
        $records = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

        return [
            'total_records' => count($records),
            'view_id' => $this->viewId,
            'records' => $records,
        ];
    }
}
