<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int     $userId,
        public string  $tipo,
        public string  $titulo,
        public string  $mensaje,
        public ?string $url   = null,
        public ?string $icono = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'tipo'    => $this->tipo,
            'titulo'  => $this->titulo,
            'mensaje' => $this->mensaje,
            'url'     => $this->url,
            'icono'   => $this->icono ?? 'bi-bell',
            'tiempo'  => now()->format('H:i'),
        ];
    }
}
