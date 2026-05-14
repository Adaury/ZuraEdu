<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsistenciaRegistrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $docenteUserId,
        public string $fecha,
        public int    $total,
        public int    $presentes,
        public string $asignaturaNombre,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-docente.{$this->docenteUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'asistencia.registrada';
    }

    public function broadcastWith(): array
    {
        return [
            'fecha'      => $this->fecha,
            'total'      => $this->total,
            'presentes'  => $this->presentes,
            'porcentaje' => $this->total > 0 ? round($this->presentes / $this->total * 100) : 0,
            'asignatura' => $this->asignaturaNombre,
        ];
    }
}
