<?php
namespace App\Events;

use App\Models\MaterialRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MaterialRequestReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $materialRequest;

    public function __construct($materialRequest)
    {
        $this->materialRequest = $materialRequest->load('inventory', 'project', 'user.department');
    }

    public function broadcastOn()
    {
        return new Channel('material-requests');
    }

    public function broadcastWith()
    {
        return [
            'materialRequest' => $this->materialRequest->load('project', 'inventory'),
            'action' => 'reminder',
        ];
    }
}
