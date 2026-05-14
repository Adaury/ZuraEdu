<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $tenantId,
        public string $tipo,
        public array  $datos = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tipo'  => $this->tipo,
            'datos' => $this->datos,
        ];
    }
}
