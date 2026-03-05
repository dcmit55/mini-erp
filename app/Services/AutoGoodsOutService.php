<?php

namespace App\Services;

use App\Models\Production\JobOrder;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\Inventory;
use App\Helpers\MaterialUsageHelper;
use App\Events\GoodsOutProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for automating goods-out process when job order status changes to "Delivered"
 *
 * Business Logic:
 * - When job_orders.status becomes "Delivered"
 * - All related material requests with status "approved"
 * - Auto-create goods_out records for remaining quantities
 * - Update material_request.status to "delivered"
 * - Reduce inventory quantities
 * - Trigger Pusher notifications
 *
 * Architecture Pattern: Service class for complex business logic
 * Transaction Safety: Uses DB::transaction with proper rollback
 * Idempotency: Checks current status before processing
 */
class AutoGoodsOutService
{
    /**
     * Process auto goods-out for a job order when status becomes "Delivered"
     *
     * @param JobOrder $jobOrder
     * @return array Result with success status, message, and processed count
     */
    public function processJobOrderDelivery(JobOrder $jobOrder): array
    {
        // Guard: Check if job order is actually delivered
        if (!$jobOrder->isDelivered()) {
            return [
                'success' => false,
                'message' => 'Job order status is not "Delivered"',
                'processed_count' => 0,
            ];
        }

        Log::info('AutoGoodsOut: Starting process for job order', [
            'job_order_id' => $jobOrder->id,
            'job_order_name' => $jobOrder->name,
            'status' => $jobOrder->status,
        ]);

        DB::beginTransaction();
        try {
            // Get all approved material requests for this job order
            // Eager load relationships to avoid N+1 queries
            $materialRequests = MaterialRequest::where('job_order_id', $jobOrder->id)
                ->where('status', 'approved')
                ->with(['inventory', 'project', 'user.department'])
                ->lockForUpdate() // Lock for transaction safety
                ->get();

            if ($materialRequests->isEmpty()) {
                DB::rollBack();
                Log::info('AutoGoodsOut: No approved material requests found', [
                    'job_order_id' => $jobOrder->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'No approved material requests to process',
                    'processed_count' => 0,
                ];
            }

            $processedCount = 0;
            $createdGoodsOuts = [];

            foreach ($materialRequests as $materialRequest) {
                // Calculate remaining quantity to goods-out
                $remainingQty = $materialRequest->qty - $materialRequest->processed_qty;

                // Skip if already fully processed
                if ($remainingQty <= 0) {
                    Log::info('AutoGoodsOut: Material request already fully processed', [
                        'material_request_id' => $materialRequest->id,
                        'material_name' => $materialRequest->inventory->name ?? 'Unknown',
                    ]);
                    continue;
                }

                // Lock inventory row
                $inventory = Inventory::where('id', $materialRequest->inventory_id)->lockForUpdate()->first();

                if (!$inventory) {
                    Log::warning('AutoGoodsOut: Inventory not found', [
                        'material_request_id' => $materialRequest->id,
                        'inventory_id' => $materialRequest->inventory_id,
                    ]);
                    continue;
                }

                // Check inventory stock availability
                if ($remainingQty > $inventory->quantity) {
                    Log::warning('AutoGoodsOut: Insufficient inventory stock', [
                        'material_request_id' => $materialRequest->id,
                        'material_name' => $inventory->name,
                        'remaining_qty' => $remainingQty,
                        'available_stock' => $inventory->quantity,
                    ]);

                    // DECISION: Skip this material instead of failing entire process
                    // Allows partial processing for available materials
                    continue;
                }

                // Create goods out record
                $goodsOut = GoodsOut::create([
                    'material_request_id' => $materialRequest->id,
                    'inventory_id' => $inventory->id,
                    'project_id' => $materialRequest->project_id,
                    'job_order_id' => $materialRequest->job_order_id,
                    'requested_by' => $materialRequest->requested_by,
                    'quantity' => $remainingQty,
                    'remark' => "Auto Goods Out - Job Order Delivered: {$jobOrder->name}",
                ]);

                // Reduce inventory stock
                $inventory->quantity -= $remainingQty;
                $inventory->save();

                // Update material request status and processed quantity
                Log::info('AutoGoodsOut: Before updating MaterialRequest', [
                    'material_request_id' => $materialRequest->id,
                    'current_status' => $materialRequest->status,
                    'current_processed_qty' => $materialRequest->processed_qty,
                    'remaining_qty' => $remainingQty,
                ]);

                $materialRequest->processed_qty = $materialRequest->processed_qty + $remainingQty;
                $materialRequest->status = 'delivered';
                $saved = $materialRequest->save();

                Log::info('AutoGoodsOut: After updating MaterialRequest', [
                    'material_request_id' => $materialRequest->id,
                    'save_result' => $saved,
                    'new_status' => $materialRequest->status,
                    'new_processed_qty' => $materialRequest->processed_qty,
                    'is_dirty' => $materialRequest->isDirty(),
                    'dirty_attributes' => $materialRequest->getDirty(),
                ]);

                // Sync material usage helper
                MaterialUsageHelper::sync($inventory->id, $materialRequest->project_id, $materialRequest->job_order_id);

                $createdGoodsOuts[] = $goodsOut;
                $processedCount++;

                Log::info('AutoGoodsOut: Material processed successfully', [
                    'goods_out_id' => $goodsOut->id,
                    'material_request_id' => $materialRequest->id,
                    'material_name' => $inventory->name,
                    'quantity' => $remainingQty,
                ]);
            }

            DB::commit();

            // Trigger Pusher notifications AFTER successful commit
            foreach ($createdGoodsOuts as $goodsOut) {
                try {
                    event(new GoodsOutProcessed($goodsOut));
                } catch (\Exception $e) {
                    // Log notification errors but don't fail the process
                    Log::error('AutoGoodsOut: Failed to send notification', [
                        'goods_out_id' => $goodsOut->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('AutoGoodsOut: Process completed successfully', [
                'job_order_id' => $jobOrder->id,
                'processed_count' => $processedCount,
                'total_material_requests' => $materialRequests->count(),
            ]);

            return [
                'success' => true,
                'message' => "Successfully processed {$processedCount} material(s) for goods-out",
                'processed_count' => $processedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('AutoGoodsOut: Transaction failed', [
                'job_order_id' => $jobOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process auto goods-out: ' . $e->getMessage(),
                'processed_count' => 0,
            ];
        }
    }
}
