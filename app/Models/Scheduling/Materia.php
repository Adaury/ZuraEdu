<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table    = 'sch_materias';
    protected $fillable = ['nombre', 'horas_semana', 'color'];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'materia_id');
    }
}
