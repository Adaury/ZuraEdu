<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Support\Collection;

/**
 * Acta Final de Calificaciones (formato oficial MINERD) y bloque de
 * Competencias Fundamentales del Boletín — Primer Ciclo de Secundaria.
 *
 * Única fuente de esta agregación para que Acta (admin + portal docente) y
 * el Boletín consuman exactamente lo mismo. Consume CalificacionAcademica
 * tal cual está persistida (nota_final/nota_completiva/nota_extraordinaria/
 * situacion ya calculados por CalificacionAcademica::recalcularPromedios())
 * -- no reimplementa esa fórmula, que ya está duplicada 5 veces en el resto
 * del sistema con umbrales inconsistentes (60/65/70/2.5/75).
 */
class ActaFinalService
{
    /**
     * ¿Puede el usuario autenticado ver el Acta Final / boletín-competencias
     * de este grupo? Mismo criterio que BoletinController::puedeVerGrupo():
     * roles de dirección/coordinación ven cualquier grupo; un docente solo
     * si es tutor o tiene una asignación activa en el grupo.
     */
    public function puedeVerGrupo(Grupo $grupo): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole([
            'Administrador', 'Director', 'Coordinador Académico',
            'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo',
            'Secretaría', 'Secretaria Docente', 'Personal Administrativo',
            'Encargado de Área',
        ])) {
            return true;
        }

        if (! $user->tieneRolDocente()) {
            return false;
        }

        $docente = Docente::where('user_id', $user->id)->first()
            ?? Docente::where('email', $user->email)->first();

        if (! $docente) {
            return false;
        }

        if ($grupo->tutor_id === $user->id) {
            return true;
        }

        return Asignacion::where('grupo_id', $grupo->id)
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->exists();
    }

    /**
     * Matriz para el Acta Final: asignaturas del grupo (columnas dinámicas,
     * según lo que el grupo realmente tiene asignado ese año -- no depende
     * de ningún catálogo de "cantidad de materias por ciclo") × estudiantes
     * (filas), con Final de Año / Completivo / Extraordinario / Especial
     * por asignatura, y Situación Final consolidada por estudiante.
     */
    public function construirActaGrupo(Grupo $grupo, SchoolYear $schoolYear): array
    {
        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($a) => $a->asignatura?->nombre ?? '')
            ->values();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->whereIn('estado', ['activa', 'promovida', 'no_promovida'])
            ->orderBy('numero_orden')
            ->get();

        $calificaciones = CalificacionAcademica::delGrupoEnAno($grupo->id, $schoolYear->id)
            ->get()
            ->groupBy('matricula_id');

        $filas = [];
        foreach ($matriculas as $idx => $matricula) {
            $porAsignacion = [];
            $situaciones   = [];

            foreach ($asignaciones as $asig) {
                $cal = ($calificaciones->get($matricula->id) ?? collect())
                    ->firstWhere('asignacion_id', $asig->id);

                $porAsignacion[$asig->id] = [
                    // "Completivo"/"Extraordinario" en el Acta son la nota
                    // CRUDA del examen (nota_cc/nota_ce), no el resultado
                    // ponderado (nota_completiva/nota_extraordinaria) -- ese
                    // cálculo solo se usa internamente para decidir A/R.
                    'final_ano'     => $cal?->nota_final,
                    'completivo'    => $cal?->nota_cc,
                    'extraordinario'=> $cal?->nota_ce,
                    'especial'      => $cal?->eval_ce ?? $cal?->eval_cf,
                    'situacion'     => $cal?->situacion,
                ];

                if ($cal?->situacion) {
                    $situaciones[] = $cal->situacion;
                }
            }

            // Promovido solo si TODAS las asignaturas con nota están en 'A'.
            // Si falta alguna asignatura sin situación (sin notas aún), no
            // se marca ninguna casilla (pendiente) -- nunca se infiere.
            $situacionFinal = match (true) {
                count($situaciones) < $asignaciones->count() => null,
                in_array('R', $situaciones, true)             => 'R',
                default                                        => 'A',
            };

            $filas[] = [
                'orden'           => $idx + 1,
                'matricula'       => $matricula,
                'asignaturas'     => $porAsignacion,
                'situacion_final' => $situacionFinal, // 'A' | 'R' | null
            ];
        }

        return compact('asignaciones', 'filas');
    }

    /**
     * Datos de Competencias Fundamentales de UN estudiante (todas sus
     * asignaturas del año), para el bloque del Boletín. Cero cálculo nuevo
     * -- expone tal cual lo que CalificacionAcademica ya tiene persistido.
     */
    public function construirBoletinCompetencias(Matricula $matricula, SchoolYear $schoolYear): Collection
    {
        return CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('asignacion', fn ($q) => $q->where('activo', true))
            ->get()
            ->sortBy(fn ($c) => $c->asignacion?->asignatura?->nombre ?? '')
            ->values();
    }
}
