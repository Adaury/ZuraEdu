<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $table    = 'sch_cursos';
    protected $fillable = ['nombre', 'grado', 'seccion', 'capacidad'];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'curso_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->seccion ? "{$this->grado} — {$this->seccion}" : $this->grado;
    }
}
