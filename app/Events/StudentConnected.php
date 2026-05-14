<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentConnected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $docenteUserId,
        public int    $estudianteId,
        public string $estudianteNombre,
        public int    $claseId,
        public string $hora,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-docente.{$this->docenteUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'student.connected';
    }

    public function broadcastWith(): array
    {
        return [
            'estudiante_id'     => $this->estudianteId,
            'estudiante_nombre' => $this->estudianteNombre,
            'clase_id'          => $this->claseId,
            'hora'              => $this->hora,
        ];
    }
}
