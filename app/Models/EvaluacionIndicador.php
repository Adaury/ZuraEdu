<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class EvaluacionIndicador extends Model
{
    use BelongsToTenant;

    protected $table = 'evaluaciones_indicadores';

    public const NIVELES = ['Excelente', 'Bueno', 'En proceso', 'Insuficiente'];

    protected $fillable = [
        'matricula_id',
        'indicador_id',
        'periodo_id',
        'nivel',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function indicador()
    {
        return $this->belongsTo(IndicadorAprendizaje::class, 'indicador_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function getNivelColorAttribute(): string
    {
        return match ($this->nivel) {
            'Excelente'   => '#198754',
            'Bueno'       => '#0d6efd',
            'En proceso'  => '#ffc107',
            'Insuficiente'=> '#dc3545',
            default       => '#6c757d',
        };
    }

    public function getNivelIconAttribute(): string
    {
        return match ($this->nivel) {
            'Excelente'   => '⭐',
            'Bueno'       => '✅',
            'En proceso'  => '⚠️',
            'Insuficiente'=> '❌',
            default       => '—',
        };
    }
}
