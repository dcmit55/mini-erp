<?php

namespace App\Events;

use App\Models\Logistic\GoodsOut;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

/**
 * Event triggered when goods out is processed from a material request
 * Broadcasts notification to relevant departments and admins
 */
class GoodsOutProcessed implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $goodsOutId;
    public $materialName;
    public $quantity;
    public $unit;
    public $projectName;
    public $jobOrderName;
    public $requestedByName;
    public $departmentId;
    public $departmentName;
    public $message;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param GoodsOut $goodsOut
     */
    public function __construct(GoodsOut $goodsOut)
    {
        // Eager load relationships to avoid N+1
        $goodsOut->load(['inventory:id,name,unit', 'project:id,name', 'jobOrder:id,name', 'materialRequest.user:id,first_name,last_name', 'materialRequest.user.department:id,name']);

        $this->goodsOutId = $goodsOut->id;
        $this->materialName = $goodsOut->inventory->name ?? 'Unknown Material';
        $this->quantity = $goodsOut->quantity;
        $this->unit = $goodsOut->inventory->unit ?? 'pcs';
        $this->projectName = $goodsOut->project->name ?? 'Unknown Project';
        $this->jobOrderName = $goodsOut->jobOrder->name ?? null;

        // Requested by info
        $user = $goodsOut->materialRequest?->user;
        $this->requestedByName = $user ? trim("{$user->first_name} {$user->last_name}") : 'System';

        $this->departmentId = $user?->department?->id;
        $this->departmentName = $user?->department?->name ?? 'Unknown Department';

        // Format message
        $this->message = sprintf('Material %s (%s %s) has been issued to %s%s', $this->materialName, number_format($this->quantity, 2), $this->unit, $this->projectName, $this->jobOrderName ? " - {$this->jobOrderName}" : '');

        $this->timestamp = now()->toDateTimeString();

        Log::info('GoodsOutProcessed event created', [
            'goods_out_id' => $this->goodsOutId,
            'material' => $this->materialName,
            'quantity' => $this->quantity,
            'project' => $this->projectName,
            'department_id' => $this->departmentId,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to:
     * 1. Department-specific channel (for department members)
     * 2. Global goods-out channel (for all admins)
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('goods-out-alerts'), // Global admin channel
        ];

        // Add department-specific channel if available
        if ($this->departmentId) {
            $channels[] = new Channel("department.{$this->departmentId}.goods-out-alerts");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'goods-out.processed';
    }

    /**
     * Get the data to broadcast.
     * This is what frontend receives in the event payload
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'goods_out_id' => $this->goodsOutId,
            'material_name' => $this->materialName,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'project_name' => $this->projectName,
            'job_order_name' => $this->jobOrderName,
            'requested_by' => $this->requestedByName,
            'department_id' => $this->departmentId,
            'department_name' => $this->departmentName,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
            'url' => route('goods_out.index'), // Redirect URL
        ];
    }
}
