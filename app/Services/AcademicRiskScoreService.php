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
use Illuminate\Support\Collection;

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
     *
     * Carga en bulk las 4 fuentes de datos (calificaciones académicas/técnicas,
     * asistencia, faltas disciplinarias) ANTES del loop — antes hacía 4-5
     * queries individuales por estudiante dentro del loop (1200+ queries para
     * 300 estudiantes en esta misma corrida síncrona vía HTTP).
     */
    public function calcularTodos(?int $schoolYearId = null): int
    {
        $schoolYear = $schoolYearId
            ? SchoolYear::find($schoolYearId)
            : SchoolYear::actual();

        if (! $schoolYear) return 0;

        $tenantId = tenant_id() ?? 0;

        $matriculas = Matricula::with([
            'estudiante',
            'grupo.grado',
            'grupo.seccion',
        ])
        ->where('school_year_id', $schoolYear->id)
        ->where('estado', 'activa')
        ->get();

        if ($matriculas->isEmpty()) return 0;

        $matriculaIds  = $matriculas->pluck('id');
        $estudianteIds = $matriculas->pluck('estudiante.id')->filter()->unique();

        $bulkCalAcademicas = CalificacionAcademica::whereIn('matricula_id', $matriculaIds)
            ->get()->groupBy('matricula_id');
        $bulkCalTecnicas = Calificacion::whereIn('matricula_id', $matriculaIds)
            ->get()->groupBy('matricula_id');

        $bulkAsistencia = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->selectRaw("matricula_id, COUNT(*) as total,
                         SUM(CASE WHEN estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistidos")
            ->groupBy('matricula_id')
            ->get()
            ->keyBy('matricula_id');

        $desde = $schoolYear->fecha_inicio ?? Carbon::now()->startOfYear();
        $hasta = $schoolYear->fecha_fin    ?? Carbon::now()->endOfYear();
        $bulkFaltas = FaltaDisciplinaria::whereIn('estudiante_id', $estudianteIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->groupBy('estudiante_id');

        $count = 0;
        foreach ($matriculas as $matricula) {
            $estudiante = $matricula->estudiante;
            if (! $estudiante) continue;

            $notasAcademicas = $bulkCalAcademicas->get($matricula->id) ?? collect();
            $notasTecnicas   = $bulkCalTecnicas->get($matricula->id) ?? collect();

            [$dimAcad, $acadMeta]        = $this->dimensionAcademicaDesdeNotas($notasAcademicas, $notasTecnicas);
            [$dimAsist, $pctAsistencia]  = $this->dimensionAsistenciaDesdeBulk($notasAcademicas, $bulkAsistencia->get($matricula->id));
            [$dimDisc, $discMeta]        = $this->dimensionDisciplinaDesdeFaltas($bulkFaltas->get($estudiante->id) ?? collect());
            $dimTend                     = $this->dimensionTendenciaDesdeNotas($notasAcademicas);

            $data = $this->componerScore($dimAcad, $dimAsist, $dimDisc, $dimTend, $acadMeta, $pctAsistencia, $discMeta);

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

        return $this->componerScore($dimAcad, $dimAsist, $dimDisc, $dimTend, $acadMeta, $pctAsistencia, $discMeta);
    }

    private function componerScore(
        float $dimAcad, float $dimAsist, float $dimDisc, float $dimTend,
        array $acadMeta, ?float $pctAsistencia, array $discMeta
    ): array {
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
        $notasAcademicas = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId)
              ->where('estado', 'activa');
        })->get();

        $notasTecnicas = Calificacion::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId)
              ->where('estado', 'activa');
        })->get();

        return $this->dimensionAcademicaDesdeNotas($notasAcademicas, $notasTecnicas);
    }

    /**
     * Prioridad académica-sobre-técnica delegada a PromedioEstudianteService
     * (la misma regla consolidada el 2026-09-02 tras encontrarla duplicada 4
     * veces) — antes era una 5ª reimplementación independiente aquí.
     */
    private function dimensionAcademicaDesdeNotas(Collection $notasAcademicas, Collection $notasTecnicas): array
    {
        $cals = (new PromedioEstudianteService())->resolverNotas($notasAcademicas, $notasTecnicas);

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
        $notasAcademicas = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId);
        })->get();

        $matriculaIds = Matricula::where('estudiante_id', $estudianteId)
            ->where('school_year_id', $schoolYearId)
            ->where('estado', 'activa')
            ->pluck('id');

        $stats = $matriculaIds->isEmpty() ? null : Asistencia::whereIn('matricula_id', $matriculaIds)
            ->selectRaw("COUNT(*) as total,
                         SUM(CASE WHEN estado IN ('presente','tardanza') THEN 1 ELSE 0 END) as asistidos")
            ->first();

        return $this->dimensionAsistenciaDesdeBulk($notasAcademicas, $stats);
    }

    private function dimensionAsistenciaDesdeBulk(Collection $notasAcademicas, ?object $statsAsistencia): array
    {
        // Primero intentar con pct_asistencia de CalificacionAcademica
        $pctValues = $notasAcademicas->pluck('pct_asistencia')->filter(fn ($v) => $v !== null);
        if ($pctValues->isNotEmpty()) {
            $pct = (float) $pctValues->avg();
            return [$this->scorePorPctAsistencia($pct), round($pct, 2)];
        }

        // Calcular desde registros de Asistencia diaria
        if (! $statsAsistencia || (int) $statsAsistencia->total === 0) {
            return [0, null];
        }

        $pct = round($statsAsistencia->asistidos / $statsAsistencia->total * 100, 2);
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
        $school = SchoolYear::find($schoolYearId);
        $desde  = $school?->fecha_inicio ?? Carbon::now()->startOfYear();
        $hasta  = $school?->fecha_fin    ?? Carbon::now()->endOfYear();

        $faltas = FaltaDisciplinaria::where('estudiante_id', $estudianteId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        return $this->dimensionDisciplinaDesdeFaltas($faltas);
    }

    private function dimensionDisciplinaDesdeFaltas(Collection $faltas): array
    {
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
        $cals = CalificacionAcademica::whereHas('matricula', function ($q) use ($estudianteId, $schoolYearId) {
            $q->where('estudiante_id', $estudianteId)
              ->where('school_year_id', $schoolYearId);
        })->get();

        return $this->dimensionTendenciaDesdeNotas($cals);
    }

    private function dimensionTendenciaDesdeNotas(Collection $cals): float
    {
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
