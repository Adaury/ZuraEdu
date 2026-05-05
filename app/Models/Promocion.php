<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'matricula_id', 'school_year_id', 'estado',
        'promedio_final', 'pct_asistencia',
        'materias_reprobadas', 'materias_reprobadas_detalle',
        'observacion', 'decidido_por', 'fecha_decision',
    ];

    protected $casts = [
        'promedio_final'              => 'float',
        'pct_asistencia'              => 'float',
        'materias_reprobadas'         => 'integer',
        'materias_reprobadas_detalle' => 'array',
        'fecha_decision'              => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function matricula(): BelongsTo   { return $this->belongsTo(Matricula::class); }
    public function schoolYear(): BelongsTo  { return $this->belongsTo(SchoolYear::class); }
    public function decidido(): BelongsTo    { return $this->belongsTo(User::class, 'decidido_por'); }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'promovido'    => 'Promovido',
            'no_promovido' => 'No Promovido',
            'condicionado' => 'Condicionado',
            default        => 'Pendiente',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'promovido'    => '#d1fae5',
            'no_promovido' => '#fee2e2',
            'condicionado' => '#fef3c7',
            default        => '#f3f4f6',
        };
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'promovido'    => 'success',
            'no_promovido' => 'danger',
            'condicionado' => 'warning',
            default        => 'secondary',
        };
    }

    public function scopePromovidos($query)  { return $query->where('estado', 'promovido'); }
    public function scopePendientes($query)  { return $query->where('estado', 'pendiente'); }
}
