<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EstudianteEscaneadoQr implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $docenteUserId,
        public int    $asignacionId,
        public string $nombreEstudiante,
        public string $hora,
        public int    $totalPresentes,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-docente.{$this->docenteUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'qr.escaneado';
    }

    public function broadcastWith(): array
    {
        return [
            'asignacion_id'   => $this->asignacionId,
            'nombre'          => $this->nombreEstudiante,
            'hora'            => $this->hora,
            'total_presentes' => $this->totalPresentes,
        ];
    }
}
