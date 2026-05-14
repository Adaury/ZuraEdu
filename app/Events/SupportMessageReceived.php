<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $tenantId,
        public int    $sessionId,
        public string $token,
        public string $visitorNombre,
        public string $mensaje,
        public string $hora,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-tenant.{$this->tenantId}.support")];
    }

    public function broadcastAs(): string
    {
        return 'support.message';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'     => $this->sessionId,
            'token'          => $this->token,
            'visitor_nombre' => $this->visitorNombre,
            'mensaje'        => $this->mensaje,
            'hora'           => $this->hora,
        ];
    }
}
