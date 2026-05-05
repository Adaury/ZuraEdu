<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClassroomMeetingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $claseVirtualId,
        public string $status,
        public ?string $meetingUrl,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("classroom.{$this->claseVirtualId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'meeting-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'status'      => $this->status,
            'meeting_url' => $this->meetingUrl,
        ];
    }
}
