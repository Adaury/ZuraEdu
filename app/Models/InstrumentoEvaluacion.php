<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentoEvaluacion extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'instrumentos_evaluacion';

    protected $fillable = [
        'asignacion_id', 'school_year_id', 'docente_id',
        'titulo', 'tipo', 'competencia', 'descripcion',
        'indicadores_logro', 'observaciones', 'valoracion_global',
        'niveles_desempeno', 'publicado', 'creado_por',
    ];

    protected $casts = [
        'niveles_desempeno' => 'array',
        'publicado'         => 'boolean',
    ];

    public static array $tiposLabels = [
        'lista_cotejo'      => 'Lista de Cotejo',
        'rubrica'           => 'Rúbrica',
        'escala_estimacion' => 'Escala de Estimación',
    ];

    public static array $nivelesDefault = [
        ['clave' => 'excelente',   'label' => 'Excelente',   'descripcion' => 'Demuestra dominio sobresaliente.', 'valor' => 4],
        ['clave' => 'bueno',       'label' => 'Bueno',       'descripcion' => 'Aplica estrategias adecuadas.', 'valor' => 3],
        ['clave' => 'regular',     'label' => 'Regular',     'descripcion' => 'Muestra algunas habilidades con apoyo.', 'valor' => 2],
        ['clave' => 'en_proceso',  'label' => 'En Proceso',  'descripcion' => 'Requiere guía constante.', 'valor' => 1],
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

    public function criterios(): HasMany
    {
        return $this->hasMany(InstrumentoCriterio::class, 'instrumento_id')->orderBy('orden');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(InstrumentoEvaluacionEstudiante::class, 'instrumento_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    public function getTipoLabelAttribute(): string
    {
        return self::$tiposLabels[$this->tipo] ?? $this->tipo;
    }

    public function getNivelesAttribute(): array
    {
        return $this->niveles_desempeno ?? self::$nivelesDefault;
    }
}
