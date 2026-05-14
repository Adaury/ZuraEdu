<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClassroomMeetingUpdated implements ShouldBroadcastNow
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
            new PrivateChannel("private-classroom.{$this->claseVirtualId}"),
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
