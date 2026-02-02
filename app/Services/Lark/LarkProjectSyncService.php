<?php

namespace App\Services\Lark;

use App\DTO\LarkProjectDTO;
use App\Models\Production\Project;
use App\Transformers\ProjectTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Project Sync Service
 *
 * ORCHESTRATOR utama untuk sinkronisasi Lark → MySQL
 * Following iSyment pattern:
 * - Database transactions
 * - Event broadcasting
 * - Error handling & logging
 *
 * FLOW:
 * 1. Fetch data dari Lark API
 * 2. Convert ke DTO
 * 3. Transform ke database format
 * 4. Upsert ke database
 * 5. Soft delete records yang tidak ada lagi
 */
class LarkProjectSyncService
{
    private LarkApiClient $apiClient;
    private ProjectTransformer $transformer;

    // Lark credentials dari .env
    private string $appToken;
    private string $tableId;
    private ?string $viewId;

    public function __construct(LarkApiClient $apiClient, ProjectTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;

        // Load from config
        $this->appToken = config('services.lark.base_id');
        $this->tableId = config('services.lark.table_id');
        $this->viewId = config('services.lark.view_id');
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
            Log::info('Starting Lark project sync');

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

            $stats['fetched'] = count($rawRecords);

            // 2. Process each record
            $larkRecordIds = [];

            foreach ($rawRecords as $rawRecord) {
                try {
                    // Convert to DTO
                    $dto = new LarkProjectDTO($rawRecord);
                    $larkRecordIds[] = $dto->recordId;

                    // Transform to database format
                    $data = $this->transformer->transform($dto);

                    // Validate
                    $this->transformer->validate($data);

                    // Upsert to database
                    $project = Project::updateOrCreate(['lark_record_id' => $dto->recordId], $data);

                    if ($project->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    Log::debug('Project synced', [
                        'lark_record_id' => $dto->recordId,
                        'project_id' => $project->id,
                        'action' => $project->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync project', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. Soft delete projects yang tidak ada lagi di Lark
            $deactivated = Project::whereNotNull('lark_record_id')->whereNotIn('lark_record_id', $larkRecordIds)->whereNull('deleted_at')->get();

            foreach ($deactivated as $project) {
                $project->delete(); // Soft delete
                $stats['deactivated']++;

                Log::info('Project deactivated (removed from Lark)', [
                    'project_id' => $project->id,
                    'lark_record_id' => $project->lark_record_id,
                    'name' => $project->name,
                ]);
            }

            DB::commit();

            Log::info('Lark project sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark project sync failed', [
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
     * Sync single project by lark_record_id
     */
    public function syncSingle(string $larkRecordId): Project
    {
        // Fetch all and find specific record
        $rawRecords = $this->apiClient->fetchRecords($this->appToken, $this->tableId, $this->viewId);

        $rawRecord = collect($rawRecords)->firstWhere('record_id', $larkRecordId);

        if (!$rawRecord) {
            throw new \Exception("Record {$larkRecordId} not found in Lark");
        }

        DB::beginTransaction();

        try {
            $dto = new LarkProjectDTO($rawRecord);
            $data = $this->transformer->transform($dto);
            $this->transformer->validate($data);

            $project = Project::updateOrCreate(
                ['lark_record_id' => $larkRecordId],
                array_merge($data, [
                    'created_by' => auth()->user()->name ?? 'Lark Sync',
                ]),
            );

            DB::commit();

            return $project;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
