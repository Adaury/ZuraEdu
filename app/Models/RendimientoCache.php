<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class RendimientoCache extends Model
{
    protected $table = 'rendimiento_cache';

    public $timestamps = false;

    protected $fillable = [
        'school_year_id', 'grupo_id', 'periodo_id',
        'total_estudiantes', 'pct_excelente', 'pct_bueno',
        'pct_regular', 'pct_bajo', 'promedio_grupo',
        'total_riesgo', 'total_aprobados', 'total_reprobados',
        'calculado_en',
    ];

    protected $casts = [
        'calculado_en' => 'datetime',
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function getSemaforoAttribute(): string
    {
        if (is_null($this->promedio_grupo)) return 'secondary';
        if ($this->promedio_grupo >= 80) return 'success';
        if ($this->promedio_grupo >= 70) return 'warning';
        return 'danger';
    }

    public static function recalcularParaGrupo(int $grupoId, int $yearId, ?int $periodoId = null): void
    {
        if ($periodoId) {
            $notas = DB::table('calificaciones as c')
                ->join('matriculas as m', 'm.id', '=', 'c.matricula_id')
                ->where('m.grupo_id', $grupoId)
                ->where('m.estado', 'activa')
                ->where('c.periodo_id', $periodoId)
                ->whereNotNull('c.nota_final')
                ->pluck('c.nota_final');
        } else {
            $notas = DB::table('calificaciones_academicas as ca')
                ->join('matriculas as m', 'm.id', '=', 'ca.matricula_id')
                ->where('m.grupo_id', $grupoId)
                ->where('m.estado', 'activa')
                ->where('ca.school_year_id', $yearId)
                ->whereNotNull('ca.nota_final')
                ->pluck('ca.nota_final');
        }

        if ($notas->isEmpty()) return;

        $total     = $notas->count();
        $promedio  = round($notas->avg(), 2);
        $excelente = $notas->filter(fn($n) => $n >= 90)->count();
        $bueno     = $notas->filter(fn($n) => $n >= 80 && $n < 90)->count();
        $regular   = $notas->filter(fn($n) => $n >= 70 && $n < 80)->count();
        $bajo      = $notas->filter(fn($n) => $n < 70)->count();

        self::updateOrCreate(
            ['school_year_id' => $yearId, 'grupo_id' => $grupoId, 'periodo_id' => $periodoId],
            [
                'total_estudiantes' => $total,
                'pct_excelente'     => $total > 0 ? round($excelente / $total * 100, 2) : 0,
                'pct_bueno'         => $total > 0 ? round($bueno    / $total * 100, 2) : 0,
                'pct_regular'       => $total > 0 ? round($regular  / $total * 100, 2) : 0,
                'pct_bajo'          => $total > 0 ? round($bajo     / $total * 100, 2) : 0,
                'promedio_grupo'    => $promedio,
                'total_riesgo'      => $bajo,
                'total_aprobados'   => $total - $bajo,
                'total_reprobados'  => $bajo,
                'calculado_en'      => now(),
            ]
        );
    }
}
