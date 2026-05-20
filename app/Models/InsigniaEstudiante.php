<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsigniaEstudiante extends Model
{
    protected $table = 'insignias_estudiante';

    public const TIPOS = [
        'asistencia_perfecta' => [
            'label'       => 'Asistencia Perfecta',
            'descripcion' => 'Mantiene una asistencia del 95% o más.',
            'icono'       => 'bi-calendar-check-fill',
            'color'       => '#10b981',
            'bg'          => '#d1fae5',
        ],
        'top_estudiante' => [
            'label'       => 'Top Estudiante',
            'descripcion' => 'Promedio académico de 90 puntos o más.',
            'icono'       => 'bi-trophy-fill',
            'color'       => '#f59e0b',
            'bg'          => '#fef3c7',
        ],
        'mejora_continua' => [
            'label'       => 'Mejora Continua',
            'descripcion' => 'Supera su promedio de un período al siguiente.',
            'icono'       => 'bi-graph-up-arrow',
            'color'       => '#3b82f6',
            'bg'          => '#dbeafe',
        ],
        'cien_puntos' => [
            'label'       => '100 Puntos',
            'descripcion' => 'Acumula 100 puntos de gamificación.',
            'icono'       => 'bi-award-fill',
            'color'       => '#8b5cf6',
            'bg'          => '#ede9fe',
        ],
        'quinientos_puntos' => [
            'label'       => '500 Puntos',
            'descripcion' => 'Acumula 500 puntos de gamificación.',
            'icono'       => 'bi-gem',
            'color'       => '#ec4899',
            'bg'          => '#fce7f3',
        ],
        'sin_faltas' => [
            'label'       => 'Sin Faltas Disciplinarias',
            'descripcion' => 'No registra faltas disciplinarias en el año.',
            'icono'       => 'bi-shield-fill-check',
            'color'       => '#0ea5e9',
            'bg'          => '#e0f2fe',
        ],
    ];

    protected $fillable = [
        'matricula_id',
        'tipo',
        'fecha_obtencion',
    ];

    protected $casts = [
        'fecha_obtencion' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function infoTipo(): array
    {
        return self::TIPOS[$this->tipo] ?? [];
    }
}
