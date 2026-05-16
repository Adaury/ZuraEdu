<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaPregunta extends Model
{
    use BelongsToTenant;

    protected $table = 'eva_preguntas';

    protected $fillable = [
        'quiz_id', 'orden', 'enunciado', 'tipo', 'opciones', 'puntos', 'explicacion',
    ];

    protected $casts = [
        'opciones' => 'array',
        'puntos'   => 'float',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(EvaQuiz::class, 'quiz_id');
    }

    public function opcionCorrecta(): ?int
    {
        if (! $this->opciones) return null;
        foreach ($this->opciones as $i => $op) {
            if (! empty($op['correcta'])) return $i;
        }
        return null;
    }

    public function esRespuestaCorrecta(mixed $respuesta): bool
    {
        return match ($this->tipo) {
            'multiple', 'verdadero_falso' => (int) $respuesta === $this->opcionCorrecta(),
            'abierta' => false, // Requiere revisión manual
            default   => false,
        };
    }
}
