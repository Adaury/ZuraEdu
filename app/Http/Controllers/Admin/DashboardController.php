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
use App\Models\Evento;
use App\Models\FaltaDisciplinaria;
use App\Models\IncidenteMedico;
use App\Models\Matricula;
use App\Models\PreMatricula;
use App\Models\Reunion;
use App\Models\RutaTransporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $user       = auth()->user();

        // ── Stats comunes (cached 5 min — tenant-scoped keys) ──────────────
        $syId = $schoolYear?->id ?? 0;
        $tid  = tenant_id();
        $totalEstudiantes = Cache::remember("t{$tid}_dashboard_estudiantes", 300, fn () => Estudiante::activos()->count());
        $totalDocentes    = Cache::remember("t{$tid}_dashboard_docentes", 300, fn () => Docente::activos()->count());
        $totalGrupos      = Cache::remember("t{$tid}_dashboard_grupos_{$syId}", 300, fn () =>
            Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $syId))->count()
        );
        $totalAsignaturas = Cache::remember("t{$tid}_dashboard_asignaturas", 300, fn () => Asignatura::activas()->count());
        $matriculasActivas = Cache::remember("t{$tid}_dashboard_matriculas_{$syId}", 300, fn () =>
            Matricula::where('estado', 'activa')->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))->count()
        );

        $docentePanel = null;
        $isDocente    = $user->hasAnyRole(['Docente', 'Docente Académico', 'Docente Técnico', 'Docente Guía']);

        // ── Rol del dashboard ────────────────────────────────────────────────
        $rolDashboard = match(true) {
            $user->hasRole('Caja / Finanzas')                                                   => 'caja',
            $user->hasAnyRole(['Secretaría', 'Secretaria Docente', 'Secretaria'])               => 'secretaria',
            $user->hasAnyRole(['Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo']) => 'coordinador',
            $user->hasAnyRole(['Registrador Académico', 'Encargado de Registro Académico'])     => 'registro',
            $user->hasRole('Biblioteca')                                                        => 'biblioteca',
            $user->hasRole('Recepción')                                                         => 'recepcion',
            default                                                                              => 'admin',
        };

        // ── Horario publicado activo ───────────────────────────────────────
        $horarioActivo = $schoolYear
            ? Cache::remember("t{$tid}_dashboard_horario_{$syId}", 300, fn() =>
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

        // ── Stats específicas por rol ─────────────────────────────────────────

        // Caja: stats financieras prominentes
        $statsCaja = null;
        if ($rolDashboard === 'caja' && \App\Models\ConfigInstitucional::moduloActivo('pagos') && $schoolYear) {
            Cache::remember("t{$tid}_pagos_sync_lock", 3600, function () {
                \App\Models\Pago::sincronizarVencidos(); return true;
            });
            $statsCaja = Cache::remember("t{$tid}_dashboard_caja_{$syId}", 180, function () use ($syId) {
                $base = \App\Models\Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $syId));
                return [
                    'cobrado'        => (clone $base)->where('estado', 'pagado')->sum('monto'),
                    'pendiente'      => (clone $base)->where('estado', 'pendiente')->sum('monto'),
                    'vencido'        => (clone $base)->where('estado', 'vencido')->sum('monto'),
                    'deudores'       => (clone $base)->where('estado', 'vencido')->distinct('matricula_id')->count('matricula_id'),
                    'ultimos_pagos'  => \App\Models\Pago::with('matricula.estudiante')
                                        ->where('estado', 'pagado')
                                        ->whereHas('matricula', fn($m) => $m->where('school_year_id', $syId))
                                        ->latest('fecha_pago')->take(6)->get(),
                    'top_deudores'   => \App\Models\Pago::with('matricula.estudiante')
                                        ->where('estado', 'vencido')
                                        ->whereHas('matricula', fn($m) => $m->where('school_year_id', $syId))
                                        ->orderByDesc('monto')->take(5)->get(),
                ];
            });
        }

        // Secretaría / Recepción / Registro: stats de registro
        $statsRegistro = null;
        if (in_array($rolDashboard, ['secretaria', 'registro', 'recepcion']) && $schoolYear) {
            $statsRegistro = Cache::remember("t{$tid}_dashboard_registro_{$syId}", 180, function () use ($syId) {
                return [
                    'estudiantes'         => Estudiante::activos()->count(),
                    'matriculas_activas'  => Matricula::where('estado', 'activa')->where('school_year_id', $syId)->count(),
                    'prematriculas_pend'  => PreMatricula::where('estado', 'pendiente')->count(),
                    'grupos'              => Grupo::where('school_year_id', $syId)->count(),
                    'ultimas_matriculas'  => Matricula::with('estudiante', 'grupo.grado')
                                            ->where('school_year_id', $syId)
                                            ->where('estado', 'activa')
                                            ->latest()->take(6)->get(),
                    'prematriculas_rec'   => PreMatricula::with('representante')
                                            ->where('estado', 'pendiente')
                                            ->latest()->take(5)->get(),
                ];
            });
        }

        // Coordinador: stats académicas de calidad
        $statsCoord = null;
        if ($rolDashboard === 'coordinador' && $schoolYear) {
            $statsCoord = Cache::remember("t{$tid}_dashboard_coord_{$syId}", 180, function () use ($syId) {
                return [
                    'estudiantes'     => Estudiante::activos()->count(),
                    'grupos'          => Grupo::where('school_year_id', $syId)->count(),
                    'observaciones'   => Observacion::whereHas('asignacion', fn($q) => $q->where('school_year_id', $syId))->count(),
                    'periodos_activos'=> Periodo::where('school_year_id', $syId)->where('cerrado', false)->count(),
                    'periodos_cerrando' => Periodo::where('school_year_id', $syId)
                                            ->where('cerrado', false)
                                            ->whereBetween('fecha_fin', [now(), now()->addDays(14)])
                                            ->get(),
                    'obs_recientes'   => Observacion::with(['asignacion.docente.user', 'asignacion.asignatura', 'asignacion.grupo.grado'])
                                            ->whereHas('asignacion', fn($q) => $q->where('school_year_id', $syId))
                                            ->latest()->take(5)->get(),
                ];
            });
        }

        // Estadísticas de planificación y observaciones (solo admin/director/coordinador)
        $rolesConExtra = ['admin', 'coordinador'];
        $statsExtra = [];
        if (!$isDocente && in_array($rolDashboard, $rolesConExtra) && $schoolYear) {
            $statsExtra = Cache::remember("t{$tid}_dashboard_extra_{$syId}", 300, function () use ($syId) {
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

        // Datos para gráficas (solo admin/director)
        $chartData = null;
        if (!$isDocente && $rolDashboard === 'admin' && $schoolYear) {
            $chartData = Cache::remember("t{$tid}_dashboard_chart_{$syId}", 300, function () use ($syId) {
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

        // Stats de pagos resumidas (solo admin/director — Caja usa $statsCaja)
        $statsPagos = null;
        if (!$isDocente && $rolDashboard === 'admin' && \App\Models\ConfigInstitucional::moduloActivo('pagos') && $schoolYear) {
            // Ejecutar sincronización de pagos vencidos como máximo 1 vez por hora por tenant
            Cache::remember("t{$tid}_pagos_sync_lock", 3600, function () {
                \App\Models\Pago::sincronizarVencidos();
                return true;
            });
            $statsPagos = Cache::remember("t{$tid}_dashboard_pagos_{$syId}", 180, function () use ($syId) {
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
                $totalClasesActivas = Cache::remember("t{$tid}_dashboard_zura_clases_{$syId}", 300, fn() =>
                    ClaseVirtual::whereHas('asignacion', fn($q) =>
                        $q->where('school_year_id', $schoolYear->id)->where('activo', true)
                    )->where('activo', true)->count()
                );
                $totalEntregasPend  = Cache::remember("t{$tid}_dashboard_zura_entregas_{$syId}", 120, fn() =>
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

        // ── Agenda próximos 7 días (solo admin/director/coordinador) ─────────
        $agendaProxima = null;
        $preMatriculasPendientes = 0;
        if (!$isDocente && in_array($rolDashboard, ['admin', 'coordinador'])) {
            $agendaProxima = Cache::remember("t{$tid}_dashboard_agenda", 120, function () {
                $hoy   = now()->toDateString();
                $en7   = now()->addDays(7)->toDateString();
                $reuniones = Reunion::where('estado', 'programada')
                    ->whereBetween('fecha', [$hoy, $en7])
                    ->orderBy('fecha')
                    ->get()
                    ->map(fn($r) => [
                        'tipo'   => 'reunion',
                        'fecha'  => $r->fecha,
                        'titulo' => $r->titulo,
                        'sub'    => Reunion::tiposLabel()[$r->tipo] ?? $r->tipo,
                        'lugar'  => $r->lugar,
                        'icon'   => 'bi-people-fill',
                        'color'  => '#2563eb',
                        'bg'     => '#eff6ff',
                        'route'  => route('admin.reuniones.show', $r),
                    ]);
                $eventos = Evento::where('activo', true)
                    ->whereBetween('fecha_inicio', [$hoy, $en7])
                    ->orderBy('fecha_inicio')
                    ->get()
                    ->map(fn($e) => [
                        'tipo'   => 'evento',
                        'fecha'  => $e->fecha_inicio,
                        'titulo' => $e->nombre,
                        'sub'    => ucfirst($e->tipo),
                        'lugar'  => $e->lugar,
                        'icon'   => 'bi-calendar-event-fill',
                        'color'  => '#059669',
                        'bg'     => '#d1fae5',
                        'route'  => route('admin.eventos.show', $e),
                    ]);
                return $reuniones->merge($eventos)->sortBy('fecha')->values();
            });
            $preMatriculasPendientes = Cache::remember("t{$tid}_dashboard_prematriculas", 120,
                fn() => PreMatricula::where('estado', 'pendiente')->count()
            );
        }

        // ── Transporte (solo admin/director) ──────────────────────────────
        $transporteStats = null;
        if (!$isDocente && $rolDashboard === 'admin') {
            $transporteStats = Cache::remember("t{$tid}_dashboard_transporte", 300, function () {
                $rutas = RutaTransporte::withCount('estudiantesRuta as ocupacion')
                    ->where('activo', true)
                    ->get();
                return [
                    'total_rutas'  => $rutas->count(),
                    'total_cap'    => $rutas->sum('capacidad'),
                    'total_pasaj'  => $rutas->sum('ocupacion'),
                    'rutas_llenas' => $rutas->filter(fn($r) => $r->capacidad > 0 && ($r->ocupacion / $r->capacidad) >= 0.8)->count(),
                ];
            });
        }

        // ── Disciplina reciente (solo admin/director) ─────────────────────
        $recentDisciplina     = null;
        $pendientesDisciplina = 0;
        if (!$isDocente && $rolDashboard === 'admin') {
            $recentDisciplina = Cache::remember("t{$tid}_dashboard_disciplina_recent", 120, fn() =>
                FaltaDisciplinaria::with('estudiante')
                    ->latest('fecha')
                    ->take(5)
                    ->get()
            );
            $pendientesDisciplina = Cache::remember("t{$tid}_dashboard_disciplina_pend", 120, fn() =>
                FaltaDisciplinaria::where('resuelto', false)->count()
            );
        }

        // ── Incidentes médicos recientes (solo admin/director) ───────────
        $recentSalud          = null;
        $totalIncidentesMes   = 0;
        if (!$isDocente && $rolDashboard === 'admin') {
            $recentSalud = Cache::remember("t{$tid}_dashboard_salud_recent", 120, fn() =>
                IncidenteMedico::with('estudiante')
                    ->latest('fecha')
                    ->take(4)
                    ->get()
            );
            $totalIncidentesMes = Cache::remember("t{$tid}_dashboard_salud_mes", 120, fn() =>
                IncidenteMedico::whereMonth('fecha', now()->month)
                    ->whereYear('fecha', now()->year)
                    ->count()
            );
        }

        // Alertas académicas (admin/director/coordinador)
        $alertasAcad = null;
        if (!$isDocente && in_array($rolDashboard, ['admin', 'coordinador']) && $schoolYear) {
            $roles = $user->getRoleNames()->toArray();
            $alertasAcad = Cache::remember("t{$tid}_dashboard_alertas_acad_{$user->id}", 90, function () use ($user, $roles) {
                return AlertaSistema::where(function ($q) use ($user, $roles) {
                        $q->where('destinatario_id', $user->id)
                          ->orWhereIn('destinatario_rol', $roles)
                          ->orWhereNull('destinatario_id');
                    })
                    ->whereIn('tipo', ['academica', 'asistencia'])
                    ->where('leida', false)
                    ->latest()
                    ->limit(6)
                    ->get();
            });
        }

        // ── Checklist post-onboarding (solo admin, no docentes) ─────────────
        $setupChecklist = null;
        if (! $isDocente && ($currentTenant = app('tenant')) && $currentTenant->onboarding_completado) {
            $meta = $currentTenant->metadatos ?? [];
            if (empty($meta['setup_checklist_dismissed'])) {
                $setupChecklist = $this->buildSetupChecklist($schoolYear);
            }
        }

        return view('admin.dashboard', compact(
            'schoolYear',
            'totalEstudiantes',
            'totalDocentes',
            'totalGrupos',
            'totalAsignaturas',
            'matriculasActivas',
            'docentePanel',
            'isDocente',
            'horarioActivo',
            'statsExtra',
            'statsPagos',
            'chartData',
            'alertasAcad',
            'zuraClassData',
            'recentDisciplina',
            'pendientesDisciplina',
            'recentSalud',
            'totalIncidentesMes',
            'transporteStats',
            'agendaProxima',
            'preMatriculasPendientes',
            'setupChecklist',
            'rolDashboard',
            'statsCaja',
            'statsRegistro',
            'statsCoord',
        ));
    }

    /** Construye los pasos del checklist de configuración inicial. */
    private function buildSetupChecklist(?SchoolYear $schoolYear): array
    {
        $syId = $schoolYear?->id ?? 0;

        $pasos = [
            [
                'key'    => 'asignaturas',
                'titulo' => 'Crear asignaturas',
                'desc'   => 'Define las materias que se impartirán en cada grado.',
                'icon'   => 'bi-book-fill',
                'color'  => '#f59e0b',
                'route'  => route('admin.asignaturas.index'),
                'label'  => 'Ir a Asignaturas',
                'done'   => Asignatura::count() > 0,
            ],
            [
                'key'    => 'docentes',
                'titulo' => 'Agregar docentes',
                'desc'   => 'Registra al personal docente del centro educativo.',
                'icon'   => 'bi-person-video3',
                'color'  => '#3b82f6',
                'route'  => route('admin.docentes.create'),
                'label'  => 'Agregar Docente',
                'done'   => Docente::count() > 0,
            ],
            [
                'key'    => 'asignaciones',
                'titulo' => 'Asignar docentes a grupos',
                'desc'   => 'Vincula cada docente con un grupo y su asignatura.',
                'icon'   => 'bi-grid-3x3-gap-fill',
                'color'  => '#8b5cf6',
                'route'  => route('admin.asignaciones.create'),
                'label'  => 'Crear Asignación',
                'done'   => $syId > 0 && Asignacion::where('school_year_id', $syId)->exists(),
            ],
            [
                'key'    => 'matriculas',
                'titulo' => 'Matricular estudiantes',
                'desc'   => 'Inscribe a los estudiantes en los grupos del año escolar.',
                'icon'   => 'bi-mortarboard-fill',
                'color'  => '#10b981',
                'route'  => route('admin.estudiantes.index'),
                'label'  => 'Ir a Estudiantes',
                'done'   => $syId > 0 && Matricula::where('estado', 'activa')->where('school_year_id', $syId)->exists(),
            ],
            [
                'key'    => 'horario',
                'titulo' => 'Publicar horario',
                'desc'   => 'Genera y publica el horario de clases para los grupos.',
                'icon'   => 'bi-calendar-week-fill',
                'color'  => '#ec4899',
                'route'  => route('admin.horarios.index'),
                'label'  => 'Ir a Horarios',
                'done'   => $syId > 0 && Horario::where('school_year_id', $syId)->where('estado', 'publicado')->exists(),
            ],
        ];

        $completados = collect($pasos)->where('done', true)->count();

        return [
            'pasos'       => $pasos,
            'completados' => $completados,
            'total'       => count($pasos),
            'porcentaje'  => (int) round(($completados / count($pasos)) * 100),
            'todo_listo'  => $completados === count($pasos),
        ];
    }

    /** Descarta el checklist de configuración (lo guarda en metadatos del tenant). */
    public function dismissSetupChecklist()
    {
        $tenant = app('tenant');
        $meta   = $tenant->metadatos ?? [];
        $meta['setup_checklist_dismissed'] = true;
        $tenant->update(['metadatos' => $meta]);

        return response()->json(['ok' => true]);
    }

    /**
     * AJAX — devuelve stats actualizadas (sin caché) para el botón "Actualizar".
     */
    public function statsJson()
    {
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        // Flush caché tenant-scoped y recalcular
        $tid = tenant_id();
        Cache::forget("t{$tid}_dashboard_estudiantes");
        Cache::forget("t{$tid}_dashboard_docentes");
        Cache::forget("t{$tid}_dashboard_grupos_{$syId}");
        Cache::forget("t{$tid}_dashboard_asignaturas");
        Cache::forget("t{$tid}_dashboard_matriculas_{$syId}");
        Cache::forget("t{$tid}_dashboard_extra_{$syId}");

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
