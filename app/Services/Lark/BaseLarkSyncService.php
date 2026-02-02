<?php

namespace App\Services\Lark;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Lark Sync Service
 *
 * Abstract class untuk semua Lark sync services.
 * Menyediakan reusable sync logic dengan:
 * - Fetch records dari Lark API
 * - Transform dengan DTO & Transformer
 * - Upsert ke database (updateOrCreate)
 * - Soft delete untuk records yang dihapus
 * - Error handling & statistics
 *
 * Cara pakai:
 * 1. Extend class ini
 * 2. Implement abstract methods:
 *    - getDtoClass()
 *    - getModelClass()
 *    - getUniqueKey()
 * 3. Set properties di constructor:
 *    - $baseId, $tableId, $viewId
 *    - $transformer
 *
 * Contoh:
 * class LarkProcurementSyncService extends BaseLarkSyncService
 * {
 *     protected function getDtoClass(): string { return LarkProcurementDTO::class; }
 *     protected function getModelClass(): string { return Procurement::class; }
 *     protected function getUniqueKey(): string { return 'lark_record_id'; }
 * }
 */
abstract class BaseLarkSyncService
{
    protected LarkApiClient $apiClient;
    protected $transformer; // Transformer instance (varies by child class)

    protected string $baseId;
    protected string $tableId;
    protected ?string $viewId = null;

    /**
     * Constructor - inject API client
     *
     * @param LarkApiClient $apiClient
     */
    public function __construct(LarkApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Abstract methods yang HARUS diimplementasi child class
     */

    /**
     * Get DTO class name
     *
     * @return string Fully qualified DTO class name (e.g., LarkProjectDTO::class)
     */
    abstract protected function getDtoClass(): string;

    /**
     * Get Eloquent Model class name
     *
     * @return string Fully qualified Model class name (e.g., Project::class)
     */
    abstract protected function getModelClass(): string;

    /**
     * Get unique key column untuk upsert
     *
     * @return string Column name (e.g., 'lark_record_id')
     */
    abstract protected function getUniqueKey(): string;

    /**
     * Main sync method
     *
     * Flow:
     * 1. Fetch records dari Lark API
     * 2. Loop setiap record:
     *    - Convert ke DTO
     *    - Transform ke database format
     *    - Validate
     *    - Upsert (updateOrCreate)
     * 3. Soft delete records yang tidak ada lagi di Lark
     * 4. Return statistics
     *
     * @return array{fetched: int, created: int, updated: int, errors: int, deactivated: int, error_details: array}
     */
    public function sync(): array
    {
        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'deactivated' => 0,
            'error_details' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Fetch records dari Lark
            $records = $this->apiClient->fetchRecords($this->baseId, $this->tableId, $this->viewId);
            $stats['fetched'] = count($records);

            Log::info('Fetched records from Lark', [
                'service' => static::class,
                'count' => $stats['fetched'],
                'view_id' => $this->viewId,
            ]);

            // 2. Process setiap record
            $larkRecordIds = [];
            $dtoClass = $this->getDtoClass();
            $modelClass = $this->getModelClass();
            $uniqueKey = $this->getUniqueKey();

            foreach ($records as $rawRecord) {
                try {
                    // Convert to DTO
                    $dto = new $dtoClass($rawRecord);
                    $larkRecordIds[] = $dto->recordId;

                    // Transform to database format
                    $data = $this->transformer->transform($dto);

                    // Validate (jika transformer punya method validate)
                    if (method_exists($this->transformer, 'validate')) {
                        $this->transformer->validate($data);
                    }

                    // Upsert to database
                    $record = $modelClass::updateOrCreate([$uniqueKey => $dto->recordId], $data);

                    if ($record->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Record synced', [
                        'service' => static::class,
                        'record_id' => $dto->recordId,
                        'model_id' => $record->id,
                        'action' => $record->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync record', [
                        'service' => static::class,
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // 3. Soft delete records yang tidak ada lagi di Lark
            if (!empty($larkRecordIds)) {
                $deactivated = $modelClass::whereNotNull($uniqueKey)->whereNotIn($uniqueKey, $larkRecordIds)->whereNull('deleted_at')->get();

                foreach ($deactivated as $record) {
                    $record->delete(); // Soft delete (jika model pakai SoftDeletes)
                    $stats['deactivated']++;
                }

                if ($stats['deactivated'] > 0) {
                    Log::info('Deactivated records no longer in Lark', [
                        'service' => static::class,
                        'count' => $stats['deactivated'],
                    ]);
                }
            }

            DB::commit();

            Log::info('Lark sync completed', [
                'service' => static::class,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark sync failed', [
                'service' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $stats;
    }

    /**
     * Set base/table/view IDs (helper method untuk child class)
     *
     * @param string $baseId
     * @param string $tableId
     * @param string|null $viewId
     * @return self
     */
    protected function setLarkConfig(string $baseId, string $tableId, ?string $viewId = null): self
    {
        $this->baseId = $baseId;
        $this->tableId = $tableId;
        $this->viewId = $viewId;

        return $this;
    }

    /**
     * Set transformer instance
     *
     * @param mixed $transformer
     * @return self
     */
    protected function setTransformer($transformer): self
    {
        $this->transformer = $transformer;

        return $this;
    }
}
