<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_year_id',
        'nombre',
        'estado',
        'es_activo',
        'generado_en',
        'iteraciones',
        'score',
        'conflictos',
        'centro_id',
        'creado_por',
    ];

    protected $casts = [
        'generado_en' => 'datetime',
        'es_activo'   => 'boolean',
        'conflictos'  => 'array',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function detalles()
    {
        return $this->hasMany(HorarioDetalle::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Helpers

    public function getEstaPublicadoAttribute(): bool
    {
        return $this->estado === 'publicado';
    }

    public function getScoreColorAttribute(): string
    {
        if ($this->score >= 90) return '#16a34a';
        if ($this->score >= 70) return '#d97706';
        return '#dc2626';
    }
}
