<?php

namespace App\Services;

use App\Models\{
    Grupo, SchoolYear, Matricula, Asignacion,
    CompetenciaEspecifica, IndicadorLogro,
    EvaluacionRegistro, Asistencia, Promocion
};
use Illuminate\Support\Collection;

class RegistroAcademicoService
{
    // ─────────────────────────────────────────────────────────────────────────
    // CONSTANTES
    // ─────────────────────────────────────────────────────────────────────────

    /** Nota mínima aprobatoria */
    const NOTA_APROBATORIA    = 65;
    const PCT_ASISTENCIA_MIN  = 75;

    /** Escala cualitativa primer ciclo */
    const ESCALA = [
        1 => 'Inicial',
        2 => 'En proceso',
        3 => 'Logrado',
        4 => 'Avanzado',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRO COMPLETO DE UN GRUPO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve toda la estructura de datos para renderizar el registro MINERD.
     * Optimizado: sólo 4 queries (sin N+1).
     */
    public function buildRegistro(Grupo $grupo, SchoolYear $schoolYear): array
    {
        $ciclo    = $grupo->grado->ciclo ?? 'primer_ciclo';
        $periodos = $schoolYear->periodos()->orderBy('numero')->get();

        // ── Asignaciones con CE e IL cargados ─────────────────────────────
        $asignaciones = Asignacion::with([
            'asignatura' => fn($q) => $q->with([
                'competenciasActivas' => fn($q2) => $q2->where('ciclo', $ciclo)
                    ->with(['indicadoresActivos']),
            ]),
            'docente',
        ])
        ->where('grupo_id', $grupo->id)
        ->where('school_year_id', $schoolYear->id)
        ->where('activo', true)
        ->orderBy('id')
        ->get();

        // ── Matrículas ordenadas ───────────────────────────────────────────
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->orderBy('numero_orden')
            ->get();

        $matriculaIds = $matriculas->pluck('id');

        // ── Una sola query para todas las evaluaciones ────────────────────
        $rawEvals = EvaluacionRegistro::whereIn('matricula_id', $matriculaIds)
            ->where('school_year_id', $schoolYear->id)
            ->get();

        // Indexar por: matricula_asignacion_periodo_indicador_competencia
        $evalMap = [];
        foreach ($rawEvals as $e) {
            $key = "{$e->matricula_id}_{$e->asignacion_id}_{$e->periodo_id}_{$e->indicador_id}_{$e->competencia_id}";
            $evalMap[$key] = $e;
        }

        // ── Asistencias por matrícula/periodo ─────────────────────────────
        $asistenciaMap = $this->buildAsistenciaMap($matriculaIds, $periodos, $schoolYear);

        // ── Construir estructura de registro ─────────────────────────────
        $registro = [];

        foreach ($matriculas as $m) {
            $estudianteRow = [
                'matricula'       => $m,
                'materias'        => [],
                'promedio_general'=> null,
                'pct_asistencia'  => $asistenciaMap[$m->id]['pct_general'] ?? null,
            ];

            $promediosMateria = [];

            foreach ($asignaciones as $asig) {
                $ces = $asig->asignatura->competenciasActivas ?? collect();

                $materiaRow = [
                    'asignacion'   => $asig,
                    'competencias' => [],
                    'promedio'     => null,
                    'aprobada'     => null,
                ];

                $promediosCE = [];

                foreach ($ces as $ce) {
                    $ceRow = [
                        'ce'          => $ce,
                        'indicadores' => [],
                        'periodos'    => [],  // CE directa (sin ILs)
                        'promedio'    => null,
                    ];

                    $ils = $ce->indicadoresActivos ?? collect();

                    if ($ils->isNotEmpty()) {
                        // ── Evaluación por IL ──────────────────────────
                        $promediosIL = [];

                        foreach ($ils as $il) {
                            $ilRow = ['il' => $il, 'periodos' => [], 'promedio' => null];
                            $valoresPeriodo = [];

                            foreach ($periodos as $p) {
                                $key = "{$m->id}_{$asig->id}_{$p->id}_{$il->id}_";
                                $eval = $evalMap[$key] ?? null;

                                if ($ciclo === 'primer_ciclo') {
                                    $valor = $eval?->valor_cualitativo;
                                } else {
                                    $valor = $eval?->nota_numerica;
                                }

                                $ilRow['periodos'][$p->id] = $valor;
                                if ($valor !== null) $valoresPeriodo[] = $valor;
                            }

                            $ilRow['promedio'] = count($valoresPeriodo)
                                ? round(array_sum($valoresPeriodo) / count($valoresPeriodo), 2)
                                : null;

                            if ($ilRow['promedio'] !== null) $promediosIL[] = $ilRow['promedio'];
                            $ceRow['indicadores'][] = $ilRow;
                        }

                        $ceRow['promedio'] = count($promediosIL)
                            ? round(array_sum($promediosIL) / count($promediosIL), 2)
                            : null;

                    } else {
                        // ── Evaluación directa por CE ──────────────────
                        $valoresPeriodo = [];
                        foreach ($periodos as $p) {
                            $key  = "{$m->id}_{$asig->id}_{$p->id}_{$ce->id}";
                            $eval = $evalMap[$key] ?? null;
                            $nota = $eval?->nota_numerica;
                            $ceRow['periodos'][$p->id] = $nota;
                            if ($nota !== null) $valoresPeriodo[] = $nota;
                        }
                        $ceRow['promedio'] = count($valoresPeriodo)
                            ? round(array_sum($valoresPeriodo) / count($valoresPeriodo), 2)
                            : null;
                    }

                    if ($ceRow['promedio'] !== null) $promediosCE[] = $ceRow['promedio'];
                    $materiaRow['competencias'][] = $ceRow;
                }

                $materiaRow['promedio'] = count($promediosCE)
                    ? round(array_sum($promediosCE) / count($promediosCE), 2)
                    : null;

                if ($materiaRow['promedio'] !== null) {
                    $materiaRow['aprobada'] = $ciclo === 'primer_ciclo'
                        ? $materiaRow['promedio'] >= 2.5   // ≥ 3 Logrado
                        : $materiaRow['promedio'] >= self::NOTA_APROBATORIA;

                    $promediosMateria[] = $materiaRow['promedio'];
                }

                $estudianteRow['materias'][] = $materiaRow;
            }

            $estudianteRow['promedio_general'] = count($promediosMateria)
                ? round(array_sum($promediosMateria) / count($promediosMateria), 2)
                : null;

            $registro[] = $estudianteRow;
        }

        return [
            'grupo'       => $grupo,
            'ciclo'       => $ciclo,
            'periodos'    => $periodos,
            'asignaciones'=> $asignaciones,
            'registro'    => $registro,
            'schoolYear'  => $schoolYear,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ASISTENCIA
    // ─────────────────────────────────────────────────────────────────────────

    private function buildAsistenciaMap(Collection $matriculaIds, Collection $periodos, SchoolYear $schoolYear): array
    {
        $asistencias = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->whereHas('asignacion', fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $map = [];

        foreach ($matriculaIds as $mId) {
            $map[$mId] = ['pct_general' => null, 'periodos' => []];
            $matr = $asistencias->where('matricula_id', $mId);
            $total = $matr->count();
            $pres  = $matr->whereIn('estado', ['presente', 'tardanza'])->count();
            $map[$mId]['pct_general'] = $total > 0 ? round(($pres / $total) * 100, 1) : null;
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GUARDAR EVALUACIÓN (AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Guarda o actualiza una evaluación individual.
     * $tipo: 'indicador' | 'competencia'
     */
    public function guardarEvaluacion(
        int    $matriculaId,
        int    $asignacionId,
        int    $periodoId,
        int    $schoolYearId,
        string $tipo,
        int    $referenciaId,
        mixed  $valor,
        int    $userId
    ): EvaluacionRegistro {

        $data = [
            'matricula_id'  => $matriculaId,
            'asignacion_id' => $asignacionId,
            'periodo_id'    => $periodoId,
            'school_year_id'=> $schoolYearId,
            'competencia_id'=> null,
            'indicador_id'  => null,
            'registrado_por'=> $userId,
        ];

        if ($tipo === 'indicador') {
            $data['indicador_id'] = $referenciaId;
            // Detectar si cualitativo o numérico
            if (is_int($valor) && $valor >= 1 && $valor <= 4) {
                $data['valor_cualitativo'] = (int) $valor;
                $data['nota_numerica']     = null;
            } else {
                $data['nota_numerica']     = (float) $valor;
                $data['valor_cualitativo'] = null;
            }
        } else {
            $data['competencia_id'] = $referenciaId;
            $data['nota_numerica']  = (float) $valor;
        }

        return EvaluacionRegistro::updateOrCreate(
            [
                'matricula_id'  => $matriculaId,
                'asignacion_id' => $asignacionId,
                'periodo_id'    => $periodoId,
                'indicador_id'  => $data['indicador_id'],
                'competencia_id'=> $data['competencia_id'],
            ],
            $data
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROMOCIÓN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcula y persiste la promoción de un estudiante.
     * Regla MINERD: promedio ≥ 65 Y asistencia ≥ 75%.
     * Segundo ciclo: máximo 2 materias reprobadas → condicionado.
     */
    public function calcularPromocion(Matricula $matricula, SchoolYear $schoolYear): Promocion
    {
        // Cargar grupo+grado si no están cargados
        $matricula->loadMissing(['grupo.grado']);
        $ciclo = $matricula->grupo?->grado?->ciclo ?? 'primer_ciclo';

        $registro = $this->buildRegistro($matricula->grupo, $schoolYear);
        $estudianteRow = collect($registro['registro'])
            ->first(fn($r) => $r['matricula']->id === $matricula->id);

        $promedioFinal  = $estudianteRow['promedio_general'] ?? null;
        $pctAsistencia  = $estudianteRow['pct_asistencia']   ?? null;

        $reprobadas = [];
        foreach ($estudianteRow['materias'] ?? [] as $mat) {
            if ($mat['aprobada'] === false) {
                $reprobadas[] = $mat['asignacion']->asignatura->nombre;
            }
        }

        $numReprobadas = count($reprobadas);

        // Determinar estado
        if ($promedioFinal === null) {
            $estado = 'pendiente';
        } elseif ($ciclo === 'primer_ciclo') {
            $aprueba = $promedioFinal >= 2.5
                && ($pctAsistencia === null || $pctAsistencia >= self::PCT_ASISTENCIA_MIN);
            $estado = $aprueba ? 'promovido' : 'no_promovido';
        } else {
            $aprueba = $promedioFinal >= self::NOTA_APROBATORIA
                && ($pctAsistencia === null || $pctAsistencia >= self::PCT_ASISTENCIA_MIN);

            if (!$aprueba && $numReprobadas <= 2) {
                $estado = 'condicionado';
            } else {
                $estado = $aprueba ? 'promovido' : 'no_promovido';
            }
        }

        return Promocion::updateOrCreate(
            ['matricula_id' => $matricula->id, 'school_year_id' => $schoolYear->id],
            [
                'estado'                      => $estado,
                'promedio_final'              => $promedioFinal,
                'pct_asistencia'              => $pctAsistencia,
                'materias_reprobadas'         => $numReprobadas,
                'materias_reprobadas_detalle' => $reprobadas,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS ESTÁTICOS
    // ─────────────────────────────────────────────────────────────────────────

    public static function escalaLabel(int $v): string
    {
        return self::ESCALA[$v] ?? '—';
    }

    public static function notaColor(float|int $v, string $ciclo = 'segundo_ciclo'): string
    {
        if ($ciclo === 'primer_ciclo') {
            return match(true) {
                $v >= 3.5 => '#d1fae5',
                $v >= 2.5 => '#a7f3d0',
                $v >= 1.5 => '#fef3c7',
                default   => '#fee2e2',
            };
        }
        return match(true) {
            $v >= 90 => '#d1fae5',
            $v >= 65 => '#dcfce7',
            $v >= 50 => '#fef3c7',
            default  => '#fee2e2',
        };
    }

    public static function notaLetra(float $nota): string
    {
        return match(true) {
            $nota >= 90 => 'A',
            $nota >= 80 => 'B',
            $nota >= 65 => 'C',
            $nota >= 50 => 'D',
            default     => 'F',
        };
    }
}
