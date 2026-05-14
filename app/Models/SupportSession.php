<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportSession extends Model
{
    protected $table = 'support_sessions';

    protected $fillable = [
        'tenant_id', 'token', 'visitor_nombre', 'visitor_email',
        'visitor_telefono', 'status', 'atendido_por', 'ultimo_mensaje_at',
    ];

    protected $casts = [
        'ultimo_mensaje_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeDelTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeAbiertas($query)
    {
        return $query->where('status', 'open');
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function mensajes()
    {
        return $this->hasMany(SupportMessage::class, 'session_id')->orderBy('created_at');
    }

    public function agente()
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public static function iniciar(int $tenantId, string $nombre, ?string $email, ?string $telefono): self
    {
        return static::create([
            'tenant_id'         => $tenantId,
            'token'             => Str::random(40),
            'visitor_nombre'    => $nombre,
            'visitor_email'     => $email,
            'visitor_telefono'  => $telefono,
            'status'            => 'open',
            'ultimo_mensaje_at' => now(),
        ]);
    }

    public function toCard(): array
    {
        return [
            'id'              => $this->id,
            'token'           => $this->token,
            'visitor_nombre'  => $this->visitor_nombre,
            'visitor_email'   => $this->visitor_email,
            'visitor_telefono'=> $this->visitor_telefono,
            'status'          => $this->status,
            'ultimo_mensaje'  => $this->ultimo_mensaje_at?->diffForHumans() ?? 'ahora',
            'sin_leer'        => $this->mensajes()->where('origen', 'visitor')->where('leido', false)->count(),
        ];
    }
}
