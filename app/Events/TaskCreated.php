<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int     $claseId,
        public int     $tareaId,
        public string  $titulo,
        public string  $tipo,
        public ?string $fechaEntrega,
        public string  $docenteNombre,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-classroom.{$this->claseId}")];
    }

    public function broadcastAs(): string
    {
        return 'task.created';
    }

    public function broadcastWith(): array
    {
        return [
            'clase_id'       => $this->claseId,
            'tarea_id'       => $this->tareaId,
            'titulo'         => $this->titulo,
            'tipo'           => $this->tipo,
            'fecha_entrega'  => $this->fechaEntrega,
            'docente_nombre' => $this->docenteNombre,
        ];
    }
}
