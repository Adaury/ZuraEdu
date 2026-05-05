<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function periodos()
    {
        return $this->hasMany(Periodo::class);
    }

    public function configCalificaciones()
    {
        return $this->hasMany(ConfigCalificacion::class);
    }

    public function boletinConfig()
    {
        return $this->hasMany(BoletinConfig::class);
    }

    public function scopeActivo($q)
    {
        return $q->where('activo', true);
    }

    public static function actual(): ?self
    {
        return static::where('activo', true)->first();
    }

    public function getNombrePeriodoAttribute(): string
    {
        return 'Año Escolar ' . $this->nombre;
    }
}
