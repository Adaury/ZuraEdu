<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_year_id',
        'numero',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'cerrado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
        'cerrado'      => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function calificacionesAcademicas()
    {
        return $this->hasMany(CalificacionAcademica::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function promedioPeriodo()
    {
        return $this->hasMany(PromedioPeriodo::class);
    }

    public function scopeActivo($q)
    {
        return $q->where('activo', true);
    }

    public function scopeAbierto($q)
    {
        return $q->where('cerrado', false);
    }

    public static function actual(): ?self
    {
        return static::where('activo', true)->where('cerrado', false)->first();
    }
}
