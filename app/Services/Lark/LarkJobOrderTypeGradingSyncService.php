<?php

namespace App\Services\Lark;

use App\DTO\LarkJobOrderTypeGradingDTO;
use App\Transformers\JobOrderTypeGradingTransformer;
use App\Models\Production\JobOrderTypeGrading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Job Order Type Grading Sync Service
 *
 * Orchestrator untuk sync Job Order Type Gradings dari Lark ke MySQL
 * Termasuk sync pivot department
 */
class LarkJobOrderTypeGradingSyncService
{
    private LarkApiClient $apiClient;
    private JobOrderTypeGradingTransformer $transformer;
    private string $appToken;
    private string $tableId;
    private ?string $viewId;

    public function __construct(LarkApiClient $apiClient, JobOrderTypeGradingTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        $this->appToken = config('lark.base_id');
        $this->tableId = config('lark.job_order_type_gradings.table_id');
        $this->viewId = config('lark.job_order_type_gradings.view_id');
    }

    /**
     * Main sync method
     *
     * @return array Sync statistics
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
            Log::info('Starting Lark job order type grading sync');

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId, 'name');

            $stats['fetched'] = count($rawRecords);

            Log::info('Fetched type grading records from Lark', [
                'count' => $stats['fetched'],
                'table_id' => $this->tableId,
                'view_id' => $this->viewId,
            ]);

            $larkRecordIds = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkJobOrderTypeGradingDTO($rawRecord);
                    $larkRecordIds[] = $dto->recordId;

                    $data = $this->transformer->transform($dto);

                    $this->transformer->validate($data);

                    // Extract department names sebelum save (bukan kolom database)
                    $departmentNames = $data['_department_names'] ?? [];
                    unset($data['_department_names']);

                    // Upsert ke database
                    $grading = JobOrderTypeGrading::updateOrCreate(
                        ['lark_record_id' => $dto->recordId],
                        array_merge($data, [
                            'last_sync_at' => now(),
                        ]),
                    );

                    // Sync pivot department
                    $departmentIds = $this->transformer->resolveDepartmentIds($departmentNames);
                    $grading->departments()->sync($departmentIds);

                    if ($grading->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Type Grading synced', [
                        'lark_record_id' => $dto->recordId,
                        'grading_id' => $grading->id,
                        'departments_count' => count($departmentIds),
                        'action' => $grading->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync type grading', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Soft delete gradings yang tidak ada lagi di Lark
            $deactivated = JobOrderTypeGrading::whereNotNull('lark_record_id')
                ->whereNotIn('lark_record_id', $larkRecordIds)
                ->whereNull('deleted_at')
                ->get();

            foreach ($deactivated as $grading) {
                $grading->delete();
                $stats['deactivated']++;

                Log::info('Type Grading deactivated (not in Lark)', [
                    'lark_record_id' => $grading->lark_record_id,
                    'grading_id' => $grading->id,
                ]);
            }

            DB::commit();

            Log::info('Lark type grading sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark type grading sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get raw response untuk debugging
     */
    public function getRawResponse(): array
    {
        return $this->apiClient->fetchRawResponse($this->appToken, $this->tableId, $this->viewId, 'name');
    }
}
