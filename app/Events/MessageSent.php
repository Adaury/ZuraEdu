<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $claseId,
        public int    $userId,
        public string $userName,
        public string $mensaje,
        public string $hora,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-classroom.{$this->claseId}")];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'clase_id'  => $this->claseId,
            'user_id'   => $this->userId,
            'user_name' => $this->userName,
            'mensaje'   => $this->mensaje,
            'hora'      => $this->hora,
        ];
    }
}
