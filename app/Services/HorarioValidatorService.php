<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Aula;
use App\Models\FranjaHoraria;
use App\Models\Grupo;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\Log;

/**
 * Validador previo al generador de horarios.
 *
 * Comprueba que el sistema tenga los datos mínimos necesarios antes
 * de lanzar el algoritmo de backtracking. Cada check retorna errores
 * bloqueantes o advertencias no bloqueantes con mensajes accionables.
 *
 * Uso:
 *   $v = (new HorarioValidatorService)->validar($schoolYearId, $grupoIds);
 *   if (! $v['valido']) { return error($v['errores']); }
 */
class HorarioValidatorService
{
    private array $errores      = [];
    private array $advertencias = [];
    private array $stats        = [];

    // =========================================================================
    //  API PÚBLICA
    // =========================================================================

    /**
     * @return array{
     *   valido: bool,
     *   errores: string[],
     *   advertencias: string[],
     *   stats: array,
     *   sugerencias: string[]
     * }
     */
    public function validar(?int $schoolYearId = null, array $grupoIds = []): array
    {
        $this->errores      = [];
        $this->advertencias = [];
        $this->stats        = [];

        // ── Año escolar ──────────────────────────────────────────────────────
        $schoolYear = $schoolYearId
            ? SchoolYear::find($schoolYearId)
            : SchoolYear::actual();

        if (! $schoolYear) {
            $this->errores[] = 'No hay año escolar activo. Ve a Configuración → Años Escolares y activa uno.';
            return $this->resultado();
        }

        $this->stats['school_year']    = $schoolYear->nombre;
        $this->stats['school_year_id'] = $schoolYear->id;

        // ── Check 1: Grupos ───────────────────────────────────────────────────
        $this->checkGrupos($schoolYear->id, $grupoIds);

        // ── Check 2: Franjas horarias ─────────────────────────────────────────
        $franjas = $this->checkFranjas();

        // ── Check 3: Aulas disponibles ────────────────────────────────────────
        $aulas = $this->checkAulas();

        // ── Check 4: Asignaciones con horas ───────────────────────────────────
        $asignaciones = $this->checkAsignaciones($schoolYear->id, $grupoIds);

        // ── Check 5: Viabilidad matemática ───────────────────────────────────
        if ($franjas > 0 && $aulas > 0 && $asignaciones !== null) {
            $this->checkViabilidad($asignaciones, $franjas, $aulas);
        }

        Log::channel('horario')->info('PRE-VALIDACIÓN completada', [
            'school_year'  => $schoolYear->nombre,
            'stats'        => $this->stats,
            'errores'      => count($this->errores),
            'advertencias' => count($this->advertencias),
        ]);

        return $this->resultado();
    }

    // =========================================================================
    //  CHECKS INDIVIDUALES
    // =========================================================================

    private function checkGrupos(int $schoolYearId, array $grupoIds): void
    {
        $q = Grupo::where('school_year_id', $schoolYearId);
        if (! empty($grupoIds)) {
            $q->whereIn('id', $grupoIds);
        }

        $count = $q->count();
        $this->stats['grupos'] = $count;

        if ($count === 0) {
            $this->errores[] = empty($grupoIds)
                ? 'No hay grupos (secciones) registrados para el año escolar activo. '
                    . 'Ve a Configuración → Grupos y crea al menos uno.'
                : 'Ninguno de los grupos seleccionados existe en el año escolar activo.';
        }
    }

    private function checkFranjas(): int
    {
        $count = FranjaHoraria::where('activa', true)->where('es_recreo', false)->count();
        $this->stats['franjas_activas'] = $count;

        if ($count === 0) {
            $this->errores[] = 'No hay franjas horarias activas. '
                . 'Ve a Horarios → Franjas Horarias y activa al menos una franja lectiva.';
        } elseif ($count < 3) {
            $this->advertencias[] = "Solo hay {$count} franja(s) horaria(s) activa(s). "
                . 'Con tan pocas franjas puede ser difícil ubicar todas las materias.';
        }

        return $count;
    }

    private function checkAulas(): int
    {
        $count = Aula::where('disponible', true)->count();
        $this->stats['aulas_disponibles'] = $count;

        if ($count === 0) {
            $this->errores[] = 'No hay aulas disponibles. '
                . 'Ve a Horarios → Aulas y marca al menos una como disponible.';
        }

        return $count;
    }

    private function checkAsignaciones(int $schoolYearId, array $grupoIds): ?\Illuminate\Support\Collection
    {
        $q = Asignacion::with(['asignatura', 'docente', 'grupo'])
            ->where('school_year_id', $schoolYearId)
            ->where('activo', true)
            ->whereNotNull('horas_semana')
            ->where('horas_semana', '>', 0);

        if (! empty($grupoIds)) {
            $q->whereIn('grupo_id', $grupoIds);
        }

        $asignaciones = $q->get();
        $this->stats['asignaciones'] = $asignaciones->count();

        if ($asignaciones->isEmpty()) {
            $this->errores[] = 'No hay asignaciones con horas_semana > 0. '
                . 'Ve a Asignaciones y configura las horas semanales para cada materia.';
            return null;
        }

        // Docentes sin asignar — advertencia (no bloquea la generación)
        $sinDocente = $asignaciones->filter(fn ($a) => is_null($a->docente_id));
        if ($sinDocente->isNotEmpty()) {
            $nombres = $sinDocente->take(3)->map(
                fn ($a) => '"'.$a->asignatura?->nombre.'" ('.$a->grupo?->nombre_completo.')'
            )->implode(', ');
            $resto = $sinDocente->count() > 3 ? ' y '.($sinDocente->count() - 3).' más' : '';
            $this->advertencias[] = sprintf(
                '%d materia(s) sin docente asignado: %s%s. El horario se generará pero esas celdas quedarán sin docente.',
                $sinDocente->count(),
                $nombres,
                $resto
            );
        }

        // Horas_semana excesivas por asignación individual
        $franjas = $this->stats['franjas_activas'] ?? 0;
        if ($franjas > 0) {
            foreach ($asignaciones as $a) {
                $maxPosible = $franjas * 5; // franjas × días
                if ($a->horas_semana > $maxPosible) {
                    $this->errores[] = sprintf(
                        '"%s" (grupo %s) requiere %d h/sem pero solo existen %d franjas × 5 días = %d slots posibles. '
                            . 'Reduce las horas o agrega más franjas.',
                        $a->asignatura?->nombre ?? '?',
                        $a->grupo?->nombre_completo ?? '?',
                        $a->horas_semana,
                        $franjas,
                        $maxPosible
                    );
                }
            }
        }

        $this->stats['total_horas_requeridas'] = (int) $asignaciones->sum('horas_semana');

        return $asignaciones;
    }

    private function checkViabilidad(\Illuminate\Support\Collection $asignaciones, int $franjas, int $aulas): void
    {
        $totalHoras      = (int) $asignaciones->sum('horas_semana');
        $slotsDisponibles = $franjas * 5 * $aulas; // franjas × días × aulas
        $this->stats['slots_disponibles'] = $slotsDisponibles;

        if ($totalHoras > $slotsDisponibles) {
            $this->advertencias[] = sprintf(
                'Se requieren %d horas/semana totales pero hay %d slots disponibles '
                    . '(%d franjas × 5 días × %d aulas). '
                    . 'Algunas clases podrían quedar sin ubicar.',
                $totalHoras,
                $slotsDisponibles,
                $franjas,
                $aulas
            );
        }

        // Grupos con muchas materias por día
        $porGrupo = $asignaciones->groupBy('grupo_id');
        foreach ($porGrupo as $grupoId => $asigs) {
            $totalGrupo = (int) $asigs->sum('horas_semana');
            $maxDia     = $franjas; // máximo slots por día para un grupo
            $promDia    = round($totalGrupo / 5, 1);

            if ($promDia > $maxDia) {
                $nombre = $asigs->first()?->grupo?->nombre_completo ?? "Grupo {$grupoId}";
                $this->advertencias[] = sprintf(
                    'El grupo "%s" tiene %.1f h/día en promedio pero solo hay %d franjas. '
                        . 'Considera reducir la carga o añadir más franjas.',
                    $nombre,
                    $promDia,
                    $maxDia
                );
            }
        }
    }

    // =========================================================================
    //  RESULTADO
    // =========================================================================

    private function resultado(): array
    {
        $sugerencias = [];

        if (! empty($this->errores)) {
            $sugerencias[] = 'Corrige los errores indicados antes de generar el horario.';
        }

        if (! empty($this->advertencias)) {
            $sugerencias[] = 'Revisa las advertencias — el horario puede generarse pero con conflictos.';
        }

        if (in_array(true, array_map(
            fn ($e) => str_contains($e, 'docente'),
            $this->errores
        ), true)) {
            $sugerencias[] = 'Ve a Asignaciones y asigna un docente a cada materia pendiente.';
        }

        return [
            'valido'       => empty($this->errores),
            'errores'      => $this->errores,
            'advertencias' => $this->advertencias,
            'stats'        => $this->stats,
            'sugerencias'  => array_values(array_unique($sugerencias)),
        ];
    }
}
