<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanClase extends Model
{
    use SoftDeletes;

    protected $table = 'planes_clase';

    protected $fillable = [
        'asignacion_id', 'school_year_id', 'docente_id',
        'titulo', 'area', 'tipo_plan', 'semana',
        'fecha_inicio', 'fecha_fin', 'grado_seccion',
        'intencion_pedagogica', 'estrategias', 'observacion',
        'archivo_path', 'archivo_nombre', 'archivo_tipo',
        'publicado', 'creado_por',
    ];

    protected $casts = [
        'estrategias'   => 'array',
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
        'publicado'     => 'boolean',
    ];

    // ── Strategies catalog ───────────────────────────────────────────────
    public static array $estrategiasCatalogo = [
        'experiencias_previas'   => 'Recuperación de Experiencias Previas',
        'actividades_grupales'   => 'Socialización — Actividades Grupales',
        'indagacion_dialogica'   => 'Indagación Dialógica / Cuestionamiento',
        'debate'                 => 'El Debate',
        'sociodrama'             => 'Sociodrama / Dramatización',
        'expositiva'             => 'Expositiva de Conocimientos',
        'descubrimiento'         => 'Descubrimiento e Indagación',
        'insercion_entorno'      => 'Inserción en el Entorno',
        'aprendizaje_colaborativo' => 'Aprendizaje Colaborativo',
        'gamificacion'           => 'Gamificación',
        'proyecto'               => 'Aprendizaje Basado en Proyectos',
    ];

    // ── Relationships ────────────────────────────────────────────────────
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function momentos(): HasMany
    {
        return $this->hasMany(PlanClaseMomento::class)->orderBy('orden');
    }

    public function inicio(): HasMany
    {
        return $this->momentos()->where('tipo', 'inicio');
    }

    public function desarrollo(): HasMany
    {
        return $this->momentos()->where('tipo', 'desarrollo');
    }

    public function cierre(): HasMany
    {
        return $this->momentos()->where('tipo', 'cierre');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    public function getEstrategiasNombresAttribute(): array
    {
        if (empty($this->estrategias)) return [];
        return array_map(
            fn($k) => self::$estrategiasCatalogo[$k] ?? $k,
            $this->estrategias
        );
    }

    public function tieneArchivo(): bool
    {
        return !empty($this->archivo_path);
    }
}
