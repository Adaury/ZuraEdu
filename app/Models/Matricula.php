<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $fillable = [
        'school_year_id',
        'estudiante_id',
        'grupo_id',
        'fecha_matricula',
        'numero_orden',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function evaluacionesIndicadores()
    {
        return $this->hasMany(EvaluacionIndicador::class);
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

    public function promocion()
    {
        return $this->hasOne(Promocion::class);
    }

    public function pagos()
    {
        return $this->hasMany(\App\Models\Pago::class);
    }

    public function scopeActivas($q)
    {
        return $q->where('estado', 'activa');
    }

    public function scopeDelAnio($q, int $yearId)
    {
        return $q->where('school_year_id', $yearId);
    }
}
