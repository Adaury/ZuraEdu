<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicadorLogro extends Model
{
    use BelongsToTenant;

    protected $table = 'indicadores_logro';

    protected $fillable = [
        'competencia_id', 'codigo', 'descripcion', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(CompetenciaEspecifica::class, 'competencia_id');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionRegistro::class, 'indicador_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Etiqueta de nivel cualitativo (primer ciclo).
     */
    public static function labelCualitativo(int $valor): string
    {
        return match($valor) {
            1 => 'Inicial',
            2 => 'En proceso',
            3 => 'Logrado',
            4 => 'Avanzado',
            default => '—',
        };
    }

    public static function colorCualitativo(int $valor): string
    {
        return match($valor) {
            1 => '#fee2e2',   // rojo claro
            2 => '#fef3c7',   // amarillo
            3 => '#d1fae5',   // verde claro
            4 => '#a7f3d0',   // verde fuerte
            default => '#f3f4f6',
        };
    }
}
