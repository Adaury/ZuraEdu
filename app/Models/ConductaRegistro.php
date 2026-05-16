<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ConductaRegistro extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'matricula_id', 'asignacion_id', 'periodo_id',
        'puntualidad', 'participacion', 'respeto',
        'trabajo_equipo', 'responsabilidad', 'orden',
        'observaciones',
    ];

    protected $casts = [
        'puntualidad'     => 'integer',
        'participacion'   => 'integer',
        'respeto'         => 'integer',
        'trabajo_equipo'  => 'integer',
        'responsabilidad' => 'integer',
        'orden'           => 'integer',
    ];

    public const INDICADORES = [
        'puntualidad'     => ['label' => 'Puntualidad',     'icon' => 'bi-clock-fill'],
        'participacion'   => ['label' => 'Participación',   'icon' => 'bi-hand-index-thumb-fill'],
        'respeto'         => ['label' => 'Respeto',         'icon' => 'bi-heart-fill'],
        'trabajo_equipo'  => ['label' => 'Trab. Equipo',    'icon' => 'bi-people-fill'],
        'responsabilidad' => ['label' => 'Responsabilidad', 'icon' => 'bi-check2-circle'],
        'orden'           => ['label' => 'Orden',           'icon' => 'bi-stars'],
    ];

    public const ESCALA = [
        5 => ['label' => 'E',  'nombre' => 'Excelente',  'color' => '#10b981', 'bg' => '#d1fae5'],
        4 => ['label' => 'MB', 'nombre' => 'Muy Bueno',  'color' => '#3b82f6', 'bg' => '#dbeafe'],
        3 => ['label' => 'B',  'nombre' => 'Bueno',      'color' => '#f59e0b', 'bg' => '#fef3c7'],
        2 => ['label' => 'R',  'nombre' => 'Regular',    'color' => '#f97316', 'bg' => '#ffedd5'],
        1 => ['label' => 'D',  'nombre' => 'Deficiente', 'color' => '#ef4444', 'bg' => '#fee2e2'],
    ];

    public function getPromedioAttribute(): ?float
    {
        $vals = array_filter([
            $this->puntualidad, $this->participacion, $this->respeto,
            $this->trabajo_equipo, $this->responsabilidad, $this->orden,
        ], fn($v) => $v !== null);
        return count($vals) ? round(array_sum($vals) / count($vals), 1) : null;
    }

    public function getConceptoAttribute(): ?int
    {
        $p = $this->promedio;
        if ($p === null) return null;
        if ($p >= 4.5) return 5;
        if ($p >= 3.5) return 4;
        if ($p >= 2.5) return 3;
        if ($p >= 1.5) return 2;
        return 1;
    }

    public function matricula()  { return $this->belongsTo(Matricula::class); }
    public function asignacion() { return $this->belongsTo(Asignacion::class); }
    public function periodo()    { return $this->belongsTo(Periodo::class); }
}
