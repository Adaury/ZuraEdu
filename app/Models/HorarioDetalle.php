<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class HorarioDetalle extends Model
{
    use BelongsToTenant;

    protected $table = 'horario_detalles';

    protected $fillable = [
        'horario_id',
        'asignacion_id',
        'aula_id',
        'franja_id',
        'dia',
        'es_suplencia',
    ];

    protected $casts = [
        'es_suplencia' => 'boolean',
    ];

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function franja()
    {
        return $this->belongsTo(FranjaHoraria::class, 'franja_id');
    }

    public function suplencias()
    {
        return $this->hasMany(Suplencia::class);
    }
}
