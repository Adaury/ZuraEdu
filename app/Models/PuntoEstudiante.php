<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class PuntoEstudiante extends Model
{
    use BelongsToTenant;

    protected $table = 'puntos_estudiante';

    public const CATEGORIAS = [
        'academico'     => ['label' => 'Académico',     'color' => 'blue',   'icon' => 'bi-mortarboard-fill'],
        'asistencia'    => ['label' => 'Asistencia',    'color' => 'green',  'icon' => 'bi-calendar-check-fill'],
        'conducta'      => ['label' => 'Conducta',      'color' => 'purple', 'icon' => 'bi-shield-check'],
        'participacion' => ['label' => 'Participación', 'color' => 'orange', 'icon' => 'bi-hand-index-thumb-fill'],
        'extra'         => ['label' => 'Extra',         'color' => 'gray',   'icon' => 'bi-star-fill'],
    ];

    protected $fillable = [
        'matricula_id',
        'concepto',
        'categoria',
        'puntos',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeDeMatricula($q, int $matriculaId)
    {
        return $q->where('matricula_id', $matriculaId);
    }

    public function scopeDeCategoria($q, string $categoria)
    {
        return $q->where('categoria', $categoria);
    }
}
