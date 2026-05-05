<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentoEvaluacionEstudiante extends Model
{
    use BelongsToTenant;

    protected $table = 'instrumento_evaluaciones';

    protected $fillable = [
        'instrumento_id', 'matricula_id',
        'puntajes', 'ponderacion', 'nivel_desempeno', 'observacion',
    ];

    protected $casts = [
        'puntajes'     => 'array',
        'ponderacion'  => 'decimal:2',
    ];

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(InstrumentoEvaluacion::class, 'instrumento_id');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function getPuntajeCriterio(int $criterioId): ?float
    {
        return $this->puntajes[$criterioId] ?? null;
    }
}
