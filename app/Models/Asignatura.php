<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    protected $fillable = [
        'codigo', 'nombre', 'descripcion',
        'area', 'area_id', 'familia_id',
        'horas_semanales', 'color',
        'activo', 'es_basica', 'num_ra',
    ];

    protected $casts = [
        'activo'   => 'boolean',
        'es_basica'=> 'boolean',
        'num_ra'   => 'integer',
    ];

    /** Familia profesional (área técnica) */
    public function familiaProfesional()
    {
        return $this->belongsTo(FamiliaProfesional::class, 'familia_id');
    }

    /** Área normalizada (tabla areas) */
    public function areaNormalizada()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function resultadosAprendizaje()
    {
        return $this->hasMany(ResultadoAprendizaje::class)->orderBy('numero');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class);
    }

    public function indicadoresAprendizaje()
    {
        return $this->hasMany(IndicadorAprendizaje::class);
    }

    /** Competencias Específicas (CE) del currículo MINERD */
    public function competencias()
    {
        return $this->hasMany(CompetenciaEspecifica::class)->orderBy('orden');
    }

    /** CE activas filtradas por ciclo */
    public function competenciasActivas()
    {
        return $this->hasMany(CompetenciaEspecifica::class)
                    ->where('activo', true)
                    ->orderBy('orden');
    }

    public function competenciasPrimerCiclo()
    {
        return $this->competenciasActivas()->where('ciclo', 'primer_ciclo');
    }

    public function competenciasSegundoCiclo()
    {
        return $this->competenciasActivas()->where('ciclo', 'segundo_ciclo');
    }

    public function scopeActivas($q)
    {
        return $q->where('activo', true);
    }

    public function scopeBasicas($q)
    {
        return $q->where('activo', true)->where('es_basica', true);
    }
}
