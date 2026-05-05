<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evento extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'cupo_maximo',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function inscripciones()
    {
        return $this->hasMany(InscripcionEvento::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'inscripciones_evento')
                    ->withPivot('fecha_inscripcion', 'asistio')
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function getTipoColorAttribute(): string
    {
        return match($this->tipo) {
            'academico'  => 'blue',
            'deportivo'  => 'green',
            'cultural'   => 'purple',
            'social'     => 'yellow',
            default      => 'gray',
        };
    }

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'academico'  => 'Académico',
            'deportivo'  => 'Deportivo',
            'cultural'   => 'Cultural',
            'social'     => 'Social',
            default      => 'Otro',
        };
    }

    public function getCuposDisponiblesAttribute(): ?int
    {
        if (is_null($this->cupo_maximo)) return null;
        return max(0, $this->cupo_maximo - $this->inscripciones()->count());
    }

    public function estaLlenoAttribute(): bool
    {
        if (is_null($this->cupo_maximo)) return false;
        return $this->inscripciones()->count() >= $this->cupo_maximo;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopePorTipo($q, string $tipo)
    {
        return $q->where('tipo', $tipo);
    }
}
