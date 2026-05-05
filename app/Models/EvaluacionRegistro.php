<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluacionRegistro extends Model
{
    use BelongsToTenant;

    protected $table = 'evaluaciones_registro';

    protected $fillable = [
        'matricula_id',
        'asignacion_id',
        'periodo_id',
        'school_year_id',
        'competencia_id',
        'indicador_id',
        'valor_cualitativo',
        'nota_numerica',
        'registrado_por',
    ];

    protected $casts = [
        'valor_cualitativo' => 'integer',
        'nota_numerica'     => 'float',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(CompetenciaEspecifica::class, 'competencia_id');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(IndicadorLogro::class, 'indicador_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Devuelve el valor efectivo según el ciclo:
     * valor_cualitativo (1–4) para primer ciclo,
     * nota_numerica (0–100) para segundo ciclo.
     */
    public function getValorEfectivoAttribute(): float|int|null
    {
        return $this->nota_numerica ?? $this->valor_cualitativo;
    }

    public function getLabelCualitativoAttribute(): string
    {
        return $this->valor_cualitativo
            ? IndicadorLogro::labelCualitativo($this->valor_cualitativo)
            : '—';
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeDeMatricula($query, int $matriculaId)
    {
        return $query->where('matricula_id', $matriculaId);
    }

    public function scopeDelPeriodo($query, int $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }

    public function scopeDeAsignacion($query, int $asignacionId)
    {
        return $query->where('asignacion_id', $asignacionId);
    }
}
