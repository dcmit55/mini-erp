<?php

namespace App\Services\Lark;

use App\DTO\LarkJobOrderDTO;
use App\Transformers\JobOrderTransformer;
use App\Models\Production\JobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Job Order Sync Service
 *
 * Main orchestrator untuk sync Job Orders dari Lark ke MySQL
 * Following iSyment pattern: Database transactions, comprehensive logging
 */
class LarkJobOrderSyncService
{
    private LarkApiClient $apiClient;
    private JobOrderTransformer $transformer;
    private string $appToken;
    private string $tableId;
    private ?string $viewId;

    public function __construct(LarkApiClient $apiClient, JobOrderTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        // Load from config
        $this->appToken = config('lark.base_id'); // Same base_id as projects
        $this->tableId = config('lark.job_orders.table_id');
        $this->viewId = config('lark.job_orders.view_id');
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
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Fetch raw data dari Lark
            Log::info('Starting Lark job order sync');

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

            $stats['fetched'] = count($rawRecords);

            Log::info('Fetched job order records from Lark', [
                'count' => $stats['fetched'],
                'table_id' => $this->tableId,
                'view_id' => $this->viewId,
            ]);

            // 2. Process each record
            $larkRecordIds = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    // Convert to DTO
                    $dto = new LarkJobOrderDTO($rawRecord);
                    $larkRecordIds[] = $dto->recordId;

                    // Transform to database format
                    $data = $this->transformer->transform($dto);

                    // Validate
                    $this->transformer->validate($data);

                    // Upsert to database with source tracking
                    $jobOrder = JobOrder::updateOrCreate(
                        ['lark_record_id' => $dto->recordId],
                        array_merge($data, [
                            'source_by' => 'Sync from Lark',
                            'last_sync_at' => now(),
                        ]),
                    );

                    if ($jobOrder->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Job Order synced', [
                        'lark_record_id' => $dto->recordId,
                        'job_order_id' => $jobOrder->id,
                        'action' => $jobOrder->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync job order', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // 3. Soft delete job orders yang tidak ada lagi di Lark
            $deactivated = JobOrder::whereNotNull('lark_record_id')->whereNotIn('lark_record_id', $larkRecordIds)->whereNull('deleted_at')->get();

            foreach ($deactivated as $jobOrder) {
                $jobOrder->delete();
                $stats['deactivated']++;

                Log::info('Job Order deactivated (not in Lark)', [
                    'lark_record_id' => $jobOrder->lark_record_id,
                    'job_order_id' => $jobOrder->id,
                ]);
            }

            DB::commit();

            Log::info('Lark job order sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark job order sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get raw response untuk debugging/audit
     */
    public function getRawResponse(): array
    {
        return $this->apiClient->fetchRawResponse($this->appToken, $this->tableId, $this->viewId);
    }

    /**
     * Sync single job order by lark_record_id
     */
    public function syncSingle(string $larkRecordId): JobOrder
    {
        // Fetch all and find specific record
        $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

        $targetRecord = collect($rawRecords)->firstWhere('record_id', $larkRecordId);

        if (!$targetRecord) {
            throw new \Exception("Job Order with lark_record_id {$larkRecordId} not found in Lark");
        }

        DB::beginTransaction();

        try {
            $dto = new LarkJobOrderDTO($targetRecord);
            $data = $this->transformer->transform($dto);
            $this->transformer->validate($data);

            $jobOrder = JobOrder::updateOrCreate(['lark_record_id' => $dto->recordId], $data);

            DB::commit();

            return $jobOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
