<?php

namespace App\Events;

use App\Models\ClassroomMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewClassroomMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ClassroomMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("private-classroom.{$this->message->clase_virtual_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-message';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'user_id'    => $this->message->user_id,
            'user_name'  => $this->message->user?->name ?? 'Usuario',
            'mensaje'    => $this->message->mensaje,
            'tipo'       => $this->message->tipo,
            'fijado'     => $this->message->fijado,
            'created_at' => $this->message->created_at->format('H:i'),
        ];
    }
}
