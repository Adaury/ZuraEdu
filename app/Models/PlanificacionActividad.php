<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class PlanificacionActividad extends Model
{
    use BelongsToTenant;

    protected $table = 'planificacion_actividades';

    protected $fillable = [
        'planificacion_id',
        'ra_codigo', 'ra_descripcion',
        'actividad_numero', 'objetivo',
        'act_inicio', 'act_desarrollo', 'act_cierre',
        'estrategias', 'recursos', 'instrumentos_evaluacion',
    ];

    protected $casts = [
        'actividad_numero' => 'integer',
    ];

    public function planificacion()
    {
        return $this->belongsTo(Planificacion::class);
    }
}
