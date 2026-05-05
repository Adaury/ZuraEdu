<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Aula;
use App\Models\ConfigInstitucional;
use App\Models\DisponibilidadDocente;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\SchoolYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de generación de horarios escolares.
 *
 * Algoritmo: Backtracking + Heurísticas CSP
 *
 * Fases:
 *   1. Ordenar slots por MRV (Most Restricted Variable) — los más difíciles primero.
 *   2. Backtracking puro con forward checking (poda temprana).
 *   3. Si el backtracking falla o alcanza el límite de iteraciones, se aplica
 *      un paso greedy sobre lo que quedó sin cubrir, usando el mejor parcial
 *      que el backtracking logró alcanzar.
 *   4. Todo el proceso se repite hasta MAX_REINTENTOS veces con semillas
 *      distintas; se guarda el resultado con mayor score.
 *
 * Reglas implementadas:
 *   R1 — Un docente no puede estar en dos clases al mismo tiempo.
 *   R2 — Un aula no puede albergar dos grupos al mismo tiempo.
 *   R3 — Un grupo no puede tener dos materias al mismo tiempo.
 *   R4 — Cada materia cumple su cantidad de horas semanales.
 *   R5 — Respetar disponibilidad declarada del docente.
 *   R6 — No repetir la misma materia más de N veces en el mismo día.
 *   R7 — Distribuir materias de forma equilibrada a lo largo de la semana.
 */
class HorarioGeneratorService
{
    // =========================================================================
    //  CONFIGURACIÓN
    // =========================================================================

    private const MAX_REINTENTOS = 3;       // intentos con semillas distintas

    /** Días laborales — configurable desde ConfigInstitucional (horario_dias) */
    private array $diasLaborales = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

    /** Límite de iteraciones — configurable via env HORARIO_MAX_ITER */
    private int $maxIter;

    /** Modo debug — activa trazas detalladas via env HORARIO_DEBUG */
    private bool $debugMode;

    /** Traza del proceso (solo en modo debug) */
    private array $debugLog = [];

    // =========================================================================
    //  DATOS CARGADOS UNA SOLA VEZ
    // =========================================================================

    private Collection $franjas;    // FranjaHoraria activas, sin recreo, ordenadas
    private Collection $aulas;      // Aula disponibles, ordenadas por capacidad ASC
    private array      $noDisponible = []; // [docente_id][dia][franja_id] = true

    // =========================================================================
    //  GRIDS DE OCUPACIÓN — se resetean en cada intento
    // =========================================================================

    /** @var array<int, array<string, array<int, bool>>>  [docente_id][dia][franja_id] */
    private array $ocupadoDocente = [];

    /** @var array<int, array<string, array<int, bool>>>  [grupo_id][dia][franja_id] */
    private array $ocupadoGrupo = [];

    /** @var array<int, array<string, array<int, bool>>>  [aula_id][dia][franja_id] */
    private array $ocupadoAula = [];

    /** @var array<int, array<string, int>>  [docente_id][dia] = count  (R4/R7) */
    private array $clasesHoyDocente = [];

    /** @var array<int, array<string, int>>  [grupo_id][dia] = count  (R4/R7) */
    private array $clasesHoyGrupo = [];

    /** @var array<int, array<string, array<int, int>>>  [grupo_id][dia][asignatura_id] = count  (R6) */
    private array $materiaHoyGrupo = [];

    // =========================================================================
    //  LÍMITES CONFIGURABLES (se cargan de ConfigInstitucional)
    // =========================================================================

    private int $maxHorasDiaDocente   = 6;
    private int $maxHorasDiaGrupo     = 8;
    private int $maxRepeticionMateria = 1; // R6: veces que la misma materia puede aparecer por día por grupo

    // =========================================================================
    //  ESTADO DEL BACKTRACKING — se resetea en cada intento
    // =========================================================================

    private int   $iteraciones    = 0;
    private bool  $limitAlcanzado = false;
    private array $conflictos     = [];

    /** Mejor solución parcial lograda, en caso de que el backtracking no complete */
    private array $mejorParcial      = [];
    private int   $mejorParcialCount = 0;

    // =========================================================================
    //  API PÚBLICA
    // =========================================================================

    /**
     * Genera el horario para el año escolar dado (o el activo si no se especifica).
     *
     * @param  array<int> $grupoIds  Si se especifica, solo genera para esos grupos.
     * @param  int|null   $existingHorarioId  Si se especifica, reemplaza los detalles de ese horario.
     * @return array{horario_id: int, asignados: int, pendientes: int, score: float, conflictos: array}
     *       | array{error: string}
     */
    public function generar(
        ?int  $schoolYearId      = null,
        string $nombre           = 'Horario Principal',
        array  $grupoIds         = [],
        ?int   $existingHorarioId = null
    ): array {
        // ── 0. Inicializar modo debug y límite de iteraciones ─────────────────
        $this->maxIter   = (int) env('HORARIO_MAX_ITER', 150_000);
        $this->debugMode = (bool) env('HORARIO_DEBUG', false);
        $this->debugLog  = [];

        $this->debug('=== INICIO GENERACIÓN ===', [
            'nombre'      => $nombre,
            'grupoIds'    => $grupoIds,
            'maxIter'     => $this->maxIter,
            'reintentos'  => self::MAX_REINTENTOS,
        ]);

        try {
            return $this->ejecutarGeneracion($schoolYearId, $nombre, $grupoIds, $existingHorarioId);
        } catch (\Throwable $e) {
            Log::channel('horario')->error('ERROR CRÍTICO en generación', [
                'mensaje'    => $e->getMessage(),
                'archivo'    => $e->getFile() . ':' . $e->getLine(),
                'trace_corto'=> collect(explode("\n", $e->getTraceAsString()))->take(8)->implode("\n"),
            ]);

            return [
                'error'       => 'No se pudo generar el horario debido a un error interno.',
                'sugerencias' => [
                    'Verifica que existan asignaciones con horas_semana configuradas.',
                    'Comprueba que haya franjas horarias y aulas disponibles.',
                    'Revisa el log en storage/logs/horario.log para más detalles.',
                ],
                'debug'       => $this->debugMode ? $this->debugLog : [],
            ];
        }
    }

    private function ejecutarGeneracion(
        ?int   $schoolYearId,
        string $nombre,
        array  $grupoIds,
        ?int   $existingHorarioId
    ): array {
        // ── 1. Año escolar ────────────────────────────────────────────────────
        $schoolYear = $schoolYearId
            ? SchoolYear::find($schoolYearId)
            : SchoolYear::actual();

        if (! $schoolYear) {
            return ['error' => 'No hay año escolar activo.'];
        }

        Log::channel('horario')->info('INICIO generación', [
            'nombre'      => $nombre,
            'school_year' => $schoolYear->nombre,
            'grupos'      => $grupoIds ?: 'todos',
        ]);

        // ── 2. Configuración ──────────────────────────────────────────────────
        $this->maxHorasDiaDocente   = (int) ConfigInstitucional::get('max_horas_dia_docente', 6);
        $this->maxHorasDiaGrupo     = (int) ConfigInstitucional::get('max_horas_dia_grupo', 8);
        $this->maxRepeticionMateria = (int) ConfigInstitucional::get('max_misma_materia_dia', 1);
        $this->diasLaborales        = ConfigInstitucional::get('horario_dias', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes']);

        // ── 3. Franjas activas (sin recreo) ───────────────────────────────────
        $this->franjas = FranjaHoraria::where('activa', true)
            ->where('es_recreo', false)
            ->orderBy('numero')
            ->get();

        if ($this->franjas->isEmpty()) {
            return ['error' => 'No hay franjas horarias activas. Ve a Horarios → Franjas Horarias.'];
        }

        // ── 4. Aulas (ordenadas por capacidad ASC para elegir la más pequeña que sirva) ──
        $this->aulas = Aula::where('disponible', true)->orderBy('capacidad')->get();

        // ── 5. Disponibilidad docentes ────────────────────────────────────────
        $this->cargarNoDisponibilidad($schoolYear->id);

        // ── 6. Asignaciones con horas configuradas ────────────────────────────
        $asignaciones = Asignacion::with(['grupo', 'docente', 'asignatura'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->whereNotNull('horas_semana')
            ->where('horas_semana', '>', 0)
            ->when(! empty($grupoIds), fn ($q) => $q->whereIn('grupo_id', $grupoIds))
            ->get();

        if ($asignaciones->isEmpty()) {
            return ['error' => 'No hay asignaciones con horas_semana > 0 para este año escolar. '
                . 'Ve a Asignaciones y configura las horas semanales.'];
        }

        $totalSlots = $asignaciones->sum('horas_semana');

        Log::channel('horario')->info('DATOS cargados', [
            'asignaciones' => $asignaciones->count(),
            'slots_totales'=> $totalSlots,
            'franjas'      => $this->franjas->count(),
            'aulas'        => $this->aulas->count(),
            'maxIter'      => $this->maxIter,
        ]);

        $this->debug('Datos cargados', [
            'asignaciones' => $asignaciones->count(),
            'slots'        => $totalSlots,
        ]);

        // ── 7. Múltiples intentos con distintas semillas; guardar el mejor ────
        $mejorResultado = null;
        $mejorScore     = -1.0;

        for ($semilla = 1; $semilla <= self::MAX_REINTENTOS; $semilla++) {
            $this->debug("=== INTENTO #{$semilla} ===");

            $resultado = $this->intentarGenerar($asignaciones, $semilla);

            Log::channel('horario')->info("INTENTO #{$semilla}", [
                'asignados'       => count($resultado['asignados']),
                'conflictos'      => count($resultado['conflictos']),
                'score'           => $resultado['score'],
                'iteraciones'     => $resultado['iteraciones'],
                'limite_alcanzado'=> $resultado['limite_alcanzado'],
            ]);

            $this->debug("Intento #{$semilla} resultado", [
                'score'      => $resultado['score'],
                'asignados'  => count($resultado['asignados']),
                'conflictos' => count($resultado['conflictos']),
                'iteraciones'=> $resultado['iteraciones'],
            ]);

            if ($resultado['score'] > $mejorScore) {
                $mejorScore     = $resultado['score'];
                $mejorResultado = $resultado;
            }

            if ($mejorScore >= 99.0) {
                $this->debug('Solución perfecta — deteniendo reintentos');
                break;
            }
        }

        // ── 8. Persistir en BD ────────────────────────────────────────────────
        $persistido = $this->persistir($schoolYear, $nombre, $mejorResultado, $existingHorarioId);

        Log::channel('horario')->info('FIN generación', [
            'horario_id' => $persistido['horario_id'] ?? null,
            'score'      => $persistido['score'] ?? null,
            'asignados'  => $persistido['asignados'] ?? null,
            'pendientes' => $persistido['pendientes'] ?? null,
        ]);

        // Adjuntar debug log al resultado si está activo
        $persistido['debug'] = $this->debugMode ? $this->debugLog : [];

        return $persistido;
    }

    // =========================================================================
    //  INTENTO INDIVIDUAL
    // =========================================================================

    /**
     * Un solo ciclo completo: ordenar → backtracking → greedy fallback.
     */
    private function intentarGenerar(Collection $asignaciones, int $semilla): array
    {
        $this->resetState();

        // Construir y ordenar la lista de slots pendientes
        $pendientes = $this->construirPendientes($asignaciones, $semilla);
        $totalSlots = count($pendientes);

        // ── Fase 1: Backtracking puro ─────────────────────────────────────────
        $asignados  = [];
        $exitoTotal = $this->backtrack($pendientes, 0, $asignados);

        if ($exitoTotal) {
            // Solución perfecta
            return [
                'asignados'       => $asignados,
                'conflictos'      => [],
                'score'           => 100.0,
                'iteraciones'     => $this->iteraciones,
                'limite_alcanzado'=> false,
            ];
        }

        // ── Fase 2: Greedy fallback sobre slots no cubiertos ──────────────────
        //
        // Si el backtracking dejó un mejor parcial, lo usamos como punto de
        // partida para el greedy (reconstruyendo el estado correspondiente).
        // Si no hay parcial (falló desde el primer slot), empezamos desde cero.

        if (! empty($this->mejorParcial)) {
            $asignados = $this->mejorParcial;
            $this->reconstruirEstado($asignados, $pendientes);
        } else {
            $asignados = [];
            // El estado ya fue reseteado — los grids están vacíos
        }

        // Identificar qué slot_idx ya están cubiertos
        $slotsCubiertos = [];
        foreach ($asignados as $slot) {
            $slotsCubiertos[$slot['slot_idx']] = true;
        }

        // Greedy para los que faltan
        foreach ($pendientes as $idx => $item) {
            if (isset($slotsCubiertos[$idx])) {
                continue; // ya cubierto por el backtracking
            }

            $slot = $this->asignarGreedy($item, $idx);

            if ($slot) {
                $asignados[]              = $slot;
                $slotsCubiertos[$idx]     = true;
            } else {
                $this->conflictos[] = [
                    'slot_idx'      => $idx,
                    'asignacion_id' => $item['asig']->id,
                    'grupo'         => optional($item['asig']->grupo)->nombre_completo ?? '?',
                    'materia'       => optional($item['asig']->asignatura)->nombre ?? '?',
                    'docente'       => optional($item['asig']->docente)->nombre_completo ?? '?',
                    'razon'         => 'Sin franja disponible tras backtracking + greedy',
                ];
            }
        }

        return [
            'asignados'        => $asignados,
            'conflictos'       => $this->conflictos,
            'score'            => $this->calcularScore(count($asignados), $totalSlots),
            'iteraciones'      => $this->iteraciones,
            'limite_alcanzado' => $this->limitAlcanzado,
        ];
    }

    // =========================================================================
    //  BACKTRACKING
    // =========================================================================

    /**
     * Algoritmo de backtracking recursivo con poda por forward checking.
     *
     * Retorna true si todos los slots pendientes fueron asignados.
     * Retorna false si no hay solución en el camino actual o si se alcanzó
     * el límite de iteraciones (en cuyo caso $limitAlcanzado = true).
     *
     * Durante la ejecución mantiene actualizado $mejorParcial con la asignación
     * más larga alcanzada, para usarla como base en el greedy fallback.
     */
    private function backtrack(array $pendientes, int $idx, array &$asignados): bool
    {
        // ── Caso base: todos asignados ────────────────────────────────────────
        if ($idx >= count($pendientes)) {
            return true;
        }

        // ── Límite de seguridad ───────────────────────────────────────────────
        $this->iteraciones++;
        if ($this->iteraciones > $this->maxIter) {
            $this->limitAlcanzado = true;
            $this->actualizarMejorParcial($asignados);
            $this->debug('Límite de iteraciones alcanzado', ['iter' => $this->iteraciones]);
            return false;
        }

        $item = $pendientes[$idx];
        $asig = $item['asig'];

        // ── Obtener combinaciones (día × franja) ordenadas heurísticamente ───
        $combinaciones = $this->getCombinaciones($asig);

        foreach ($combinaciones as [$dia, $franja]) {
            // ── Verificar todas las restricciones (R1–R7) ─────────────────────
            if (! $this->puedoAsignar($asig, $dia, $franja->id)) {
                continue;
            }

            // ── Elegir aula (R2) ──────────────────────────────────────────────
            $capacidad = $asig->grupo?->capacidad ?? 30;
            $aulaId    = $this->elegirAula($dia, $franja->id, $capacidad);

            // ── Asignar temporalmente ─────────────────────────────────────────
            $this->marcarOcupado($asig, $aulaId, $dia, $franja->id);
            $asignados[] = [
                'slot_idx'      => $idx,
                'asignacion_id' => $asig->id,
                'aula_id'       => $aulaId,
                'franja_id'     => $franja->id,
                'dia'           => $dia,
            ];

            // ── Forward checking: el siguiente slot aún tiene opciones ────────
            $siguiente = $pendientes[$idx + 1] ?? null;
            $hayOpciones = ($siguiente === null)
                || ($this->contarSlotsDisponibles($siguiente['asig']) > 0);

            if ($hayOpciones) {
                if ($this->backtrack($pendientes, $idx + 1, $asignados)) {
                    return true; // ¡Solución completa encontrada!
                }
            }

            // ── Deshacer (backtrack) ──────────────────────────────────────────
            array_pop($asignados);
            $this->desmarcarOcupado($asig, $aulaId, $dia, $franja->id);

            if ($this->limitAlcanzado) {
                return false; // propagar el corte
            }
        }

        // Ninguna combinación funcionó para este slot — guardar mejor parcial
        $this->actualizarMejorParcial($asignados);

        return false; // el llamador probará otra combinación para el slot anterior
    }

    // =========================================================================
    //  GREEDY FALLBACK
    // =========================================================================

    /**
     * Asigna el primer slot válido disponible para el item dado.
     * Se usa solo como fallback cuando el backtracking no pudo cubrir un slot.
     */
    private function asignarGreedy(array $item, int $idx): ?array
    {
        $asig      = $item['asig'];
        $capacidad = $asig->grupo?->capacidad ?? 30;

        foreach ($this->getCombinaciones($asig) as [$dia, $franja]) {
            if (! $this->puedoAsignar($asig, $dia, $franja->id)) {
                continue;
            }

            $aulaId = $this->elegirAula($dia, $franja->id, $capacidad);
            $this->marcarOcupado($asig, $aulaId, $dia, $franja->id);

            return [
                'slot_idx'      => $idx,
                'asignacion_id' => $asig->id,
                'aula_id'       => $aulaId,
                'franja_id'     => $franja->id,
                'dia'           => $dia,
            ];
        }

        return null;
    }

    // =========================================================================
    //  VERIFICACIÓN DE RESTRICCIONES (R1 – R7)
    // =========================================================================

    /**
     * Retorna true si el slot (asig, dia, franja) cumple TODAS las reglas.
     *
     * R1 — Colisión de docente
     * R2 — Colisión de aula (verificado en elegirAula)
     * R3 — Colisión de grupo
     * R5 — Disponibilidad del docente
     * R4/R7 — Límite de horas por día (docente y grupo)
     * R6 — Repetición de materia en el mismo día
     */
    private function puedoAsignar(Asignacion $asig, string $dia, int $franjaId): bool
    {
        $dId = $asig->docente_id;
        $gId = $asig->grupo_id;
        $mId = $asig->asignatura_id;

        // R1: docente libre en ese slot (solo si hay docente asignado)
        if ($dId !== null && ! empty($this->ocupadoDocente[$dId][$dia][$franjaId])) {
            return false;
        }

        // R3: grupo libre en ese slot
        if (! empty($this->ocupadoGrupo[$gId][$dia][$franjaId])) {
            return false;
        }

        // R5: docente disponible ese día y franja (solo si hay docente)
        if ($dId !== null && ! empty($this->noDisponible[$dId][$dia][$franjaId])) {
            return false;
        }

        // R4/R7: docente no supera su máximo de horas en el día (solo si hay docente)
        if ($dId !== null && ($this->clasesHoyDocente[$dId][$dia] ?? 0) >= $this->maxHorasDiaDocente) {
            return false;
        }

        // R4/R7: grupo no supera su máximo de horas en el día
        if (($this->clasesHoyGrupo[$gId][$dia] ?? 0) >= $this->maxHorasDiaGrupo) {
            return false;
        }

        // R6: la misma materia no aparece más de N veces en el mismo día para el grupo
        if (($this->materiaHoyGrupo[$gId][$dia][$mId] ?? 0) >= $this->maxRepeticionMateria) {
            return false;
        }

        return true;
    }

    // =========================================================================
    //  HEURÍSTICA DE COMBINACIONES (R7 — distribución equilibrada)
    // =========================================================================

    /**
     * Devuelve las combinaciones (día, franja) en orden de preferencia:
     *
     * Criterio 1 — Días donde esta materia NO ha aparecido aún (R6, diversidad).
     * Criterio 2 — Días con menos clases para el grupo (semana equilibrada — R7).
     * Criterio 3 — Días con menos clases para el docente (carga equilibrada).
     * Criterio 4 — Franja más temprana (estabilidad en empates).
     */
    private function getCombinaciones(Asignacion $asig): array
    {
        $dId = $asig->docente_id;
        $gId = $asig->grupo_id;
        $mId = $asig->asignatura_id;

        $combinaciones = [];
        foreach ($this->diasLaborales as $dia) {
            foreach ($this->franjas as $franja) {
                $combinaciones[] = [$dia, $franja];
            }
        }

        usort($combinaciones, function (array $a, array $b) use ($dId, $gId, $mId): int {
            [$diaA, $franjaA] = $a;
            [$diaB, $franjaB] = $b;

            // C1: preferir días sin esta materia (distribución)
            $matA = $this->materiaHoyGrupo[$gId][$diaA][$mId] ?? 0;
            $matB = $this->materiaHoyGrupo[$gId][$diaB][$mId] ?? 0;
            if ($matA !== $matB) {
                return $matA <=> $matB;
            }

            // C2: preferir días con menos carga del grupo
            $cargaGrupoA = $this->clasesHoyGrupo[$gId][$diaA] ?? 0;
            $cargaGrupoB = $this->clasesHoyGrupo[$gId][$diaB] ?? 0;
            if ($cargaGrupoA !== $cargaGrupoB) {
                return $cargaGrupoA <=> $cargaGrupoB;
            }

            // C3: preferir días con menos carga del docente (si tiene docente)
            if ($dId !== null) {
                $cargaDocA = $this->clasesHoyDocente[$dId][$diaA] ?? 0;
                $cargaDocB = $this->clasesHoyDocente[$dId][$diaB] ?? 0;
                if ($cargaDocA !== $cargaDocB) {
                    return $cargaDocA <=> $cargaDocB;
                }
            }

            // C4: franja más temprana primero (estabilidad)
            return $franjaA->numero <=> $franjaB->numero;
        });

        return $combinaciones;
    }

    // =========================================================================
    //  CONSTRUCCIÓN Y ORDENAMIENTO MRV DE LOS PENDIENTES
    // =========================================================================

    /**
     * Construye la lista de slots pendientes: un elemento por hora-clase necesaria.
     * Ordena aplicando MRV:
     *   - Primero las asignaciones con menos slots disponibles (más restringidas).
     *   - Dentro del mismo MRV, las de mayor horas_semana (más difíciles de ubicar).
     *   - El parámetro $semilla introduce variedad entre reintentos.
     */
    private function construirPendientes(Collection $asignaciones, int $semilla): array
    {
        // Calcular MRV una vez por asignación única (optimización: O(A × D × F))
        $mrvCache = [];
        foreach ($asignaciones as $asig) {
            $mrvCache[$asig->id] = $this->contarSlotsDisponibles($asig);
        }

        // Ordenar asignaciones
        $lista = $asignaciones->values()->all();
        usort($lista, function (Asignacion $a, Asignacion $b) use ($mrvCache, $semilla): int {
            // MRV: menos slots disponibles primero
            $mrvA = $mrvCache[$a->id] ?? 9999;
            $mrvB = $mrvCache[$b->id] ?? 9999;
            if ($mrvA !== $mrvB) {
                return $mrvA <=> $mrvB;
            }

            // Mayor número de horas primero (harder to place)
            if ($a->horas_semana !== $b->horas_semana) {
                return (int) $b->horas_semana <=> (int) $a->horas_semana;
            }

            // Tie-break con semilla para diversidad entre reintentos
            return (($a->id + $semilla) % 13) <=> (($b->id + $semilla) % 13);
        });

        // Expandir: una entrada por hora-clase necesaria
        $pendientes = [];
        foreach ($lista as $asig) {
            for ($h = 0; $h < (int) $asig->horas_semana; $h++) {
                $pendientes[] = ['asig' => $asig, 'hora_num' => $h];
            }
        }

        return $pendientes;
    }

    /**
     * Cuenta cuántos slots (día × franja) son válidos para una asignación
     * dado el estado actual de los grids de ocupación.
     *
     * Usado para MRV y forward checking.
     */
    private function contarSlotsDisponibles(Asignacion $asig): int
    {
        $count = 0;
        foreach ($this->diasLaborales as $dia) {
            foreach ($this->franjas as $franja) {
                if ($this->puedoAsignar($asig, $dia, $franja->id)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    // =========================================================================
    //  SELECCIÓN DE AULA (R2)
    // =========================================================================

    /**
     * Elige el aula más pequeña disponible cuya capacidad sea suficiente.
     * Si no hay aula con capacidad suficiente, usa cualquier aula libre.
     * Si no hay ninguna, retorna null (la clase se asigna sin aula).
     */
    private function elegirAula(string $dia, int $franjaId, int $capacidadNecesaria): ?int
    {
        // Aulas ya vienen ordenadas por capacidad ASC
        foreach ($this->aulas as $aula) {
            if (empty($this->ocupadoAula[$aula->id][$dia][$franjaId])
                && $aula->capacidad >= $capacidadNecesaria) {
                return $aula->id;
            }
        }

        // Fallback: cualquier aula libre (ignora capacidad)
        foreach ($this->aulas as $aula) {
            if (empty($this->ocupadoAula[$aula->id][$dia][$franjaId])) {
                return $aula->id;
            }
        }

        return null; // sin aula — la clase queda sin sala asignada
    }

    // =========================================================================
    //  GESTIÓN DEL ESTADO (MARCAR / DESMARCAR)
    // =========================================================================

    private function marcarOcupado(Asignacion $asig, ?int $aulaId, string $dia, int $franjaId): void
    {
        $dId = $asig->docente_id;
        $gId = $asig->grupo_id;
        $mId = $asig->asignatura_id;

        if ($dId !== null) {
            $this->ocupadoDocente[$dId][$dia][$franjaId] = true;
            $this->clasesHoyDocente[$dId][$dia]          = ($this->clasesHoyDocente[$dId][$dia] ?? 0) + 1;
        }
        $this->ocupadoGrupo[$gId][$dia][$franjaId]   = true;

        if ($aulaId !== null) {
            $this->ocupadoAula[$aulaId][$dia][$franjaId] = true;
        }

        $this->clasesHoyGrupo[$gId][$dia]        = ($this->clasesHoyGrupo[$gId][$dia] ?? 0) + 1;
        $this->materiaHoyGrupo[$gId][$dia][$mId] = ($this->materiaHoyGrupo[$gId][$dia][$mId] ?? 0) + 1;
    }

    private function desmarcarOcupado(Asignacion $asig, ?int $aulaId, string $dia, int $franjaId): void
    {
        $dId = $asig->docente_id;
        $gId = $asig->grupo_id;
        $mId = $asig->asignatura_id;

        if ($dId !== null) {
            unset($this->ocupadoDocente[$dId][$dia][$franjaId]);
            $this->clasesHoyDocente[$dId][$dia] = max(0, ($this->clasesHoyDocente[$dId][$dia] ?? 1) - 1);
        }
        unset($this->ocupadoGrupo[$gId][$dia][$franjaId]);

        if ($aulaId !== null) {
            unset($this->ocupadoAula[$aulaId][$dia][$franjaId]);
        }

        $this->clasesHoyGrupo[$gId][$dia]        = max(0, ($this->clasesHoyGrupo[$gId][$dia] ?? 1) - 1);
        $this->materiaHoyGrupo[$gId][$dia][$mId] = max(0, ($this->materiaHoyGrupo[$gId][$dia][$mId] ?? 1) - 1);
    }

    /**
     * Reconstruye los grids de ocupación a partir de un conjunto de slots
     * ya asignados. Necesario para continuar desde el mejor parcial del
     * backtracking antes de pasar al greedy.
     */
    private function reconstruirEstado(array $asignados, array $pendientes): void
    {
        $this->ocupadoDocente   = [];
        $this->ocupadoGrupo     = [];
        $this->ocupadoAula      = [];
        $this->clasesHoyDocente = [];
        $this->clasesHoyGrupo   = [];
        $this->materiaHoyGrupo  = [];

        foreach ($asignados as $slot) {
            $item = $pendientes[$slot['slot_idx']] ?? null;
            if ($item === null) {
                continue;
            }
            $this->marcarOcupado($item['asig'], $slot['aula_id'], $slot['dia'], $slot['franja_id']);
        }
    }

    // =========================================================================
    //  HELPERS DE ESTADO
    // =========================================================================

    private function actualizarMejorParcial(array $asignados): void
    {
        if (count($asignados) > $this->mejorParcialCount) {
            $this->mejorParcial      = $asignados;
            $this->mejorParcialCount = count($asignados);
        }
    }

    private function resetState(): void
    {
        $this->ocupadoDocente    = [];
        $this->ocupadoGrupo      = [];
        $this->ocupadoAula       = [];
        $this->clasesHoyDocente  = [];
        $this->clasesHoyGrupo    = [];
        $this->materiaHoyGrupo   = [];
        $this->conflictos        = [];
        $this->iteraciones       = 0;
        $this->limitAlcanzado    = false;
        $this->mejorParcial      = [];
        $this->mejorParcialCount = 0;
    }

    // =========================================================================
    //  CARGA DE DISPONIBILIDAD
    // =========================================================================

    private function cargarNoDisponibilidad(int $schoolYearId): void
    {
        DisponibilidadDocente::where('school_year_id', $schoolYearId)
            ->where('disponible', false)
            ->get()
            ->each(function ($r): void {
                $this->noDisponible[$r->docente_id][$r->dia][$r->franja_id] = true;
            });
    }

    // =========================================================================
    //  SCORE
    // =========================================================================

    /**
     * Score 0–100:
     *   - Base: porcentaje de slots cubiertos.
     *   - Penalización: 2 puntos por cada conflicto no resuelto.
     */
    private function calcularScore(int $asignados, int $total): float
    {
        if ($total === 0) {
            return 100.0;
        }

        $pct          = ($asignados / $total) * 100;
        $penalizacion = count($this->conflictos) * 2;

        return max(0.0, round($pct - $penalizacion, 2));
    }

    // =========================================================================
    //  PERSISTENCIA
    // =========================================================================

    private function persistir(SchoolYear $schoolYear, string $nombre, array $resultado, ?int $existingHorarioId = null): array
    {
        return DB::transaction(function () use ($schoolYear, $nombre, $resultado, $existingHorarioId): array {
            if ($existingHorarioId !== null) {
                // Regenerar: reutilizar el mismo horario, solo reemplazar detalles
                $horario = Horario::findOrFail($existingHorarioId);
                $horario->detalles()->delete();
                $horario->update([
                    'generado_en' => now(),
                    'iteraciones' => $resultado['iteraciones'],
                    'score'       => $resultado['score'],
                    'conflictos'  => $resultado['conflictos'],
                ]);
            } else {
                // Desactivar horario activo anterior del mismo año
                Horario::where('school_year_id', $schoolYear->id)
                    ->where('es_activo', true)
                    ->update(['es_activo' => false]);

                $horario = Horario::create([
                    'school_year_id' => $schoolYear->id,
                    'nombre'         => $nombre,
                    'estado'         => 'borrador',
                    'es_activo'      => true,
                    'generado_en'    => now(),
                    'iteraciones'    => $resultado['iteraciones'],
                    'score'          => $resultado['score'],
                    'conflictos'     => $resultado['conflictos'],
                ]);
            }

            foreach ($resultado['asignados'] as $slot) {
                HorarioDetalle::create([
                    'horario_id'    => $horario->id,
                    'asignacion_id' => $slot['asignacion_id'],
                    'aula_id'       => $slot['aula_id'],
                    'franja_id'     => $slot['franja_id'],
                    'dia'           => $slot['dia'],
                ]);
            }

            Log::channel('horario')->info('PERSISTIDO', [
                'horario_id'       => $horario->id,
                'school_year'      => $schoolYear->nombre,
                'asignados'        => count($resultado['asignados']),
                'conflictos'       => count($resultado['conflictos']),
                'score'            => $horario->score,
                'iteraciones'      => $resultado['iteraciones'],
                'limite_alcanzado' => $resultado['limite_alcanzado'],
            ]);

            return [
                'horario_id' => $horario->id,
                'asignados'  => count($resultado['asignados']),
                'pendientes' => count($resultado['conflictos']),
                'score'      => $horario->score,
                'conflictos' => $resultado['conflictos'],
            ];
        });
    }

    // =========================================================================
    //  DEBUG
    // =========================================================================

    /**
     * Agrega una entrada al log de debug en memoria.
     * Solo activo cuando HORARIO_DEBUG=true.
     * No usa Log::channel para no saturar el log en producción.
     */
    private function debug(string $mensaje, array $contexto = []): void
    {
        if (! $this->debugMode) {
            return;
        }

        $entrada = ['ts' => now()->format('H:i:s.u'), 'msg' => $mensaje];

        if (! empty($contexto)) {
            $entrada['ctx'] = $contexto;
        }

        $this->debugLog[] = $entrada;

        // También vuelca al log dedicado en modo debug
        Log::channel('horario')->debug("[DEBUG] {$mensaje}", $contexto);
    }
}
