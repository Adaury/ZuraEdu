<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FranjaHoraria;
use App\Models\Grupo;
use App\Models\Asignatura;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\Planificacion;
use App\Models\PlanClase;
use App\Models\SchoolYear;
use App\Models\AlertaSistema;
use App\Models\ClaseVirtual;
use App\Models\EntregaClassroom;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $user       = auth()->user();

        // ── Stats comunes (cached 5 min — counts don't change per-request) ──
        $syId = $schoolYear?->id ?? 0;
        $totalEstudiantes = Cache::remember('dashboard_total_estudiantes', 300, fn () => Estudiante::activos()->count());
        $totalDocentes    = Cache::remember('dashboard_total_docentes', 300, fn () => Docente::activos()->count());
        $totalGrupos      = Cache::remember("dashboard_total_grupos_{$syId}", 300, fn () =>
            Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $syId))->count()
        );
        $totalAsignaturas = Cache::remember('dashboard_total_asignaturas', 300, fn () => Asignatura::activas()->count());

        $docentePanel = null;
        $isDocente    = $user->hasRole('Docente');

        // ── Horario publicado activo ───────────────────────────────────────
        $horarioActivo = $schoolYear
            ? Cache::remember("dashboard_horario_activo_{$syId}", 300, fn() =>
                Horario::where('school_year_id', $syId)->where('estado', 'publicado')->latest()->first()
              )
            : null;

        // ── Panel específico para Docente ─────────────────────────────────
        if ($isDocente) {
            $docente = Docente::where('user_id', $user->id)->first();

            if ($docente && $schoolYear) {
                $asignacionesDocente = Asignacion::where('docente_id', $docente->id)
                    ->where('school_year_id', $schoolYear->id)
                    ->where('activo', true)
                    ->with(['grupo.grado', 'grupo.seccion', 'asignatura'])
                    ->get();

                // Notas pendientes — bulk query instead of N exists() calls
                $asignacionIds = $asignacionesDocente->pluck('id');
                $asignacionesConPendientes = CalificacionAcademica::whereIn('asignacion_id', $asignacionIds)
                    ->where('school_year_id', $schoolYear->id)
                    ->where('publicado', false)
                    ->distinct()
                    ->pluck('asignacion_id');
                $notasPendientes = $asignacionesConPendientes->count();

                // Períodos que cierran en los próximos 7 días
                $periodosCerrando = Periodo::where('school_year_id', $schoolYear->id)
                    ->where('cerrado', false)
                    ->whereBetween('fecha_fin', [now(), now()->addDays(7)])
                    ->get();

                // Alertas recientes del docente
                $alertasRecientes = AlertaSistema::noLeidas()
                    ->vigentes()
                    ->paraUsuario($user->id, 'Docente')
                    ->latest()
                    ->take(5)
                    ->get();

                // Horario personal del docente (si hay uno publicado)
                $horarioDocente   = collect();
                $franjasHorario   = collect();
                if ($horarioActivo) {
                    $horarioDocente = HorarioDetalle::with(['asignacion.asignatura', 'asignacion.grupo.grado', 'asignacion.grupo.seccion', 'franja', 'aula'])
                        ->where('horario_id', $horarioActivo->id)
                        ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id))
                        ->get();
                    $franjasHorario = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
                }

                $docentePanel = compact(
                    'docente', 'asignacionesDocente',
                    'notasPendientes', 'periodosCerrando', 'alertasRecientes',
                    'horarioDocente', 'franjasHorario'
                );
            }
        }

        // Estadísticas de planificación y observaciones (solo para no-docentes)
        $statsExtra = [];
        if (!$isDocente && $schoolYear) {
            $statsExtra = Cache::remember("dashboard_stats_extra_{$syId}", 300, function () use ($syId) {
                $planificacionesCount = Planificacion::whereHas('asignacion',
                    fn($q) => $q->where('school_year_id', $syId))->count();
                $planesClaseCount = PlanClase::where('school_year_id', $syId)->count();
                $observacionesCount = Observacion::whereHas('asignacion',
                    fn($q) => $q->where('school_year_id', $syId))->count();
                return [
                    'planificaciones' => $planificacionesCount,
                    'planes_clase'    => $planesClaseCount,
                    'observaciones'   => $observacionesCount,
                ];
            });
        }

        // Datos para gráficas (solo no-docentes)
        $chartData = null;
        if (!$isDocente && $schoolYear) {
            $chartData = Cache::remember("dashboard_chart_{$syId}", 300, function () use ($syId) {
                // Matrículas por grado
                $porGrado = Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
                    ->join('grados', 'grupos.grado_id', '=', 'grados.id')
                    ->where('matriculas.school_year_id', $syId)
                    ->where('matriculas.estado', 'activa')
                    ->selectRaw('grados.nombre as grado, COUNT(*) as total')
                    ->groupBy('grados.nombre')
                    ->orderBy('grados.nombre')
                    ->pluck('total', 'grado');

                // Asistencia global del mes actual
                $mesActual = now()->month;
                $asistenciaMes = \App\Models\Asistencia::whereMonth('fecha', $mesActual)
                    ->selectRaw("estado, COUNT(*) as total")
                    ->groupBy('estado')
                    ->pluck('total', 'estado');

                return [
                    'porGrado'      => $porGrado,
                    'asistenciaMes' => $asistenciaMes,
                ];
            });
        }

        // Stats de pagos (solo si módulo activo y usuario admin/director)
        $statsPagos = null;
        if (!$isDocente && \App\Models\ConfigInstitucional::moduloActivo('pagos') && $schoolYear) {
            \App\Models\Pago::sincronizarVencidos();
            $statsPagos = Cache::remember("dashboard_stats_pagos_{$syId}", 180, function () use ($syId) {
                $base = \App\Models\Pago::whereHas('matricula',
                    fn($m) => $m->where('school_year_id', $syId));
                return [
                    'cobrado'   => (clone $base)->where('estado', 'pagado')->sum('monto'),
                    'pendiente' => (clone $base)->where('estado', 'pendiente')->sum('monto'),
                    'vencido'   => (clone $base)->where('estado', 'vencido')->sum('monto'),
                    'deudores'  => (clone $base)->where('estado', 'vencido')
                                                ->distinct('matricula_id')->count('matricula_id'),
                ];
            });
        }

        // ── ZuraClass widget ──────────────────────────────────────────────
        $zuraClassData = null;
        if ($schoolYear) {
            if ($isDocente) {
                $docente = $docente ?? Docente::where('user_id', $user->id)->first();
                if ($docente) {
                    $misClases = ClaseVirtual::with(['asignacion.asignatura', 'asignacion.grupo'])
                        ->whereHas('asignacion', fn($q) =>
                            $q->where('docente_id', $docente->id)
                              ->where('school_year_id', $schoolYear->id)
                              ->where('activo', true)
                        )
                        ->where('activo', true)
                        ->latest()
                        ->take(4)
                        ->get();

                    // Entregas pendientes de calificar en todas mis clases
                    $entregasPendientes = EntregaClassroom::whereHas('material.claseVirtual.asignacion', fn($q) =>
                            $q->where('docente_id', $docente->id)
                              ->where('school_year_id', $schoolYear->id)
                        )
                        ->where('estado', 'entregado')
                        ->count();

                    $zuraClassData = [
                        'clases'             => $misClases,
                        'entregasPendientes' => $entregasPendientes,
                        'totalClases'        => $misClases->count(),
                    ];
                }
            } else {
                $totalClasesActivas = Cache::remember("dashboard_zura_clases_{$syId}", 300, fn() =>
                    ClaseVirtual::whereHas('asignacion', fn($q) =>
                        $q->where('school_year_id', $schoolYear->id)->where('activo', true)
                    )->where('activo', true)->count()
                );
                $totalEntregasPend  = Cache::remember("dashboard_zura_entregas_{$syId}", 120, fn() =>
                    EntregaClassroom::whereHas('material.claseVirtual.asignacion', fn($q) =>
                        $q->where('school_year_id', $schoolYear->id)
                    )->where('estado', 'entregado')->count()
                );
                $zuraClassData = [
                    'totalClasesActivas'  => $totalClasesActivas,
                    'totalEntregasPend'   => $totalEntregasPend,
                ];
            }
        }

        // Alertas académicas recientes no leídas (solo admin/director)
        $alertasAcad = null;
        if (!$isDocente && $schoolYear) {
            $user  = auth()->user();
            $roles = $user->getRoleNames()->toArray();
            $alertasAcad = AlertaSistema::where(function ($q) use ($user, $roles) {
                    $q->where('destinatario_id', $user->id)
                      ->orWhereIn('destinatario_rol', $roles)
                      ->orWhereNull('destinatario_id');
                })
                ->whereIn('tipo', ['academica', 'asistencia'])
                ->where('leida', false)
                ->latest()
                ->limit(6)
                ->get();
        }

        return view('admin.dashboard', compact(
            'schoolYear',
            'totalEstudiantes',
            'totalDocentes',
            'totalGrupos',
            'totalAsignaturas',
            'docentePanel',
            'isDocente',
            'horarioActivo',
            'statsExtra',
            'statsPagos',
            'chartData',
            'alertasAcad',
            'zuraClassData'
        ));
    }

    /**
     * AJAX — devuelve stats actualizadas (sin caché) para el botón "Actualizar".
     */
    public function statsJson()
    {
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        // Flush caché y recalcular
        Cache::forget('dashboard_total_estudiantes');
        Cache::forget('dashboard_total_docentes');
        Cache::forget("dashboard_total_grupos_{$syId}");
        Cache::forget('dashboard_total_asignaturas');
        Cache::forget("dashboard_stats_extra_{$syId}");

        return response()->json([
            'totalEstudiantes'  => Estudiante::activos()->count(),
            'totalDocentes'     => Docente::activos()->count(),
            'totalGrupos'       => Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $syId))->count(),
            'totalAsignaturas'  => Asignatura::activas()->count(),
            'matriculasActivas' => Matricula::where('estado','activa')
                                    ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
                                    ->count(),
            'planificaciones'   => $syId ? Planificacion::whereHas('asignacion',
                fn($q) => $q->where('school_year_id', $syId))->count() : 0,
            'observaciones'     => $syId ? Observacion::whereHas('asignacion',
                fn($q) => $q->where('school_year_id', $syId))->count() : 0,
            'updatedAt'         => now()->format('d/m/Y H:i:s'),
        ]);
    }
}
