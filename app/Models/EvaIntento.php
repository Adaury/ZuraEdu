<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaIntento extends Model
{
    use BelongsToTenant;

    protected $table = 'eva_intentos';

    protected $fillable = [
        'quiz_id', 'matricula_id', 'estado', 'respuestas',
        'puntuacion', 'puntuacion_max', 'iniciado_en', 'finalizado_en',
    ];

    protected $casts = [
        'respuestas'     => 'array',
        'puntuacion'     => 'float',
        'puntuacion_max' => 'float',
        'iniciado_en'    => 'datetime',
        'finalizado_en'  => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(EvaQuiz::class, 'quiz_id');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function getPorcentajeAttribute(): float
    {
        if (! $this->puntuacion_max) return 0;
        return round(($this->puntuacion / $this->puntuacion_max) * 100, 1);
    }

    public function getDuracionAttribute(): ?string
    {
        if (! $this->finalizado_en || ! $this->iniciado_en) return null;
        $seg = $this->iniciado_en->diffInSeconds($this->finalizado_en);
        return $seg >= 60 ? floor($seg / 60) . ' min ' . ($seg % 60) . 's' : $seg . 's';
    }
}
