<?php

namespace App\Events;

use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarnetEscaneado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly string $nombrePersona,
        public readonly string $numero_carnet,
        public readonly string $tipoEvento,
        public readonly string $estado,
        public readonly string $hora,
        public readonly ?string $foto = null,
        public readonly ?string $grupo = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("carnet.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'carnet.escaneado';
    }

    public function broadcastWith(): array
    {
        return [
            'nombre'       => $this->nombrePersona,
            'numero_carnet'=> $this->numero_carnet,
            'tipo_evento'  => $this->tipoEvento,
            'estado'       => $this->estado,
            'hora'         => $this->hora,
            'foto'         => $this->foto,
            'grupo'        => $this->grupo,
        ];
    }
}
