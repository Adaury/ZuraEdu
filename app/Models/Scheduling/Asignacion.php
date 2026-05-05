<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table    = 'sch_asignaciones';
    protected $fillable = ['materia_id', 'profesor_id', 'curso_id', 'horas_semana'];

    public function materia()  { return $this->belongsTo(Materia::class,  'materia_id'); }
    public function profesor() { return $this->belongsTo(Profesor::class, 'profesor_id'); }
    public function curso()    { return $this->belongsTo(Curso::class,    'curso_id'); }
}
