<?php

namespace App\Events;

use App\Models\Production\JobOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Job Order Countdown Alert Event
 *
 * STUB FOR FUTURE IMPLEMENTATION
 *
 * This event will be triggered when a job order's countdown reaches critical level (1 day)
 * Currently prepared but not actively broadcasting
 *
 * TO ACTIVATE:
 * 1. Uncomment ShouldBroadcast implementation
 * 2. Configure Pusher credentials in .env
 * 3. Trigger from JobOrderObserver when countdown_days = 1
 * 4. Add frontend listener in layouts/app.blade.php
 *
 * CHANNEL STRATEGY:
 * Option A: Broadcast to specific departments
 *   - Channel: department.{id}.job-order-alerts
 *   - Pros: Targeted, reduces noise
 *   - Cons: Need to broadcast to multiple channels
 *
 * Option B: Broadcast to global admin channel
 *   - Channel: job-order-alerts
 *   - Pros: Simple, single broadcast
 *   - Cons: All admins receive all notifications
 *
 * RECOMMENDED: Option A (department-specific)
 */
class JobOrderCountdownAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public JobOrder $jobOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(JobOrder $jobOrder)
    {
        $this->jobOrder = $jobOrder;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to primary department
        if ($this->jobOrder->department_id) {
            $channels[] = new Channel("department.{$this->jobOrder->department_id}.job-order-alerts");
        }

        // Broadcast to additional departments
        foreach ($this->jobOrder->departments as $department) {
            $channels[] = new Channel("department.{$department->id}.job-order-alerts");
        }

        // Fallback: global admin channel if no departments
        if (empty($channels)) {
            $channels[] = new Channel('job-order-alerts');
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $daysUntil = $this->jobOrder->days_until_delivery ?? 0;

        return [
            'job_order_id' => $this->jobOrder->id,
            'job_order_name' => $this->jobOrder->name,
            'delivery_date' => $this->jobOrder->delivery_date?->format('Y-m-d'),
            'days_until_delivery' => $daysUntil,
            'delivery_display' => $this->jobOrder->delivery_display,
            'project_name' => $this->jobOrder->project?->name,
            'primary_department' => $this->jobOrder->department?->name,
            'departments' => $this->jobOrder->departments->pluck('name')->toArray(),
            'message' => "Job Order '{$this->jobOrder->name}' delivery is approaching: {$this->jobOrder->delivery_display}",
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'job-order.delivery-alert';
    }
}
