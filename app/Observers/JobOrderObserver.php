<?php

namespace App\Observers;

use App\Models\Production\JobOrder;
use App\Services\AutoGoodsOutService;
use Illuminate\Support\Facades\Log;

/**
 * Job Order Observer
 *
 * Monitor changes to job orders
 * - Delivery date alerts: Handled by scheduled command (CheckDeliveryDateAlerts)
 * - Status change to "Delivered": Triggers auto goods-out service
 */
class JobOrderObserver
{
    protected AutoGoodsOutService $autoGoodsOutService;

    public function __construct(AutoGoodsOutService $autoGoodsOutService)
    {
        $this->autoGoodsOutService = $autoGoodsOutService;
    }

    /**
     * Handle the JobOrder "updated" event.
     *
     * @param JobOrder $jobOrder
     * @return void
     */
    public function updated(JobOrder $jobOrder)
    {
        // Delivery date alerts are handled by scheduler
        // This method can be used for other real-time triggers if needed

        // Log when delivery_date changes
        if ($jobOrder->wasChanged('delivery_date')) {
            Log::info('Job Order delivery date updated', [
                'job_order_id' => $jobOrder->id,
                'job_order_name' => $jobOrder->name,
                'old_delivery_date' => $jobOrder->getOriginal('delivery_date'),
                'new_delivery_date' => $jobOrder->delivery_date,
                'days_until_delivery' => $jobOrder->days_until_delivery,
            ]);
        }

        // CRITICAL BUSINESS LOGIC: Auto goods-out when status becomes "Delivered"
        // COMMENTED OUT - UNDER REVISION
        /*
        if ($jobOrder->wasChanged('status')) {
            $oldStatus = $jobOrder->getOriginal('status');
            $newStatus = $jobOrder->status;

            Log::info('Job Order status changed', [
                'job_order_id' => $jobOrder->id,
                'job_order_name' => $jobOrder->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Trigger auto goods-out if status changed to "Delivered"
            if ($newStatus && strtolower($newStatus) === 'delivered') {
                Log::info('Job Order marked as Delivered - triggering auto goods-out', [
                    'job_order_id' => $jobOrder->id,
                    'job_order_name' => $jobOrder->name,
                ]);

                $result = $this->autoGoodsOutService->processJobOrderDelivery($jobOrder);

                if ($result['success']) {
                    Log::info('Auto goods-out completed successfully', [
                        'job_order_id' => $jobOrder->id,
                        'processed_count' => $result['processed_count'],
                        'message' => $result['message'],
                    ]);
                } else {
                    Log::error('Auto goods-out failed', [
                        'job_order_id' => $jobOrder->id,
                        'message' => $result['message'],
                    ]);
                }
            }
        }
        */
    }

    /**
     * Handle the JobOrder "saved" event.
     * Fired after both create and update
     *
     * @param JobOrder $jobOrder
     * @return void
     */
    public function saved(JobOrder $jobOrder)
    {
        // Additional logic when job order is saved
        // Can be used for audit logging or other side effects
    }
}
