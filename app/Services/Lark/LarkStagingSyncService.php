<?php

namespace App\Services\Lark;

use App\DTO\LarkBtSgCourierDTO;
use App\DTO\LarkBtSgItemDTO;
use App\DTO\LarkSgBtCourierDTO;
use App\DTO\LarkSgBtItemDTO;
use App\Transformers\LarkBtSgCourierTransformer;
use App\Transformers\LarkBtSgItemTransformer;
use App\Transformers\LarkSgBtCourierTransformer;
use App\Transformers\LarkSgBtItemTransformer;
use App\Models\Lark\LarkBtSgCourierId;
use App\Models\Lark\LarkBtSgItemTracking;
use App\Models\Lark\LarkSgBtCourierId;
use App\Models\Lark\LarkSgBtItemTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Lark Staging Sync Service
 *
 * Syncs data from Lark to 4 staging tables
 * Following iSyment pattern: DTO -> Transformer -> Upsert
 */
class LarkStagingSyncService
{
    private LarkApiClient $apiClient;
    private string $appToken;
    private LarkBtSgCourierTransformer $btSgCourierTransformer;
    private LarkBtSgItemTransformer $btSgItemTransformer;
    private LarkSgBtCourierTransformer $sgBtCourierTransformer;
    private LarkSgBtItemTransformer $sgBtItemTransformer;

    public function __construct(LarkApiClient $apiClient, LarkBtSgCourierTransformer $btSgCourierTransformer, LarkBtSgItemTransformer $btSgItemTransformer, LarkSgBtCourierTransformer $sgBtCourierTransformer, LarkSgBtItemTransformer $sgBtItemTransformer)
    {
        $this->apiClient = $apiClient;
        $this->appToken = config('lark.base_id');
        $this->btSgCourierTransformer = $btSgCourierTransformer;
        $this->btSgItemTransformer = $btSgItemTransformer;
        $this->sgBtCourierTransformer = $sgBtCourierTransformer;
        $this->sgBtItemTransformer = $sgBtItemTransformer;
    }

    /**
     * Sync BT-SG Courier data
     */
    public function syncBtSgCourier(): array
    {
        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

        DB::beginTransaction();

        try {
            $tableId = config('lark.staging.bt_sg_courier.table_id');
            $viewId = config('lark.staging.bt_sg_courier.view_id');

            Log::info('Starting BT-SG Courier sync', ['table_id' => $tableId, 'view_id' => $viewId]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $tableId, $viewId);
            $stats['fetched'] = count($rawRecords);

            foreach ($rawRecords as $rawRecord) {
                try {
                    // DTO -> Extract raw data
                    $dto = new LarkBtSgCourierDTO($rawRecord);

                    // Transformer -> Normalize data
                    $data = $this->btSgCourierTransformer->transform($dto);

                    // Validate
                    $this->btSgCourierTransformer->validate($data);

                    // Upsert with name + date as unique key
                    $record = LarkBtSgCourierId::updateOrCreate(
                        [
                            'name' => $data['name'],
                            'date' => $data['date'],
                        ],
                        $data,
                    );

                    $record->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

                    Log::debug('BT-SG Courier synced', [
                        'lark_record_id' => $dto->recordId,
                        'name' => $data['name'],
                        'action' => $record->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync BT-SG Courier record', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();
            Log::info('BT-SG Courier sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BT-SG Courier sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sync BT-SG Item Tracking
     */
    public function syncBtSgItems(): array
    {
        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

        DB::beginTransaction();

        try {
            $tableId = config('lark.staging.bt_sg_items.table_id');
            $viewId = config('lark.staging.bt_sg_items.view_id');

            Log::info('Starting BT-SG Items sync', ['table_id' => $tableId, 'view_id' => $viewId]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $tableId, $viewId);
            $stats['fetched'] = count($rawRecords);

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkBtSgItemDTO($rawRecord);
                    $data = $this->btSgItemTransformer->transform($dto);
                    $this->btSgItemTransformer->validate($data);

                    $record = LarkBtSgItemTracking::updateOrCreate(
                        [
                            'item_name' => $data['item_name'],
                            'status' => $data['status'],
                        ],
                        $data,
                    );

                    $record->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to sync BT-SG Item record', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
            Log::info('BT-SG Items sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BT-SG Items sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sync SG-BT Courier data
     */
    public function syncSgBtCourier(): array
    {
        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

        DB::beginTransaction();

        try {
            $tableId = config('lark.staging.sg_bt_courier.table_id');
            $viewId = config('lark.staging.sg_bt_courier.view_id');

            Log::info('Starting SG-BT Courier sync', ['table_id' => $tableId, 'view_id' => $viewId]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $tableId, $viewId);
            $stats['fetched'] = count($rawRecords);

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkSgBtCourierDTO($rawRecord);
                    $data = $this->sgBtCourierTransformer->transform($dto);
                    $this->sgBtCourierTransformer->validate($data);

                    $record = LarkSgBtCourierId::updateOrCreate(
                        [
                            'name' => $data['name'],
                            'date' => $data['date'],
                        ],
                        $data,
                    );

                    $record->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to sync SG-BT Courier record', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
            Log::info('SG-BT Courier sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SG-BT Courier sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sync SG-BT Item Tracking
     */
    public function syncSgBtItems(): array
    {
        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

        DB::beginTransaction();

        try {
            $tableId = config('lark.staging.sg_bt_items.table_id');
            $viewId = config('lark.staging.sg_bt_items.view_id');

            Log::info('Starting SG-BT Items sync', ['table_id' => $tableId, 'view_id' => $viewId]);

            $rawRecords = $this->apiClient->fetchRecords($this->appToken, $tableId, $viewId);
            $stats['fetched'] = count($rawRecords);

            foreach ($rawRecords as $rawRecord) {
                try {
                    $dto = new LarkSgBtItemDTO($rawRecord);
                    $data = $this->sgBtItemTransformer->transform($dto);
                    $this->sgBtItemTransformer->validate($data);

                    $record = LarkSgBtItemTracking::updateOrCreate(
                        [
                            'item_name' => $data['item_name'],
                            'status' => $data['status'],
                        ],
                        $data,
                    );

                    $record->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = [
                        'record_id' => $rawRecord['record_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to sync SG-BT Item record', [
                        'record' => $rawRecord,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
            Log::info('SG-BT Items sync completed', $stats);

            return $stats;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SG-BT Items sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            // Try parsing common formats
            $date = Carbon::parse($dateStr);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', ['date_string' => $dateStr]);
            return null;
        }
    }
}
