<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use App\Models\MaterialRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MaterialRequestUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $materialRequest;
    public $action; // Tambahkan properti action

    public function __construct($materialRequest, string $action)
    {
        if (is_array($materialRequest) || $materialRequest instanceof \Illuminate\Support\Collection) {
            $this->materialRequest = collect($materialRequest)
                ->map(function ($mr) {
                    if (is_object($mr) && method_exists($mr, 'load')) {
                        return $mr->load('inventory', 'project', 'user.department');
                    }
                    return $mr;
                })
                ->values();
        } else {
            $this->materialRequest = is_object($materialRequest) && method_exists($materialRequest, 'load') ? $materialRequest->load('inventory', 'project', 'user.department') : $materialRequest;
        }
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('material-requests');
    }
}
