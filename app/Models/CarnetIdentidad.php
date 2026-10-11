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

    /**
     * ¿Puede este carnet abrir la puerta hoy? Además de estado = activo exige:
     * (1) vigencia_hasta no vencida (antes el campo se imprimía en el carnet
     * pero nunca se validaba al escanear) y (2) que el estudiante no figure
     * retirado/transferido en su matrícula más reciente (un alumno dado de
     * baja seguía entrando con su QR permanente).
     */
    public function estaHabilitado(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        if ($this->vigencia_hasta && $this->vigencia_hasta->lt(today())) {
            return false;
        }

        if ($this->tipo === 'estudiante' && $this->matricula?->estudiante_id) {
            $ultima = Matricula::where('estudiante_id', $this->matricula->estudiante_id)
                ->orderByDesc('school_year_id')
                ->orderByDesc('id')
                ->first();

            if ($ultima && in_array($ultima->estado, ['retirada', 'transferida'], true)) {
                return false;
            }
        }

        return true;
    }

    public function getUltimoAccesoAttribute(): ?CarnetAcceso
    {
        return $this->accesos()->latest()->first();
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->user?->name ?? '—';
    }
}
