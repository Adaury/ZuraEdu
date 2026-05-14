<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnuncioTenant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $tenantId,
        public string $titulo,
        public string $mensaje,
        public string $tipo = 'info',   // info | warning | danger | success
        public ?string $url = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-tenant.{$this->tenantId}.notifications")];
    }

    public function broadcastAs(): string
    {
        return 'anuncio';
    }

    public function broadcastWith(): array
    {
        return [
            'titulo'  => $this->titulo,
            'mensaje' => $this->mensaje,
            'tipo'    => $this->tipo,
            'url'     => $this->url,
            'tiempo'  => now()->format('H:i'),
        ];
    }
}
