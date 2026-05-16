<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricaAplicacion extends Model
{
    use BelongsToTenant;

    protected $table = 'rubrica_aplicaciones';

    protected $fillable = [
        'rubrica_id', 'asignacion_id', 'matricula_id',
        'resultados', 'puntaje', 'puntaje_max', 'observaciones', 'aplicado_en',
    ];

    protected $casts = [
        'resultados'  => 'array',
        'puntaje'     => 'float',
        'puntaje_max' => 'float',
        'aplicado_en' => 'datetime',
    ];

    public function rubrica(): BelongsTo
    {
        return $this->belongsTo(Rubrica::class);
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function getPorcentajeAttribute(): float
    {
        if (! $this->puntaje_max) return 0;
        return round(($this->puntaje / $this->puntaje_max) * 100, 1);
    }
}
