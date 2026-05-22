<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasoSeguimiento extends Model
{
    use BelongsToTenant;

    protected $table = 'casos_seguimiento';

    protected $fillable = [
        'tenant_id',
        'estudiante_id',
        'tipo',
        'descripcion',
        'nivel_riesgo',
        'estado',
        'responsable_id',
        'fecha_apertura',
        'fecha_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre'   => 'date',
    ];

    // ── Catálogos ────────────────────────────────────────────────────────────

    const TIPOS = [
        'academico'  => 'Académico',
        'social'     => 'Social',
        'familiar'   => 'Familiar',
        'conductual' => 'Conductual',
        'otro'       => 'Otro',
    ];

    const NIVELES_RIESGO = [
        'bajo'    => ['label' => 'Bajo',    'color' => 'green'],
        'medio'   => ['label' => 'Medio',   'color' => 'yellow'],
        'alto'    => ['label' => 'Alto',    'color' => 'orange'],
        'critico' => ['label' => 'Crítico', 'color' => 'red'],
    ];

    const ESTADOS = [
        'abierto'        => ['label' => 'Abierto',        'color' => 'blue'],
        'en_seguimiento' => ['label' => 'En Seguimiento', 'color' => 'indigo'],
        'cerrado'        => ['label' => 'Cerrado',        'color' => 'gray'],
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class)->withDefault();
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id')->withDefault();
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(IntervencionCaso::class, 'caso_id')->orderBy('fecha');
    }

    public function intervencionesDesc(): HasMany
    {
        return $this->hasMany(IntervencionCaso::class, 'caso_id')->orderByDesc('fecha');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getNivelRiesgoInfoAttribute(): array
    {
        return self::NIVELES_RIESGO[$this->nivel_riesgo] ?? ['label' => $this->nivel_riesgo, 'color' => 'gray'];
    }

    public function getEstadoInfoAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? ['label' => $this->estado, 'color' => 'gray'];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAbiertos($q)
    {
        return $q->whereIn('estado', ['abierto', 'en_seguimiento']);
    }

    public function scopeCriticos($q)
    {
        return $q->where('nivel_riesgo', 'critico');
    }
}
