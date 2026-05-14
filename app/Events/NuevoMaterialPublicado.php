<?php

namespace App\Events;

use App\Models\MaterialClase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoMaterialPublicado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MaterialClase $material) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-classroom.{$this->material->clase_virtual_id}")];
    }

    public function broadcastAs(): string
    {
        return 'material.nuevo';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->material->id,
            'tipo'     => $this->material->tipo,
            'titulo'   => $this->material->titulo,
            'clase_id' => $this->material->clase_virtual_id,
            'tiempo'   => $this->material->created_at?->diffForHumans() ?? 'ahora',
        ];
    }
}
