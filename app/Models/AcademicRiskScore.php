<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRiskScore extends Model
{
    use BelongsToTenant;

    protected $table = 'academic_risk_scores';

    protected $fillable = [
        'tenant_id', 'estudiante_id', 'school_year_id',
        'score', 'nivel',
        'dim_academico', 'dim_asistencia', 'dim_disciplina', 'dim_tendencia',
        'materias_en_riesgo', 'total_materias', 'promedio_general', 'pct_asistencia',
        'tardanzas', 'faltas_leves', 'faltas_graves', 'suspensiones',
        'calculado_en',
    ];

    protected $casts = [
        'score'            => 'integer',
        'dim_academico'    => 'float',
        'dim_asistencia'   => 'float',
        'dim_disciplina'   => 'float',
        'dim_tendencia'    => 'float',
        'promedio_general' => 'float',
        'pct_asistencia'   => 'float',
        'calculado_en'     => 'datetime',
    ];

    // Configuración de niveles de riesgo
    const NIVELES = [
        'sin_riesgo' => ['label' => 'Sin Riesgo', 'color' => '#22c55e', 'bg' => '#f0fdf4', 'badge' => 'success',  'min' => 0,  'max' => 19 ],
        'bajo'       => ['label' => 'Bajo',        'color' => '#84cc16', 'bg' => '#f7fee7', 'badge' => 'lime',     'min' => 20, 'max' => 39 ],
        'moderado'   => ['label' => 'Moderado',    'color' => '#f59e0b', 'bg' => '#fffbeb', 'badge' => 'warning',  'min' => 40, 'max' => 59 ],
        'alto'       => ['label' => 'Alto',         'color' => '#f97316', 'bg' => '#fff7ed', 'badge' => 'orange',   'min' => 60, 'max' => 79 ],
        'critico'    => ['label' => 'Crítico',      'color' => '#ef4444', 'bg' => '#fef2f2', 'badge' => 'danger',   'min' => 80, 'max' => 100],
    ];

    public static function nivelDesdeScore(int $score): string
    {
        return match (true) {
            $score < 20 => 'sin_riesgo',
            $score < 40 => 'bajo',
            $score < 60 => 'moderado',
            $score < 80 => 'alto',
            default     => 'critico',
        };
    }

    public function getNivelConfigAttribute(): array
    {
        return static::NIVELES[$this->nivel] ?? static::NIVELES['sin_riesgo'];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
