<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table    = 'sch_horarios';
    protected $fillable = ['nombre', 'estado', 'score', 'iteraciones', 'conflictos', 'generado_en'];

    protected $casts = [
        'conflictos'  => 'array',
        'generado_en' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(HorarioDetalle::class, 'horario_id');
    }

    public function getEstaPublicadoAttribute(): bool
    {
        return $this->estado === 'publicado';
    }
}
