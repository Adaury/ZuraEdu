<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetenciaEspecifica extends Model
{
    protected $table = 'competencias_especificas';

    protected $fillable = [
        'asignatura_id', 'ciclo', 'codigo', 'nombre', 'descripcion', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function indicadores(): HasMany
    {
        return $this->hasMany(IndicadorLogro::class, 'competencia_id')->orderBy('orden');
    }

    public function indicadoresActivos(): HasMany
    {
        return $this->hasMany(IndicadorLogro::class, 'competencia_id')
                    ->where('activo', true)
                    ->orderBy('orden');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionRegistro::class, 'competencia_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopePrimerCiclo($query)
    {
        return $query->where('ciclo', 'primer_ciclo');
    }

    public function scopeSegundoCiclo($query)
    {
        return $query->where('ciclo', 'segundo_ciclo');
    }

    public function scopeDelCiclo($query, string $ciclo)
    {
        return $query->where('ciclo', $ciclo);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getCicloLabelAttribute(): string
    {
        return $this->ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo';
    }
}
