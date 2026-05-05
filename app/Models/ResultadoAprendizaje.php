<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoAprendizaje extends Model
{
    protected $table = 'resultados_aprendizaje';

    protected $fillable = [
        'asignatura_id',
        'numero',
        'descripcion',
        'peso',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'peso'   => 'float',
    ];

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }
}
