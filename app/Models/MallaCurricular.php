<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MallaCurricular extends Model
{
    protected $table = 'malla_curricular';

    protected $fillable = [
        'grado_id', 'asignatura_id', 'area', 'especialidad_id',
        'horas_semanales', 'horas_anuales', 'es_obligatoria',
        'orden_display', 'notas_curriculo', 'activo',
    ];

    protected $casts = [
        'es_obligatoria' => 'boolean',
        'activo'         => 'boolean',
    ];

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(EspecialidadTecnica::class, 'especialidad_id');
    }

    public function scopePorGrado($query, int $gradoId)
    {
        return $query->where('grado_id', $gradoId);
    }

    public function scopePorArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
