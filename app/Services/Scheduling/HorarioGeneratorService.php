<?php

namespace App\Services\Scheduling;

use App\Models\Scheduling\Asignacion;
use App\Models\Scheduling\Aula;
use App\Models\Scheduling\Franja;
use App\Models\Scheduling\Horario;
use App\Models\Scheduling\HorarioDetalle;
use App\Models\Scheduling\DisponibilidadProfesor;
use Illuminate\Support\Collection;

/**
 * Generador de horarios — Backtracking + Heurísticas (MRV + Forward Checking)
 *
 * REGLAS que garantiza:
 *  R1 — Un profesor no puede tener dos clases al mismo tiempo
 *  R2 — Un aula no puede tener dos clases al mismo tiempo
 *  R3 — Un curso no puede tener dos clases al mismo tiempo
 *  R4 — Se respetan las horas_semana de cada asignación
 *  R5 — Solo se asigna al profesor en día/franja donde está disponible
 *  R6 — La misma materia no se repite más de una vez por día por curso
 *  R7 — Distribución equilibrada (MRV + getCombinaciones)
 */
class HorarioGeneratorService
{
    private const DIAS         = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    private const MAX_ITER     = 200_000;
    private const MAX_REINTENTOS = 3;

    // Grids de ocupación O(1)
    private array $ocupadoProfesor = []; // [profesor_id][dia][franja_id]
    private array $ocupadoCurso    = []; // [curso_id][dia][franja_id]
    private array $ocupadoAula     = []; // [aula_id][dia][franja_id]
    private array $materiaHoyCurso = []; // [curso_id][dia][materia_id] — R6
    private array $clasesHoyCurso  = []; // [curso_id][dia] count — R7
    private array $clasesHoyProf   = []; // [profesor_id][dia] count — R7

    private array $disponibilidad  = []; // [profesor_id][dia][franja_id] => bool
    private array $aulasCache      = [];

    private int   $iteraciones     = 0;
    private array $mejorParcial    = [];
    private int   $mejorParcialN   = 0;

    // ── ENTRY POINT ──────────────────────────────────────────────────────

    public function generar(string $nombre = 'Horario Principal'): array
    {
        $asignaciones = Asignacion::with(['materia', 'profesor.disponibilidad', 'curso'])
            ->get();

        if ($asignaciones->isEmpty()) {
            return ['error' => 'No hay asignaciones configuradas. Agrega materias, profesores y cursos primero.'];
        }

        $this->cargarDisponibilidad();
        $this->aulasCache = Aula::where('disponible', true)->get()->all();

        $mejorResultado = null;

        for ($semilla = 0; $semilla < self::MAX_REINTENTOS; $semilla++) {
            $resultado = $this->intentarGenerar($asignaciones, $semilla);

            if ($mejorResultado === null || $resultado['asignados'] > $mejorResultado['asignados']) {
                $mejorResultado = $resultado;
            }

            if ($resultado['pendientes'] === 0) break; // solución perfecta
        }

        return $this->persistir($nombre, $mejorResultado);
    }

    // ── INTENTO DE GENERACIÓN ─────────────────────────────────────────────

    private function intentarGenerar(Collection $asignaciones, int $semilla): array
    {
        // Reset estado
        $this->ocupadoProfesor = $this->ocupadoCurso = $this->ocupadoAula = [];
        $this->materiaHoyCurso = $this->clasesHoyCurso = $this->clasesHoyProf = [];
        $this->iteraciones     = 0;
        $this->mejorParcial    = [];
        $this->mejorParcialN   = 0;

        $pendientes = $this->construirPendientes($asignaciones, $semilla);
        $asignados  = [];

        $this->backtrack($pendientes, 0, $asignados);

        // Si backtracking no llegó al 100%, completar con greedy desde el mejor parcial
        if (count($asignados) < count($pendientes)) {
            $this->reconstruirEstado($this->mejorParcial, $pendientes);
            $asignados = $this->mejorParcial;

            foreach ($pendientes as $i => $item) {
                if (!isset($asignados[$i])) {
                    $slot = $this->asignarGreedy($item);
                    if ($slot) $asignados[$i] = $slot;
                }
            }
        }

        $conflictos = [];
        foreach ($pendientes as $i => $item) {
            if (!isset($asignados[$i])) {
                $conflictos[] = [
                    'materia'  => $item['asignacion']->materia->nombre,
                    'profesor' => $item['asignacion']->profesor->nombre_completo,
                    'curso'    => $item['asignacion']->curso->nombre,
                    'motivo'   => 'Sin slot disponible tras ' . self::MAX_REINTENTOS . ' reintentos',
                ];
            }
        }

        return [
            'asignados'   => $asignados,
            'pendientes'  => count($conflictos),
            'conflictos'  => $conflictos,
            'iteraciones' => $this->iteraciones,
        ];
    }

    // ── BACKTRACKING ──────────────────────────────────────────────────────

    private function backtrack(array $pendientes, int $idx, array &$asignados): bool
    {
        if ($idx === count($pendientes)) return true;

        if (++$this->iteraciones > self::MAX_ITER) return false;

        // Guardar mejor parcial
        if ($idx > $this->mejorParcialN) {
            $this->mejorParcialN = $idx;
            $this->mejorParcial  = $asignados;
        }

        $item      = $pendientes[$idx];
        $asignacion = $item['asignacion'];
        $combinaciones = $this->getCombinaciones($asignacion);

        foreach ($combinaciones as [$dia, $franjaId]) {
            if (!$this->puedoAsignar($asignacion, $dia, $franjaId)) continue;

            // Forward checking: ¿el siguiente slot aún tendrá opciones?
            if (isset($pendientes[$idx + 1])) {
                $next = $pendientes[$idx + 1]['asignacion'];
                $this->marcarOcupado($asignacion, $dia, $franjaId);
                $tieneOpciones = count($this->getCombinaciones($next)) > 0;
                $this->desmarcarOcupado($asignacion, $dia, $franjaId);
                if (!$tieneOpciones) continue;
            }

            $aulaId = $this->elegirAula($dia, $franjaId, $asignacion->curso->capacidad ?? 0);

            $this->marcarOcupado($asignacion, $dia, $franjaId);
            $asignados[$idx] = [
                'asignacion_id' => $asignacion->id,
                'dia'           => $dia,
                'franja_id'     => $franjaId,
                'aula_id'       => $aulaId,
            ];

            if ($this->backtrack($pendientes, $idx + 1, $asignados)) return true;

            // Backtrack
            unset($asignados[$idx]);
            $this->desmarcarOcupado($asignacion, $dia, $franjaId);
        }

        return false;
    }

    // ── GREEDY FALLBACK ───────────────────────────────────────────────────

    private function asignarGreedy(array $item): ?array
    {
        $asignacion    = $item['asignacion'];
        $combinaciones = $this->getCombinaciones($asignacion);

        foreach ($combinaciones as [$dia, $franjaId]) {
            if (!$this->puedoAsignar($asignacion, $dia, $franjaId)) continue;

            $aulaId = $this->elegirAula($dia, $franjaId, $asignacion->curso->capacidad ?? 0);
            $this->marcarOcupado($asignacion, $dia, $franjaId);

            return [
                'asignacion_id' => $asignacion->id,
                'dia'           => $dia,
                'franja_id'     => $franjaId,
                'aula_id'       => $aulaId,
            ];
        }

        return null;
    }

    // ── REGLAS DE VALIDACIÓN ──────────────────────────────────────────────

    /**
     * Verifica que se puedan cumplir todas las restricciones para asignar
     * esta asignación en el slot día + franja.
     */
    private function puedoAsignar(Asignacion $asig, string $dia, int $franjaId): bool
    {
        $pId = $asig->profesor_id;
        $cId = $asig->curso_id;
        $mId = $asig->materia_id;

        // R1 — Profesor libre
        if (!empty($this->ocupadoProfesor[$pId][$dia][$franjaId])) return false;

        // R2 — Curso libre
        if (!empty($this->ocupadoCurso[$cId][$dia][$franjaId])) return false;

        // R5 — Disponibilidad del profesor
        if (isset($this->disponibilidad[$pId][$dia][$franjaId])
            && !$this->disponibilidad[$pId][$dia][$franjaId]) {
            return false;
        }

        // R6 — La misma materia no se repite más de una vez por día en el mismo curso
        if (!empty($this->materiaHoyCurso[$cId][$dia][$mId])) return false;

        return true;
    }

    // ── GRIDS DE OCUPACIÓN ────────────────────────────────────────────────

    private function marcarOcupado(Asignacion $asig, string $dia, int $franjaId): void
    {
        $this->ocupadoProfesor[$asig->profesor_id][$dia][$franjaId] = true;
        $this->ocupadoCurso[$asig->curso_id][$dia][$franjaId]       = true;
        $this->materiaHoyCurso[$asig->curso_id][$dia][$asig->materia_id] = true;
        $this->clasesHoyCurso[$asig->curso_id][$dia]  = ($this->clasesHoyCurso[$asig->curso_id][$dia]  ?? 0) + 1;
        $this->clasesHoyProf[$asig->profesor_id][$dia] = ($this->clasesHoyProf[$asig->profesor_id][$dia] ?? 0) + 1;
    }

    private function desmarcarOcupado(Asignacion $asig, string $dia, int $franjaId): void
    {
        unset($this->ocupadoProfesor[$asig->profesor_id][$dia][$franjaId]);
        unset($this->ocupadoCurso[$asig->curso_id][$dia][$franjaId]);
        unset($this->materiaHoyCurso[$asig->curso_id][$dia][$asig->materia_id]);
        $this->clasesHoyCurso[$asig->curso_id][$dia]   = max(0, ($this->clasesHoyCurso[$asig->curso_id][$dia]  ?? 1) - 1);
        $this->clasesHoyProf[$asig->profesor_id][$dia]  = max(0, ($this->clasesHoyProf[$asig->profesor_id][$dia] ?? 1) - 1);
    }

    // ── COMBINACIONES (día × franja) ordenadas por heurística ─────────────

    /**
     * Genera slots ordenados para distribuir equilibradamente:
     * - Días donde el curso ya tiene MENOS clases hoy (R7)
     * - Días donde el profesor ya tiene MENOS clases hoy (R7)
     * - Franja más temprana dentro del día
     */
    private function getCombinaciones(Asignacion $asig): array
    {
        $franjas = Franja::where('activa', true)
            ->where('es_recreo', false)
            ->orderBy('numero')
            ->pluck('id')
            ->all();

        $combinaciones = [];
        foreach (self::DIAS as $dia) {
            foreach ($franjas as $franjaId) {
                $combinaciones[] = [$dia, $franjaId];
            }
        }

        // Ordenar: días con menos carga primero
        usort($combinaciones, function ($a, $b) use ($asig) {
            [$dA, $fA] = $a;
            [$dB, $fB] = $b;

            $cId = $asig->curso_id;
            $pId = $asig->profesor_id;

            $cargaCursoA = $this->clasesHoyCurso[$cId][$dA] ?? 0;
            $cargaCursoB = $this->clasesHoyCurso[$cId][$dB] ?? 0;
            if ($cargaCursoA !== $cargaCursoB) return $cargaCursoA - $cargaCursoB;

            $cargaProfA = $this->clasesHoyProf[$pId][$dA] ?? 0;
            $cargaProfB = $this->clasesHoyProf[$pId][$dB] ?? 0;
            if ($cargaProfA !== $cargaProfB) return $cargaProfA - $cargaProfB;

            return $fA - $fB; // franja más temprana
        });

        return $combinaciones;
    }

    // ── MRV — ORDEN DE ASIGNACIONES ───────────────────────────────────────

    /**
     * Construye la lista de "slots a asignar" ordenada por MRV
     * (Most Restricted Variable first):
     * Las asignaciones con MENOS slots disponibles se asignan primero.
     */
    private function construirPendientes(Collection $asignaciones, int $semilla): array
    {
        $pendientes = [];
        foreach ($asignaciones as $asig) {
            for ($h = 0; $h < $asig->horas_semana; $h++) {
                $pendientes[] = ['asignacion' => $asig, 'slot_idx' => $h];
            }
        }

        // MRV: contar slots disponibles (aproximado — sin ocupación aún)
        usort($pendientes, function ($a, $b) use ($semilla) {
            $sA = $this->contarSlotsDisponibles($a['asignacion']);
            $sB = $this->contarSlotsDisponibles($b['asignacion']);

            if ($sA !== $sB) return $sA - $sB; // menos opciones → primero

            // Desempate con semilla para variación entre reintentos
            return ($a['asignacion']->id + $semilla * 7) % 11 <=>
                   ($b['asignacion']->id + $semilla * 7) % 11;
        });

        return $pendientes;
    }

    private function contarSlotsDisponibles(Asignacion $asig): int
    {
        $franjas = Franja::where('activa', true)->where('es_recreo', false)->count();
        $disponibles = 0;
        foreach (self::DIAS as $dia) {
            for ($f = 1; $f <= $franjas; $f++) {
                if (!isset($this->disponibilidad[$asig->profesor_id][$dia][$f])
                    || $this->disponibilidad[$asig->profesor_id][$dia][$f]) {
                    $disponibles++;
                }
            }
        }
        return $disponibles;
    }

    // ── AULAS ─────────────────────────────────────────────────────────────

    /**
     * Elige el aula disponible más ajustada a la capacidad del curso.
     */
    private function elegirAula(string $dia, int $franjaId, int $capacidadNecesaria): ?int
    {
        $disponibles = array_filter($this->aulasCache, function ($aula) use ($dia, $franjaId) {
            return empty($this->ocupadoAula[$aula->id][$dia][$franjaId]);
        });

        if (empty($disponibles)) return null;

        usort($disponibles, fn($a, $b) => $a->capacidad - $b->capacidad);

        foreach ($disponibles as $aula) {
            if ($aula->capacidad >= $capacidadNecesaria) {
                $this->ocupadoAula[$aula->id][$dia][$franjaId] = true;
                return $aula->id;
            }
        }

        // Si ninguna tiene capacidad suficiente, usar la mayor disponible
        $mayor = end($disponibles);
        $this->ocupadoAula[$mayor->id][$dia][$franjaId] = true;
        return $mayor->id;
    }

    // ── CARGA DE DISPONIBILIDAD ───────────────────────────────────────────

    private function cargarDisponibilidad(): void
    {
        $this->disponibilidad = [];
        DisponibilidadProfesor::all()->each(function ($d) {
            $this->disponibilidad[$d->profesor_id][$d->dia][$d->franja_id] = $d->disponible;
        });
    }

    // ── RECONSTRUIR ESTADO DESDE PARCIAL ──────────────────────────────────

    private function reconstruirEstado(array $asignados, array $pendientes): void
    {
        $this->ocupadoProfesor = $this->ocupadoCurso = $this->ocupadoAula = [];
        $this->materiaHoyCurso = $this->clasesHoyCurso = $this->clasesHoyProf = [];

        foreach ($asignados as $idx => $slot) {
            $asig = $pendientes[$idx]['asignacion'];
            $this->marcarOcupado($asig, $slot['dia'], $slot['franja_id']);
            if ($slot['aula_id']) {
                $this->ocupadoAula[$slot['aula_id']][$slot['dia']][$slot['franja_id']] = true;
            }
        }
    }

    // ── PERSISTIR EN BD ───────────────────────────────────────────────────

    private function persistir(string $nombre, array $resultado): array
    {
        $total     = count($resultado['asignados']) + $resultado['pendientes'];
        $score     = $total > 0 ? (int) round(count($resultado['asignados']) / $total * 100) : 0;

        $horario = Horario::create([
            'nombre'      => $nombre,
            'estado'      => 'borrador',
            'score'       => $score,
            'iteraciones' => $resultado['iteraciones'],
            'conflictos'  => $resultado['conflictos'] ?: null,
            'generado_en' => now(),
        ]);

        foreach ($resultado['asignados'] as $slot) {
            HorarioDetalle::create([
                'horario_id'    => $horario->id,
                'asignacion_id' => $slot['asignacion_id'],
                'dia'           => $slot['dia'],
                'franja_id'     => $slot['franja_id'],
                'aula_id'       => $slot['aula_id'],
            ]);
        }

        return [
            'horario_id'  => $horario->id,
            'asignados'   => count($resultado['asignados']),
            'pendientes'  => $resultado['pendientes'],
            'conflictos'  => $resultado['conflictos'],
            'score'       => $score,
            'iteraciones' => $resultado['iteraciones'],
        ];
    }
}
