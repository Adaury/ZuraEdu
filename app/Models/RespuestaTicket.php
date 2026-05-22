<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestaTicket extends Model
{
    use BelongsToTenant;

    protected $table = 'respuestas_ticket';

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'user_id',
        'mensaje',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TicketSoporte::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
