<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PlanifUnidad extends Model
{
    use BelongsToTenant;

    protected $table    = 'planif_unidades';
    protected $fillable = [
        'tenant_id', 'planif_anual_id', 'numero', 'titulo', 'periodo', 'semanas',
        'objetivos', 'competencias', 'indicadores', 'contenidos',
        'estrategias', 'recursos', 'evaluacion',
        'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'competencias'  => 'array',
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
        'semanas'       => 'integer',
        'numero'        => 'integer',
    ];

    public const COMPETENCIAS = [
        'Comunicativa',
        'Pensamiento Lógico y Resolución de Problemas',
        'Científica y Tecnológica',
        'Ética y Ciudadana',
        'Ambiental y de la Salud',
        'Desarrollo Personal y Espiritual',
    ];

    public function planifAnual() { return $this->belongsTo(PlanifAnual::class); }
}
