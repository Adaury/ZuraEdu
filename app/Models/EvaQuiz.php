<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaQuiz extends Model
{
    use BelongsToTenant;

    protected $table = 'eva_quizzes';

    protected $fillable = [
        'asignacion_id', 'titulo', 'instrucciones',
        'duracion_minutos', 'intentos_max', 'mostrar_resultados',
        'aleatorizar', 'publicado', 'disponible_desde', 'disponible_hasta',
    ];

    protected $casts = [
        'mostrar_resultados' => 'boolean',
        'aleatorizar'        => 'boolean',
        'publicado'          => 'boolean',
        'disponible_desde'   => 'datetime',
        'disponible_hasta'   => 'datetime',
    ];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(EvaPregunta::class, 'quiz_id')->orderBy('orden');
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(EvaIntento::class, 'quiz_id');
    }

    public function getPuntajeTotalAttribute(): float
    {
        return (float) $this->preguntas->sum('puntos');
    }

    public function intentoActivo(int $matriculaId): ?EvaIntento
    {
        return $this->intentos()->where('matricula_id', $matriculaId)->where('estado', 'en_curso')->first();
    }

    public function intentoFinalizado(int $matriculaId): ?EvaIntento
    {
        return $this->intentos()->where('matricula_id', $matriculaId)->where('estado', 'finalizado')
            ->orderByDesc('puntuacion')->first();
    }

    public function puedeIntentar(int $matriculaId): bool
    {
        $total = $this->intentos()->where('matricula_id', $matriculaId)
            ->whereIn('estado', ['finalizado', 'en_curso'])->count();
        return $total < $this->intentos_max;
    }

    public function estaDisponible(): bool
    {
        if (! $this->publicado) return false;
        $ahora = now();
        if ($this->disponible_desde && $ahora->lt($this->disponible_desde)) return false;
        if ($this->disponible_hasta && $ahora->gt($this->disponible_hasta)) return false;
        return true;
    }
}
