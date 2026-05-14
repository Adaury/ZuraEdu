<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportAdminReply implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $token,
        public string $adminNombre,
        public string $mensaje,
        public string $hora,
    ) {}

    // Canal público — el visitante no está autenticado
    public function broadcastOn(): array
    {
        return [new Channel("support.{$this->token}")];
    }

    public function broadcastAs(): string
    {
        return 'admin.reply';
    }

    public function broadcastWith(): array
    {
        return [
            'admin_nombre' => $this->adminNombre,
            'mensaje'      => $this->mensaje,
            'hora'         => $this->hora,
        ];
    }
}
