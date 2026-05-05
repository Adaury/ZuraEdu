<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class PlanificacionRaItem extends Model
{
    use BelongsToTenant;

    protected $table = 'planificacion_ra_items';

    protected $fillable = [
        'planificacion_id', 'orden',
        'ra_codigo', 'ra_descripcion', 'nivel_taxonomico',
        'elementos_capacidad', 'fechas',
        'actividades', 'instrumentos_evaluacion', 'contenidos',
    ];

    protected $casts = [
        'elementos_capacidad' => 'array',
        'fechas'              => 'array',
        'orden'               => 'integer',
    ];

    public function planificacion()
    {
        return $this->belongsTo(Planificacion::class);
    }
}
