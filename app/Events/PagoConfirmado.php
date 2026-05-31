<?php

namespace App\Events;

use App\Models\Pago;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoConfirmado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Pago $pago) {}
}
