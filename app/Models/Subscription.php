<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id', 'plan_id', 'estado',
        'fecha_inicio', 'fecha_fin',
        'monto_pagado', 'moneda', 'ciclo',
        'metodo_pago', 'referencia_pago', 'metadatos',
        'stripe_session_id', 'stripe_payment_intent',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'monto_pagado' => 'float',
        'metadatos'    => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function estaActiva(): bool
    {
        return in_array($this->estado, ['activa', 'prueba'])
            && $this->fecha_fin->isFuture();
    }

    public function diasRestantes(): int
    {
        return max(0, (int) now()->diffInDays($this->fecha_fin, false));
    }

    // ── Query scopes ──────────────────────────────────────────────────────

    public function scopeActivas($q)
    {
        return $q->whereIn('estado', ['activa', 'prueba'])
                 ->where('fecha_fin', '>=', now()->toDateString());
    }

    public function scopeVencidas($q)
    {
        return $q->where('fecha_fin', '<', now()->toDateString())
                 ->whereNotIn('estado', ['cancelada']);
    }
}
