<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEvaluacionPeriodo extends Model
{
    use BelongsToTenant;

    protected $table = 'plan_evaluacion_periodos';

    protected $fillable = [
        'tenant_id', 'asignacion_id', 'periodo_id',
        'tareas', 'practicas', 'participacion', 'proyecto', 'examen',
        'observaciones', 'publicado',
    ];

    protected $casts = ['publicado' => 'boolean'];

    public static array $categorias = [
        'tareas'        => ['label' => 'Tareas / Trabajos', 'icon' => 'bi-pencil',           'color' => '#3b82f6'],
        'practicas'     => ['label' => 'Prácticas',         'icon' => 'bi-tools',             'color' => '#10b981'],
        'participacion' => ['label' => 'Participación',     'icon' => 'bi-hand-index-thumb',  'color' => '#f59e0b'],
        'proyecto'      => ['label' => 'Proyecto',          'icon' => 'bi-kanban',            'color' => '#8b5cf6'],
        'examen'        => ['label' => 'Examen / Prueba',   'icon' => 'bi-journal-check',     'color' => '#ef4444'],
    ];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function getTotalAttribute(): int
    {
        return $this->tareas + $this->practicas + $this->participacion + $this->proyecto + $this->examen;
    }
}
