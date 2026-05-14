<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalificacionesPublicadas implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'notifications';

    public function __construct(
        public int    $grupoId,
        public string $periodo,
        public string $asignatura,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-grupo.{$this->grupoId}")];
    }

    public function broadcastAs(): string
    {
        return 'calificaciones.publicadas';
    }

    public function broadcastWith(): array
    {
        return [
            'periodo'    => $this->periodo,
            'asignatura' => $this->asignatura,
            'mensaje'    => "Calificaciones de {$this->asignatura} publicadas — {$this->periodo}",
        ];
    }
}
