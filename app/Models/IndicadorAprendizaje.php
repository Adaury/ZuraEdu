<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class IndicadorAprendizaje extends Model
{
    use BelongsToTenant;

    protected $table = 'indicadores_aprendizaje';

    protected $fillable = [
        'asignatura_id',
        'grado_id',
        'descripcion',
        'periodo_numero',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(EvaluacionIndicador::class, 'indicador_id');
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDelPeriodo($q, int $numero)
    {
        return $q->where('periodo_numero', $numero);
    }
}
