<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class FranjaHoraria extends Model
{
    use BelongsToTenant;

    protected $table = 'franjas_horarias';

    protected $fillable = [
        'numero',
        'hora_inicio',
        'hora_fin',
        'nombre',
        'es_recreo',
        'activa',
        'centro_id',
    ];

    protected $casts = [
        'es_recreo' => 'boolean',
        'activa'    => 'boolean',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre ?? ($this->hora_inicio . ' - ' . $this->hora_fin);
    }

    public function disponibilidades()
    {
        return $this->hasMany(DisponibilidadDocente::class, 'franja_id');
    }

    public function detalles()
    {
        return $this->hasMany(HorarioDetalle::class, 'franja_id');
    }
}
