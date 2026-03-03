<?php

namespace App\Events;

use App\Models\Admin\FeatureAnnouncement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewFeatureAnnouncement implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $announcement;
    public $targetUserIds;

    /**
     * Create a new event instance.
     */
    public function __construct(FeatureAnnouncement $announcement, array $targetUserIds = [])
    {
        $this->announcement = $announcement;
        $this->targetUserIds = $targetUserIds;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // Broadcast ke channel umum feature-announcements
        return new Channel('feature-announcements');
    }

    /**
     * Data yang akan di-broadcast
     */
    public function broadcastWith()
    {
        return [
            'announcement' => [
                'id' => $this->announcement->id,
                'title' => $this->announcement->title,
                'description' => $this->announcement->description,
                'version' => $this->announcement->version,
                'priority' => $this->announcement->priority,
            ],
            'target_user_ids' => $this->targetUserIds,
        ];
    }
}
