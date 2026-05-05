<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcQuiz extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_quizzes';

    protected $fillable = [
        'material_id',
        'duracion_minutos',
        'intentos_max',
        'autocorreccion',
        'aleatorizar_preguntas',
        'mostrar_respuestas',
    ];

    protected $casts = [
        'autocorreccion'        => 'boolean',
        'aleatorizar_preguntas' => 'boolean',
        'mostrar_respuestas'    => 'boolean',
    ];

    public function material()
    {
        return $this->belongsTo(MaterialClase::class, 'material_id');
    }

    public function preguntas()
    {
        return $this->hasMany(ZcPregunta::class, 'quiz_id')->orderBy('orden');
    }

    public function intentos()
    {
        return $this->hasMany(ZcIntento::class, 'quiz_id');
    }

    public function getPuntajeTotalAttribute(): float
    {
        return $this->preguntas->sum('puntos');
    }

    public function intentoActivo(int $matriculaId): ?ZcIntento
    {
        return $this->intentos()
            ->where('matricula_id', $matriculaId)
            ->where('estado', 'en_curso')
            ->first();
    }

    public function puedeIntentar(int $matriculaId): bool
    {
        $total = $this->intentos()
            ->where('matricula_id', $matriculaId)
            ->whereIn('estado', ['finalizado', 'en_curso'])
            ->count();
        return $total < $this->intentos_max;
    }
}
