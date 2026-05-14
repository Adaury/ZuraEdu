<?php

namespace App\Events;

use App\Models\TenantChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoMensajeTenantChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TenantChatMessage $mensaje) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("private-tenant.{$this->mensaje->tenant_id}.chat")];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return $this->mensaje->toChat();
    }
}
