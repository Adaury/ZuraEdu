<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaRecurso extends Model
{
    use BelongsToTenant;

    protected $table = 'reservas_recurso';

    protected $fillable = [
        'recurso_id',
        'solicitante_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoFisico::class, 'recurso_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado) {
            'aprobada'  => ['label' => 'Aprobada',  'color' => 'green'],
            'rechazada' => ['label' => 'Rechazada', 'color' => 'red'],
            default     => ['label' => 'Pendiente', 'color' => 'yellow'],
        };
    }

    public function getDuracionAttribute(): string
    {
        $inicio = \Carbon\Carbon::parse($this->hora_inicio);
        $fin    = \Carbon\Carbon::parse($this->hora_fin);
        $mins   = $inicio->diffInMinutes($fin);

        return $mins >= 60
            ? floor($mins / 60) . 'h ' . ($mins % 60 ? ($mins % 60) . 'min' : '')
            : $mins . ' min';
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }
}
