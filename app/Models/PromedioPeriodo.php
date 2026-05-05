<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cache de promedio por estudiante + asignación + período.
 * Se recalcula al publicar calificaciones o asistencia.
 */
class PromedioPeriodo extends Model
{
    protected $table = 'promedios_periodo';

    protected $fillable = [
        'matricula_id', 'asignacion_id', 'periodo_id', 'school_year_id',
        'promedio', 'valor_cualitativo', 'indicador', 'pct_asistencia',
        'publicado', 'calculado_en',
    ];

    protected $casts = [
        'publicado'      => 'boolean',
        'calculado_en'   => 'datetime',
        'promedio'       => 'decimal:2',
        'pct_asistencia' => 'decimal:2',
    ];

    /* ── Relaciones ─────────────────────────────────────────── */

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /* ── Helpers ─────────────────────────────────────────────── */

    /**
     * Escala cualitativa 1-4 → etiqueta MINERD primer ciclo.
     */
    public static function etiquetaCualitativa(int $v): string
    {
        return match($v) {
            1 => 'Inicial',
            2 => 'En proceso',
            3 => 'Logrado',
            4 => 'Avanzado',
            default => '—',
        };
    }

    /**
     * Nota numérica 0-100 → indicador segundo ciclo.
     */
    public static function indicadorNumerico(float $nota): string
    {
        if ($nota >= 90) return 'Excelente';
        if ($nota >= 75) return 'Bueno';
        if ($nota >= 65) return 'En proceso';
        return 'Insuficiente';
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopePublicados($q)  { return $q->where('publicado', true); }
    public function scopeDelAnio($q, int $yearId) { return $q->where('school_year_id', $yearId); }
}
