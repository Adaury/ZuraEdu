<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarnetIdentidad extends Model
{
    use BelongsToTenant;

    protected $table = 'carnet_identidades';

    protected $fillable = [
        'tipo', 'user_id', 'matricula_id',
        'numero_carnet', 'qr_token', 'estado', 'vigencia_hasta',
    ];

    protected $casts = [
        'vigencia_hasta' => 'date',
    ];

    const TIPOS = ['estudiante', 'docente', 'empleado'];
    const ESTADOS = ['activo', 'suspendido', 'vencido'];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(CarnetAcceso::class, 'carnet_identidad_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos($q)
    {
        return $q->where('estado', 'activo');
    }

    public function scopeEstudiantes($q)
    {
        return $q->where('tipo', 'estudiante');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getUltimoAccesoAttribute(): ?CarnetAcceso
    {
        return $this->accesos()->latest()->first();
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->user?->name ?? '—';
    }
}
