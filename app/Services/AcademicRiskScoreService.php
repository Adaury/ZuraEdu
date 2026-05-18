<?php

namespace App\Services;

use App\Models\AcademicRiskScore;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\FaltaDisciplinaria;
use App\Models\Matricula;
use App\Models\Asistencia;
use App\Models\SchoolYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AcademicRiskScoreService
{
    // Pesos de cada dimensión en el score final
    const W_ACADEMICO  = 0.40;
    const W_ASISTENCIA = 0.30;
    const W_DISCIPLINA = 0.20;
    const W_TENDENCIA  = 0.10;

    /**
     * Calcula y persiste el score de riesgo para todos los estudiantes activos
     * del año escolar indicado (o el actual). Devuelve la cantidad procesada.
     */
    public function calcularTodos(?int $schoolYearId = null): int
    {
        $schoolYear = $schoolYearId
            ? SchoolYear::find($schoolYearId)
            : SchoolYear::actual();

        if (! $schoolYear) return 0;

        $tenantId = tenant_id() ?? 0;

        // Obtener todas las matrículas activas del año
        $matriculas = Matricula::with([
            'estudiante',
            'grupo.grado',
            'grupo.seccion',
        ])
        ->where('school_year_id', $schoolYear->id)
        ->where('estado', 'activa')
        ->get();

        $count = 0;
        foreach ($matriculas as $matricula) {
            $estudiante = $matricula->estudiante;
            if (! $estudiante) continue;

            $data = $this->calcularParaEstudiante($estudiante->id, $schoolYear->id);

            AcademicRiskScore::updateOrCreate(
                [
                    'tenant_id'      => $tenantId,
                    'estudiante_id'  => $estudiante->id,
                    'school_year_id' => $schoolYear->id,
                ],
                array_merge($data, [
                    'tenant_id'      => $tenantId,
                    'calculado_en'   => now(),
                ])
            );
            $count++;
        }

        return $count;
    }

    /**
     * Calcula (sin persistir) el score completo para un estudiante.
     */
    public function calcularParaEstudiante(int $estudianteId, int $schoolYearId): array
    {
        // ── 1. Datos académicos ───────────────────────────────────────────
        [$dimAcad, $acadMeta] = $this->dimensionAcademica($estudianteId, $schoolYearId);

        // ── 2. Asistencia ────────────────────────────────────────────────
        [$dimAsist, $pctAsistencia] = $this->dimensionAsistencia($estudianteId, $schoolYearId);

        // ── 3. Disciplina ────────────────────────────────────────────────
        [$dimDisc, $discMeta] = $this->dimensionDisciplina($estudianteId, $schoolYearId);

        // ── 4. Tendencia ─────────────────────────────────────────────────
        $dimTend = $this->dimensionTendencia($estudianteId, $schoolYearId);

        // ── Componer score final ─────────────────────────────────────────
        $score = (int) round(
            $dimAcad  * self::W_ACADEMICO  +
            $dimAsist * self::W_ASISTENCIA +
            $dimDisc  * self::W_DISCIPLINA +
            $dimTend  * self::W_TENDENCIA
        );
        $score = max(0, min(100, $score));

        return [
            'score'             => $score,
            'nivel'             => AcademicRiskScore::nivelDesdeScore($score),
            'dim_academico'     => round($dimAcad,  2),
            'dim_asistencia'    => round($dimAsist, 2),
            'dim_disciplina'    => round($dimDisc,  2),
            'dim_tendencia'     => round($dimTend,  2),
            'materias_en_riesgo'=> $acadMeta['en_riesgo'],
            'total_materias'    => $acadMeta['total'],
            'promedio_general'  => $acadMeta['promedio'],
            'pct_asistencia'    => $pctAsistencia,
            'tardanzas'         => $discMeta['tardanzas'],
            'faltas_leves'      => $discMeta['leves'],
            'faltas_graves'     => $discMeta['graves'],
            'suspensiones'      => $discMeta['suspensiones'],
        ];
    }

    // ── Dimensión Académica ───────────────────────────────────────────────

    private function dimensionAcademica(int $estudianteId, int $schoolYearId): array
    {
        // Intentar con CalificacionAcademica (secundaria/técnica 2do ciclo)
        $cals = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId)
              ->where('estado', 'activa');
        })->whereNotNull('nota_final')->get();

        // Si no hay, intentar con Calificacion (1er ciclo técnico)
        if ($cals->isEmpty()) {
            $cals = Calificacion::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
                $q->where('estudiante_id', $estudianteId)
                  ->where('school_year_id', $schoolYearId)
                  ->where('estado', 'activa');
            })->whereNotNull('nota_final')->get();
        }

        $total    = $cals->count();
        $enRiesgo = $cals->where('nota_final', '<', 70)->count();
        $promedio = $total > 0 ? round($cals->avg('nota_final'), 2) : null;

        if ($total === 0) {
            return [0, ['en_riesgo' => 0, 'total' => 0, 'promedio' => null]];
        }

        $pctFallando = $enRiesgo / $total * 100;

        $base = match (true) {
            $pctFallando === 0.0  => 0,
            $pctFallando <= 25    => 25,
            $pctFallando <= 50    => 55,
            $pctFallando <= 75    => 75,
            default               => 90,
        };

        // Penalización por promedio muy bajo
        if ($promedio !== null) {
            if ($promedio < 60)      $base = min($base + 20, 100);
            elseif ($promedio < 70)  $base = min($base + 8,  100);
        }

        return [
            min((float) $base, 100.0),
            ['en_riesgo' => $enRiesgo, 'total' => $total, 'promedio' => $promedio],
        ];
    }

    // ── Dimensión Asistencia ──────────────────────────────────────────────

    private function dimensionAsistencia(int $estudianteId, int $schoolYearId): array
    {
        // Primero intentar con pct_asistencia de CalificacionAcademica
        $pctFromCal = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId);
        })->whereNotNull('pct_asistencia')->avg('pct_asistencia');

        if ($pctFromCal !== null) {
            return [$this->scorePorPctAsistencia((float) $pctFromCal), round((float) $pctFromCal, 2)];
        }

        // Calcular desde registros de Asistencia diaria
        $matriculaIds = Matricula::where('estudiante_id', $estudianteId)
            ->where('school_year_id', $schoolYearId)
            ->where('estado', 'activa')
            ->pluck('id');

        if ($matriculaIds->isEmpty()) return [0, null];

        $stats = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->selectRaw("COUNT(*) as total,
                         SUM(CASE WHEN estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistidos")
            ->first();

        if (! $stats || $stats->total === 0) return [0, null];

        $pct = round($stats->asistidos / $stats->total * 100, 2);
        return [$this->scorePorPctAsistencia($pct), $pct];
    }

    private function scorePorPctAsistencia(float $pct): float
    {
        return match (true) {
            $pct >= 95 => 0,
            $pct >= 90 => 15,
            $pct >= 80 => 35,
            $pct >= 70 => 60,
            $pct >= 60 => 80,
            default    => 100,
        };
    }

    // ── Dimensión Disciplina ──────────────────────────────────────────────

    private function dimensionDisciplina(int $estudianteId, int $schoolYearId): array
    {
        // Buscar faltas en el año escolar actual
        $school = SchoolYear::find($schoolYearId);
        $desde  = $school?->fecha_inicio ?? Carbon::now()->startOfYear();
        $hasta  = $school?->fecha_fin    ?? Carbon::now()->endOfYear();

        $faltas = FaltaDisciplinaria::where('estudiante_id', $estudianteId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        $tardanzas   = $faltas->where('tipo', 'tardanza')->count();
        $leves       = $faltas->where('tipo', 'falta_leve')->count();
        $graves      = $faltas->where('tipo', 'falta_grave')->count();
        $suspensiones= $faltas->where('tipo', 'suspension')->count();

        $score = 0;
        $score += min($tardanzas   * 3,  15);
        $score += min($leves       * 12, 35);
        $score += min($graves      * 22, 55);
        $score += min($suspensiones* 45, 100);

        return [
            min((float) $score, 100.0),
            compact('tardanzas', 'leves', 'graves', 'suspensiones'),
        ];
    }

    // ── Dimensión Tendencia ───────────────────────────────────────────────

    private function dimensionTendencia(int $estudianteId, int $schoolYearId): float
    {
        // Calcular promedio por período usando avg_comp{n}_p{k}
        $cals = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId);
        })->get();

        if ($cals->isEmpty()) return 20.0; // sin datos = estable/neutro

        $avgPorPeriodo = [];
        foreach ([1, 2, 3, 4] as $p) {
            $vals = $cals->flatMap(function ($c) use ($p) {
                return array_filter([
                    $c->{"avg_comp1_p{$p}"},
                    $c->{"avg_comp2_p{$p}"},
                    $c->{"avg_comp3_p{$p}"},
                    $c->{"avg_comp4_p{$p}"},
                ], fn($v) => $v !== null && $v > 0);
            });
            if ($vals->isNotEmpty()) {
                $avgPorPeriodo[$p] = $vals->avg();
            }
        }

        if (count($avgPorPeriodo) < 2) return 20.0;

        $keys   = array_keys($avgPorPeriodo);
        $first  = $avgPorPeriodo[$keys[0]];
        $last   = $avgPorPeriodo[$keys[count($keys) - 1]];
        $delta  = $last - $first;

        return match (true) {
            $delta >= 5  => 0,    // mejora clara
            $delta >= 0  => 10,   // leve mejora / estable
            $delta >= -5 => 35,   // leve declive
            $delta >= -15=> 65,   // declive moderado
            default      => 90,   // declive severo
        };
    }
}
