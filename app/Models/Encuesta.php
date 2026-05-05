<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encuesta extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'titulo',
        'descripcion',
        'dirigida_a',
        'activo',
        'fecha_cierre',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fecha_cierre' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function preguntas(): HasMany
    {
        return $this->hasMany(PreguntaEncuesta::class)->orderBy('orden');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaEncuesta::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                     ->where(function ($q) {
                         $q->whereNull('fecha_cierre')
                           ->orWhere('fecha_cierre', '>=', today());
                     });
    }

    public function scopeDirigidaA($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('dirigida_a', $rol)
              ->orWhere('dirigida_a', 'todos');
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Indica si el usuario ya respondió esta encuesta */
    public function yaRespondio(int $userId): bool
    {
        return $this->respuestas()->where('user_id', $userId)->exists();
    }

    /** Total de participantes únicos */
    public function totalParticipantes(): int
    {
        return $this->respuestas()->distinct('user_id')->count('user_id');
    }

    public function getDirigidaALabelAttribute(): string
    {
        return match($this->dirigida_a) {
            'padres'      => 'Padres / Representantes',
            'estudiantes' => 'Estudiantes',
            default       => 'Todos',
        };
    }
}
