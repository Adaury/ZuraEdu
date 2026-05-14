<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\RendimientoCache;
use App\Models\SchoolYear;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\PreMatricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesEjecutivosController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->hasAnyRole([
                'Administrador', 'Director',
                'Coordinador Académico',
                'Coordinador Primer Ciclo',
                'Coordinador Segundo Ciclo',
            ])) {
                abort(403, 'Acceso reservado para directivos.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $periodos   = $this->getPeriodos($schoolYear);

        $periodoId = $request->input('periodo_id');

        // ── Rendimiento (usa cache precalculada) ─────────────────────────
        $cacheQuery = RendimientoCache::where('school_year_id', $schoolYear?->id ?? 0)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                               fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion']);

        $rendimiento = $cacheQuery->get();

        // Si no hay cache, intentar calcular
        if ($rendimiento->isEmpty() && $schoolYear) {
            $grupos = Grupo::where('school_year_id', $schoolYear->id)->where('activo', true)->get();
            foreach ($grupos as $g) {
                RendimientoCache::recalcularParaGrupo($g->id, $schoolYear->id, $periodoId ?: null);
            }
            $rendimiento = $cacheQuery->get();
        }

        // ── KPIs globales ────────────────────────────────────────────────
        $totalEstudiantes      = Estudiante::activos()->count();
        $totalDocentes         = Docente::activos()->count();
        $promedioInstitucional = $rendimiento->avg('promedio_grupo');
        $totalAprobados        = $rendimiento->sum('total_aprobados');
        $totalAlumnos          = max($rendimiento->sum('total_estudiantes'), 1);
        $tasaAprobacion        = round($totalAprobados / $totalAlumnos * 100, 1);

        // Asistencia del mes actual
        $mesActual = now()->month;
        $anioActual = now()->year;
        $asistenciaMes = Asistencia::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->selectRaw("estado, COUNT(*) as total")
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $totalAsist    = $asistenciaMes->sum();
        $presenteAsist = ($asistenciaMes['presente'] ?? 0) + ($asistenciaMes['tardanza'] ?? 0);
        $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : null;

        // Pagos
        $statsPagos = null;
        try {
            $pagosBase = $schoolYear
                ? Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $schoolYear->id))
                : Pago::query();
            $statsPagos = [
                'cobrado'   => (clone $pagosBase)->where('estado', 'pagado')->sum('monto'),
                'pendiente' => (clone $pagosBase)->where('estado', 'pendiente')->sum('monto'),
                'vencido'   => (clone $pagosBase)->where('estado', 'vencido')->sum('monto'),
            ];
        } catch (\Exception $e) { /* módulo desactivado */ }

        // ── Datos para gráficas ──────────────────────────────────────────

        // 1. Promedios por grado
        $promediosPorGrado = $rendimiento
            ->groupBy(fn($r) => $r->grupo?->grado?->nombre ?? 'Sin grado')
            ->map(fn($items) => round($items->avg('promedio_grupo'), 1))
            ->sortKeys();

        // 2. Matrículas activas por grado
        $matriculasPorGrado = Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
            ->join('grados', 'grupos.grado_id', '=', 'grados.id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre')
            ->orderBy('grados.nombre')
            ->pluck('total', 'grado');

        // 3. Tendencia de asistencia — últimos 6 meses
        $tendenciaAsistencia = $this->tendenciaAsistencia();

        // 4. Distribución de desempeño (pct_ de rendimiento_cache)
        $distribucionDesempeno = [
            'Excelente (90-100)' => round($rendimiento->avg('pct_excelente') ?? 0, 1),
            'Bueno (80-89)'      => round($rendimiento->avg('pct_bueno') ?? 0, 1),
            'Regular (70-79)'    => round($rendimiento->avg('pct_regular') ?? 0, 1),
            'Bajo (<70)'         => round($rendimiento->avg('pct_bajo') ?? 0, 1),
        ];

        // 5. Top y bottom grupos por promedio
        $topGrupos    = $rendimiento->sortByDesc('promedio_grupo')->take(5)->values();
        $bottomGrupos = $rendimiento->filter(fn($r) => $r->promedio_grupo > 0)
                                    ->sortBy('promedio_grupo')->take(5)->values();

        // 6. Disciplina del año (por tipo)
        $disciplinaPorTipo = [];
        try {
            $disciplinaPorTipo = FaltaDisciplinaria::selectRaw('tipo, COUNT(*) as total')
                ->groupBy('tipo')
                ->pluck('total', 'tipo')
                ->toArray();
        } catch (\Exception $e) {}

        // 7. Pre-matrículas pendientes
        $preMatriculaStats = [];
        try {
            $preMatriculaStats = [
                'pendientes'  => PreMatricula::where('estado', 'pendiente')->count(),
                'aprobadas'   => PreMatricula::where('estado', 'aprobada')->count(),
                'rechazadas'  => PreMatricula::where('estado', 'rechazada')->count(),
            ];
        } catch (\Exception $e) {}

        return view('admin.ejecutivo.index', compact(
            'schoolYear', 'periodos', 'periodoId',
            'totalEstudiantes', 'totalDocentes',
            'promedioInstitucional', 'tasaAprobacion', 'pctAsistencia',
            'statsPagos', 'asistenciaMes',
            'promediosPorGrado', 'matriculasPorGrado',
            'tendenciaAsistencia', 'distribucionDesempeno',
            'topGrupos', 'bottomGrupos',
            'disciplinaPorTipo', 'preMatriculaStats', 'rendimiento'
        ));
    }

    public function pdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $periodoId  = $request->input('periodo_id');

        $rendimiento = RendimientoCache::where('school_year_id', $schoolYear?->id ?? 0)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                               fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get();

        $totalEstudiantes      = Estudiante::activos()->count();
        $promedioInstitucional = $rendimiento->avg('promedio_grupo');
        $totalAprobados        = $rendimiento->sum('total_aprobados');
        $totalAlumnos          = max($rendimiento->sum('total_estudiantes'), 1);
        $tasaAprobacion        = round($totalAprobados / $totalAlumnos * 100, 1);

        $mesActual  = now()->month;
        $anioActual = now()->year;
        $asistenciaMes = Asistencia::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->selectRaw("estado, COUNT(*) as total")
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $totalAsist    = $asistenciaMes->sum();
        $presenteAsist = ($asistenciaMes['presente'] ?? 0) + ($asistenciaMes['tardanza'] ?? 0);
        $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : null;

        $statsPagos = null;
        try {
            $pagosBase = $schoolYear
                ? Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $schoolYear->id))
                : Pago::query();
            $statsPagos = [
                'cobrado'   => (clone $pagosBase)->where('estado', 'pagado')->sum('monto'),
                'pendiente' => (clone $pagosBase)->where('estado', 'pendiente')->sum('monto'),
                'vencido'   => (clone $pagosBase)->where('estado', 'vencido')->sum('monto'),
            ];
        } catch (\Exception $e) {}

        $matriculasPorGrado = Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
            ->join('grados', 'grupos.grado_id', '=', 'grados.id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre')
            ->orderBy('grados.nombre')
            ->pluck('total', 'grado');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.ejecutivo.pdf', compact(
            'schoolYear', 'periodoId', 'inst',
            'totalEstudiantes', 'promedioInstitucional',
            'tasaAprobacion', 'pctAsistencia',
            'statsPagos', 'asistenciaMes',
            'matriculasPorGrado', 'rendimiento'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('reporte_ejecutivo_' . now()->format('Ymd') . '.pdf');
    }

    private function tendenciaAsistencia(): array
    {
        $meses  = collect();
        $labels = [];
        $data   = ['presente' => [], 'tardanza' => [], 'ausente' => []];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $labels[] = ucfirst($fecha->locale('es')->translatedFormat('M Y'));

            $rows = Asistencia::whereMonth('fecha', $fecha->month)
                ->whereYear('fecha', $fecha->year)
                ->selectRaw("estado, COUNT(*) as total")
                ->groupBy('estado')
                ->pluck('total', 'estado');

            $data['presente'][] = (int) ($rows['presente'] ?? 0);
            $data['tardanza'][] = (int) ($rows['tardanza'] ?? 0);
            $data['ausente'][]  = (int) ($rows['ausente'] ?? 0);
        }

        return compact('labels', 'data');
    }
}
