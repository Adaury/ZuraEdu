<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\PuntoEstudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Tarea;
use App\Traits\HasDocenteHoy;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    use HasDocenteHoy;

    /** GET /api/v1/dashboard */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;

        return match (true) {
            in_array($role, ['Administrador','Director','Coordinador Academico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']) => $this->admin($user),
            $user->tieneRolDocente()  => $this->docente($user),
            $role === 'Estudiante'    => $this->estudiante($user),
            $role === 'Representante' => $this->padre($user),
            default => response()->json(['message' => 'Rol no soportado.'], 403),
        };
    }

    private function admin($user)
    {
        $sy = SchoolYear::actual();
        return response()->json([
            'role' => 'admin',
            'school_year' => $sy?->nombre,
            'stats' => [
                'estudiantes' => Estudiante::activos()->count(),
                'docentes'    => Docente::activos()->count(),
                'grupos'      => Grupo::where('activo', true)->when($sy, fn($q) => $q->where('school_year_id', $sy->id))->count(),
            ],
        ]);
    }

    private function docente($user)
    {
        $sy      = SchoolYear::actual();
        $docente = Docente::where('user_id', $user->id)->first();

        $asignacionesModelos = ($docente && $sy)
            ? Asignacion::where('docente_id', $docente->id)->where('school_year_id', $sy->id)->where('activo', true)
                ->with(['grupo.grado','grupo.seccion','asignatura'])->get()
            : collect();

        $asignaciones = $asignacionesModelos
            ->map(fn($a) => ['id' => $a->id, 'asignatura' => $a->asignatura?->nombre, 'grupo' => $a->grupo?->nombre_completo, 'area' => $a->area]);

        $hoy = $docente
            ? $this->datosHoyDocente($docente, $sy, $asignacionesModelos)
            : null;

        return response()->json([
            'role'         => 'docente',
            'nombre'       => $docente ? "{$docente->apellidos}, {$docente->nombres}" : $user->name,
            'school_year'  => $sy?->nombre,
            'asignaciones' => $asignaciones,
            'hoy'          => $hoy,
        ]);
    }

    private function estudiante($user)
    {
        $sy         = SchoolYear::actual();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        $matricula  = $estudiante?->matriculas()
            ->where('estado','activa')->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with(['grupo.grado','grupo.seccion'])->latest()->first();

        $promedio = null;
        $asistencia = null;

        if ($matricula) {
            $notas = Calificacion::where('matricula_id', $matricula->id)->where('publicado', true)->pluck('nota_final');
            $notasA = CalificacionAcademica::where('matricula_id', $matricula->id)->where('publicado', true)->pluck('nota_final');
            $todas = $notas->merge($notasA)->filter();
            $promedio = $todas->count() ? round($todas->avg(), 1) : null;

            $total = Asistencia::where('matricula_id', $matricula->id)->count();
            $pres  = Asistencia::where('matricula_id', $matricula->id)->whereIn('estado',['presente','tardanza'])->count();
            $asistencia = $total > 0 ? round($pres / $total * 100, 1) : null;
        }

        $totalMaterias = $matricula
            ? Asignacion::where('grupo_id', $matricula->grupo_id)
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->where('activo', true)->count()
            : null;

        // Gamificación
        $gamif = null;
        $tieneGamif = !app()->bound('tenant') || (app()->bound('tenant') && app('tenant')?->can('gamificacion'));
        if ($tieneGamif && $matricula) {
            $totalPuntos    = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
            $insigniasCount = InsigniaEstudiante::where('matricula_id', $matricula->id)->count();

            $grupoIds = Matricula::where('grupo_id', $matricula->grupo_id)->where('estado', 'activa')->pluck('id');
            $ranking  = PuntoEstudiante::whereIn('matricula_id', $grupoIds)
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')->orderByDesc('total')->get();

            $posicion = null;
            foreach ($ranking as $idx => $r) {
                if ($r->matricula_id === $matricula->id) { $posicion = $idx + 1; break; }
            }
            if ($posicion === null && (int) $totalPuntos === 0) {
                $posicion = $ranking->count() + 1;
            }

            $gamif = [
                'puntos'       => (int) $totalPuntos,
                'insignias'    => $insigniasCount,
                'posicion'     => $posicion,
                'total_grupo'  => $grupoIds->count(),
            ];
        }

        return response()->json([
            'role'          => 'estudiante',
            'nombre'        => $estudiante ? "{$estudiante->apellidos}, {$estudiante->nombres}" : $user->name,
            'grupo'         => $matricula?->grupo?->nombre_completo,
            'school_year'   => $sy?->nombre,
            'promedio'      => $promedio,
            'pct_asistencia'=> $asistencia,
            'total_materias'=> $totalMaterias,
            'gamificacion'  => $gamif,
        ]);
    }

    /**
     * Bloque "Hoy" por hijo (Carnet+, tareas pendientes, próximo pago) --
     * mismas definiciones que PortalPadreController::dashboard() (web), pero
     * reimplementado aquí porque esa lógica vive inline en un método de
     * >200 líneas, no en helpers reutilizables. Mismo patrón bulk-query
     * (sin N+1) para todos los hijos a la vez.
     */
    private function datosHoyPadre($hijosBase, ?int $syId): \Illuminate\Support\Collection
    {
        $matriculaIds = $hijosBase->flatMap(fn($e) => $e->matriculas->pluck('id'))->filter()->unique()->values();

        $userIdsHijos = $hijosBase->pluck('user_id')->filter()->unique();
        $carnetsPorUserId = $userIdsHijos->isNotEmpty()
            ? CarnetIdentidad::whereIn('user_id', $userIdsHijos)->where('tipo', 'estudiante')->get()->keyBy('user_id')
            : collect();
        $carnetIds = $carnetsPorUserId->pluck('id');
        $accesosHoyPorCarnet = $carnetIds->isNotEmpty()
            ? CarnetAcceso::whereIn('carnet_identidad_id', $carnetIds)->hoy()->orderByDesc('created_at')->get()->groupBy('carnet_identidad_id')
            : collect();

        $grupoIdsHijos = $hijosBase->flatMap(fn($e) => $e->matriculas->pluck('grupo_id'))->filter()->unique()->values();
        $asignacionesPorGrupo = $grupoIdsHijos->isNotEmpty()
            ? Asignacion::whereIn('grupo_id', $grupoIdsHijos)->where('activo', true)
                ->when($syId, fn($q) => $q->where('school_year_id', $syId))->get()->groupBy('grupo_id')
            : collect();
        $todasAsignacionIds = $asignacionesPorGrupo->flatten(1)->pluck('id');
        $tareasPorAsignacion = $todasAsignacionIds->isNotEmpty()
            ? Tarea::whereIn('asignacion_id', $todasAsignacionIds)->where('activo', true)->where('fecha_limite', '>=', today())
                ->get()->groupBy('asignacion_id')
            : collect();
        $todasTareaIds = $tareasPorAsignacion->flatten(1)->pluck('id');
        $entregasPorTareaYEstudiante = $todasTareaIds->isNotEmpty()
            ? EntregaTarea::whereIn('tarea_id', $todasTareaIds)->whereIn('estudiante_id', $hijosBase->pluck('id'))
                ->get()->groupBy(fn($e) => "{$e->tarea_id}_{$e->estudiante_id}")
            : collect();

        $proximosPagosPorMatricula = $matriculaIds->isNotEmpty()
            ? Pago::whereIn('matricula_id', $matriculaIds)->where('estado', 'pendiente')->orderBy('fecha_vencimiento')
                ->get()->groupBy('matricula_id')
            : collect();

        return $hijosBase->mapWithKeys(function ($estudiante) use (
            $carnetsPorUserId, $accesosHoyPorCarnet, $asignacionesPorGrupo,
            $tareasPorAsignacion, $entregasPorTareaYEstudiante, $proximosPagosPorMatricula
        ) {
            $matricula = $estudiante->matriculas->first();

            $carnet    = $carnetsPorUserId->get($estudiante->user_id);
            $carnetHoy = $carnet ? $accesosHoyPorCarnet->get($carnet->id, collect())->first() : null;

            $tareasPendientes = 0;
            if ($matricula) {
                foreach ($asignacionesPorGrupo->get($matricula->grupo_id, collect()) as $asig) {
                    foreach ($tareasPorAsignacion->get($asig->id, collect()) as $tarea) {
                        if (! $entregasPorTareaYEstudiante->has("{$tarea->id}_{$estudiante->id}")) {
                            $tareasPendientes++;
                        }
                    }
                }
            }

            $proximoPago = $matricula ? $proximosPagosPorMatricula->get($matricula->id, collect())->first() : null;

            return [$estudiante->id => [
                'carnet_hoy' => $carnetHoy ? [
                    'tipo'  => $carnetHoy->tipo_evento,
                    'hora'  => $carnetHoy->created_at->format('H:i'),
                ] : null,
                'tareas_pendientes' => $tareasPendientes,
                'proximo_pago' => $proximoPago ? [
                    'concepto'          => $proximoPago->concepto,
                    'monto'             => (float) $proximoPago->monto,
                    'fecha_vencimiento' => optional($proximoPago->fecha_vencimiento)->toDateString(),
                ] : null,
            ]];
        });
    }

    private function padre($user)
    {
        $sy   = SchoolYear::actual();
        $syId = $sy?->id;
        $rep  = Representante::where('user_id', $user->id)->first();

        $hijosBase = $rep
            ? $rep->estudiantes()->with(['matriculas' => fn($q) => $q->where('estado','activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $syId))->with(['grupo.grado','grupo.seccion'])])->get()
            : collect();

        $hoyPorHijo = $rep ? $this->datosHoyPadre($hijosBase, $syId) : collect();

        $hijos = $hijosBase->map(fn($e) => [
            'id'     => $e->id,
            'nombre' => "{$e->nombres} {$e->apellidos}",
            'grupo'  => $e->matriculas->first()?->grupo?->nombre_completo,
            'hoy'    => $hoyPorHijo->get($e->id),
        ]);

        return response()->json([
            'role'        => 'padre',
            'nombre'      => $rep?->nombres ?? $user->name,
            'school_year' => $sy?->nombre,
            'hijos'       => $hijos,
        ]);
    }
}
