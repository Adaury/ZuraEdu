<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = [
        'school_year_id',
        'grupo_id',
        'asignatura_id',
        'docente_id',
        'activo',
        'area',
        'tipo_evaluacion',
        'horas_semana',
        'pesos_ra',
    ];

    protected $casts = [
        'activo'   => 'boolean',
        'pesos_ra' => 'array',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function calificacionesAcademicas()
    {
        return $this->hasMany(CalificacionAcademica::class);
    }

    public function evaluacionesRegistro()
    {
        return $this->hasMany(EvaluacionRegistro::class);
    }

    public function promedioPeriodo()
    {
        return $this->hasMany(PromedioPeriodo::class);
    }

    public function getNombreAttribute(): string
    {
        return $this->asignatura?->nombre . ' — ' . $this->grupo?->nombre_corto;
    }

    /** Devuelve true si la asignación es de primer ciclo */
    public function esPrimerCiclo(): bool
    {
        return in_array($this->tipo_evaluacion, ['indicadores_logro', 'ra']);
    }
}
