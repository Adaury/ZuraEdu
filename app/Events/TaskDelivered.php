<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDelivered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $docenteUserId,
        public int    $tareaId,
        public string $tareaTitulo,
        public string $estudianteNombre,
        public string $hora,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-docente.{$this->docenteUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'task.delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'tarea_id'          => $this->tareaId,
            'tarea_titulo'      => $this->tareaTitulo,
            'estudiante_nombre' => $this->estudianteNombre,
            'hora'              => $this->hora,
        ];
    }
}
