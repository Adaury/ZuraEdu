<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class HorarioDetalle extends Model
{
    protected $table    = 'sch_horario_detalles';
    protected $fillable = ['horario_id', 'asignacion_id', 'aula_id', 'franja_id', 'dia'];

    public function horario()    { return $this->belongsTo(Horario::class,    'horario_id'); }
    public function asignacion() { return $this->belongsTo(Asignacion::class, 'asignacion_id'); }
    public function aula()       { return $this->belongsTo(Aula::class,       'aula_id'); }
    public function franja()     { return $this->belongsTo(Franja::class,     'franja_id'); }
}
