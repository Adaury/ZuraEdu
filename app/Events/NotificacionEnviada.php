<?php

namespace App\Events;

use App\Models\Notificacion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionEnviada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notificacion $notificacion) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-user.{$this->notificacion->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        $datos = $this->notificacion->datos ?? [];

        return [
            'id'      => $this->notificacion->id,
            'tipo'    => $this->notificacion->tipo,
            'titulo'  => $this->notificacion->titulo,
            'mensaje' => $this->notificacion->mensaje,
            'icono'   => Notificacion::ICONOS[$this->notificacion->tipo] ?? 'bi-bell',
            'tiempo'  => $this->notificacion->created_at?->diffForHumans() ?? 'ahora',
            'url'     => $datos['url'] ?? null,
        ];
    }
}
