<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BancoPregunta extends Model
{
    use BelongsToTenant;

    protected $table = 'banco_preguntas';

    protected $fillable = [
        'docente_id', 'asignatura_id', 'enunciado', 'tipo',
        'opciones', 'puntos_default', 'explicacion', 'categoria', 'usos',
    ];

    protected $casts = [
        'opciones'       => 'array',
        'puntos_default' => 'float',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function opcionCorrecta(): ?int
    {
        if (! $this->opciones) return null;
        foreach ($this->opciones as $i => $op) {
            if (! empty($op['correcta'])) return $i;
        }
        return null;
    }
}
