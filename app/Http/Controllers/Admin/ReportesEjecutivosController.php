<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\PreMatricula;
use App\Models\RendimientoCache;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $periodoId  = $request->input('periodo_id');

        // ── Rendimiento (usa cache precalculada) ─────────────────────────
        $cacheQuery = RendimientoCache::where('school_year_id', $schoolYear?->id ?? 0)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                               fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion']);

        $rendimiento = $cacheQuery->get();

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
        $mesActual  = now()->month;
        $anioActual = now()->year;
        $asistenciaMes = Asistencia::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $totalAsist    = $asistenciaMes->sum();
        $presenteAsist = ($asistenciaMes['presente'] ?? 0) + ($asistenciaMes['tardanza'] ?? 0);
        $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : null;

        // Pagos
        $statsPagos = null;
        try {
            $pagosBase  = $schoolYear
                ? Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $schoolYear->id))
                : Pago::query();
            $statsPagos = [
                'cobrado'   => (clone $pagosBase)->where('estado', 'pagado')->sum('monto'),
                'pendiente' => (clone $pagosBase)->where('estado', 'pendiente')->sum('monto'),
                'vencido'   => (clone $pagosBase)->where('estado', 'vencido')->sum('monto'),
            ];
        } catch (\Exception $e) {}

        // ── Datos para gráficas ──────────────────────────────────────────
        $promediosPorGrado = $rendimiento
            ->groupBy(fn($r) => $r->grupo?->grado?->nombre ?? 'Sin grado')
            ->map(fn($items) => round($items->avg('promedio_grupo'), 1))
            ->sortKeys();

        $matriculasPorGrado = Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
            ->join('grados', 'grupos.grado_id', '=', 'grados.id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre')
            ->orderBy('grados.nombre')
            ->pluck('total', 'grado');

        $tendenciaAsistencia = $this->tendenciaAsistencia();

        $distribucionDesempeno = [
            'Excelente (90-100)' => round($rendimiento->avg('pct_excelente') ?? 0, 1),
            'Bueno (80-89)'      => round($rendimiento->avg('pct_bueno') ?? 0, 1),
            'Regular (70-79)'    => round($rendimiento->avg('pct_regular') ?? 0, 1),
            'Bajo (<70)'         => round($rendimiento->avg('pct_bajo') ?? 0, 1),
        ];

        $topGrupos    = $rendimiento->sortByDesc('promedio_grupo')->take(5)->values();
        $bottomGrupos = $rendimiento->filter(fn($r) => $r->promedio_grupo > 0)
                                    ->sortBy('promedio_grupo')->take(5)->values();

        $disciplinaPorTipo = [];
        try {
            $disciplinaPorTipo = FaltaDisciplinaria::selectRaw('tipo, COUNT(*) as total')
                ->groupBy('tipo')->pluck('total', 'tipo')->toArray();
        } catch (\Exception $e) {}

        $preMatriculaStats = [];
        try {
            $preMatriculaStats = [
                'pendientes' => PreMatricula::where('estado', 'pendiente')->count(),
                'aprobadas'  => PreMatricula::where('estado', 'aprobada')->count(),
                'rechazadas' => PreMatricula::where('estado', 'rechazada')->count(),
            ];
        } catch (\Exception $e) {}

        // ── Secciones nuevas ─────────────────────────────────────────────
        $promediosPorAsignatura = $this->promediosPorAsignatura($schoolYear);
        $riesgoData             = $this->riesgoAcademico($schoolYear);
        $statsDocentes          = $this->statsDocentes($schoolYear);
        $comparativa            = $this->comparativaAnual(
            $schoolYear, $promedioInstitucional, $tasaAprobacion, $pctAsistencia
        );

        return view('admin.ejecutivo.index', compact(
            'schoolYear', 'periodos', 'periodoId',
            'totalEstudiantes', 'totalDocentes',
            'promedioInstitucional', 'tasaAprobacion', 'pctAsistencia',
            'statsPagos', 'asistenciaMes',
            'promediosPorGrado', 'matriculasPorGrado',
            'tendenciaAsistencia', 'distribucionDesempeno',
            'topGrupos', 'bottomGrupos',
            'disciplinaPorTipo', 'preMatriculaStats', 'rendimiento',
            'promediosPorAsignatura', 'riesgoData', 'statsDocentes', 'comparativa'
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
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $totalAsist    = $asistenciaMes->sum();
        $presenteAsist = ($asistenciaMes['presente'] ?? 0) + ($asistenciaMes['tardanza'] ?? 0);
        $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : null;

        $statsPagos = null;
        try {
            $pagosBase  = $schoolYear
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

        $promediosPorAsignatura = $this->promediosPorAsignatura($schoolYear);
        $riesgoData             = $this->riesgoAcademico($schoolYear);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.ejecutivo.pdf', compact(
            'schoolYear', 'periodoId', 'inst',
            'totalEstudiantes', 'promedioInstitucional',
            'tasaAprobacion', 'pctAsistencia',
            'statsPagos', 'asistenciaMes',
            'matriculasPorGrado', 'rendimiento',
            'promediosPorAsignatura', 'riesgoData'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('reporte_ejecutivo_' . now()->format('Ymd') . '.pdf');
    }

    public function excel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $periodoId  = $request->input('periodo_id');

        $rendimiento = RendimientoCache::where('school_year_id', $schoolYear?->id ?? 0)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                               fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get();

        $totalEstudiantes      = Estudiante::activos()->count();
        $totalDocentes         = Docente::activos()->count();
        $promedioInst          = round($rendimiento->avg('promedio_grupo') ?? 0, 1);
        $totalAprobados        = $rendimiento->sum('total_aprobados');
        $totalAlumnos          = max($rendimiento->sum('total_estudiantes'), 1);
        $tasaAprobacion        = round($totalAprobados / $totalAlumnos * 100, 1);

        $mesActual  = now()->month;
        $anioActual = now()->year;
        $asistenciaMes = Asistencia::whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')->pluck('total', 'estado');
        $totalAsist    = $asistenciaMes->sum();
        $presenteAsist = ($asistenciaMes['presente'] ?? 0) + ($asistenciaMes['tardanza'] ?? 0);
        $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : 0;

        $matriculasPorGrado = Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
            ->join('grados', 'grupos.grado_id', '=', 'grados.id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre')
            ->orderBy('grados.nombre')
            ->pluck('total', 'grado');

        $promediosPorAsignatura = $this->promediosPorAsignatura($schoolYear);
        $riesgoData             = $this->riesgoAcademico($schoolYear);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $ss = new Spreadsheet();

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];
        $titleStyle = [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        // ── Hoja 1: Resumen Ejecutivo ──────────────────────────────────────
        $ws = $ss->getActiveSheet()->setTitle('Resumen Ejecutivo');
        $ws->mergeCells('A1:D1');
        $ws->setCellValue('A1', strtoupper($inst) . ' — REPORTE EJECUTIVO');
        $ws->getStyle('A1')->applyFromArray($titleStyle);
        $ws->getRowDimension(1)->setRowHeight(28);

        $ws->setCellValue('A2', 'Año escolar: ' . ($schoolYear?->nombre ?? '—'));
        $ws->setCellValue('C2', 'Generado: ' . now()->format('d/m/Y H:i'));
        $ws->getStyle('A2:D2')->getFont()->setSize(9)->setItalic(true);
        $ws->getStyle('A2:D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $ws->setCellValue('A4', 'INDICADOR');
        $ws->setCellValue('B4', 'VALOR');
        $ws->getStyle('A4:B4')->applyFromArray($hdrStyle);

        $kpis = [
            ['Estudiantes Activos',          $totalEstudiantes],
            ['Docentes Activos',             $totalDocentes],
            ['Promedio Institucional',       $promedioInst ?: '—'],
            ['Tasa de Aprobación (%)',       $tasaAprobacion . '%'],
            ['Asistencia del Mes (%)',       $pctAsistencia . '%'],
            ['Estudiantes en Riesgo (≥2 materias < 70)', $riesgoData['totalEnRiesgo']],
        ];
        $row = 5;
        foreach ($kpis as $kpi) {
            $ws->setCellValue('A' . $row, $kpi[0]);
            $ws->setCellValue('B' . $row, $kpi[1]);
            $ws->getStyle('A' . $row)->getFont()->setBold(true)->setSize(9);
            $ws->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $ws->getColumnDimension('A')->setWidth(38);
        $ws->getColumnDimension('B')->setWidth(22);
        $ws->getColumnDimension('C')->setWidth(22);
        $ws->getColumnDimension('D')->setWidth(22);

        // ── Hoja 2: Rendimiento por Grupo ──────────────────────────────────
        $ws2 = $ss->createSheet()->setTitle('Por Grupo');
        $ws2->mergeCells('A1:G1');
        $ws2->setCellValue('A1', 'RENDIMIENTO POR GRUPO — ' . ($schoolYear?->nombre ?? ''));
        $ws2->getStyle('A1')->applyFromArray($titleStyle);
        $ws2->getRowDimension(1)->setRowHeight(22);

        $hdrs2 = ['Grado', 'Sección', 'Estudiantes', 'Promedio', '% Aprobados', 'En Riesgo', 'Semáforo'];
        foreach ($hdrs2 as $i => $h) {
            $ws2->setCellValue([$i + 1, 2], $h);
        }
        $ws2->getStyle('A2:G2')->applyFromArray($hdrStyle);

        $row2 = 3;
        foreach ($rendimiento->sortByDesc('promedio_grupo') as $r) {
            $prom = round($r->promedio_grupo, 1);
            $ws2->setCellValue('A' . $row2, $r->grupo?->grado?->nombre ?? '—');
            $ws2->setCellValue('B' . $row2, $r->grupo?->seccion?->nombre ?? '—');
            $ws2->setCellValue('C' . $row2, $r->total_estudiantes);
            $ws2->setCellValue('D' . $row2, $prom);
            $aprobPct = $r->total_estudiantes > 0
                ? round($r->total_aprobados / $r->total_estudiantes * 100, 1)
                : 0;
            $ws2->setCellValue('E' . $row2, $aprobPct . '%');
            $ws2->setCellValue('F' . $row2, $r->total_riesgo ?? 0);
            $semColor  = $prom >= 80 ? '16a34a' : ($prom >= 70 ? 'd97706' : 'dc2626');
            $semLabel  = $prom >= 80 ? 'Verde' : ($prom >= 70 ? 'Amarillo' : 'Rojo');
            $ws2->setCellValue('G' . $row2, $semLabel);
            $ws2->getStyle('G' . $row2)->getFont()->getColor()->setRGB($semColor);
            $ws2->getStyle('G' . $row2)->getFont()->setBold(true);
            $ws2->getStyle('C' . $row2 . ':F' . $row2)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row2++;
        }

        foreach (range(1, 7) as $c) {
            $ws2->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $ws2->freezePane('A3');

        // ── Hoja 3: Rendimiento por Asignatura ─────────────────────────────
        $ws3 = $ss->createSheet()->setTitle('Por Asignatura');
        $ws3->mergeCells('A1:D1');
        $ws3->setCellValue('A1', 'RENDIMIENTO POR ASIGNATURA — ' . ($schoolYear?->nombre ?? ''));
        $ws3->getStyle('A1')->applyFromArray($titleStyle);
        $ws3->getRowDimension(1)->setRowHeight(22);

        $hdrs3 = ['#', 'Asignatura', 'Promedio', 'Total Estudiantes'];
        foreach ($hdrs3 as $i => $h) {
            $ws3->setCellValue([$i + 1, 2], $h);
        }
        $ws3->getStyle('A2:D2')->applyFromArray($hdrStyle);

        $row3 = 3;
        foreach ($promediosPorAsignatura as $idx => $asig) {
            $prom = round((float) $asig->promedio, 1);
            $ws3->setCellValue('A' . $row3, $idx + 1);
            $ws3->setCellValue('B' . $row3, $asig->nombre);
            $ws3->setCellValue('C' . $row3, $prom);
            $ws3->setCellValue('D' . $row3, $asig->total_estudiantes);
            $color = $prom >= 80 ? '16a34a' : ($prom >= 70 ? 'd97706' : 'dc2626');
            $ws3->getStyle('C' . $row3)->getFont()->getColor()->setRGB($color)->setBold(true);
            $ws3->getStyle('A' . $row3 . ':D' . $row3)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws3->getStyle('B' . $row3)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $row3++;
        }

        foreach (range(1, 4) as $c) {
            $ws3->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $ws3->getColumnDimension('B')->setWidth(32);

        // ── Hoja 4: Riesgo Académico ────────────────────────────────────────
        $ws4 = $ss->createSheet()->setTitle('Riesgo Académico');
        $ws4->mergeCells('A1:C1');
        $ws4->setCellValue('A1', 'ESTUDIANTES EN RIESGO ACADÉMICO (≥2 materias < 70)');
        $ws4->getStyle('A1')->applyFromArray($titleStyle);
        $ws4->getRowDimension(1)->setRowHeight(22);

        $hdrs4 = ['Grado', 'Estudiantes en Riesgo', '% del Total'];
        foreach ($hdrs4 as $i => $h) {
            $ws4->setCellValue([$i + 1, 2], $h);
        }
        $ws4->getStyle('A2:C2')->applyFromArray($hdrStyle);

        $row4 = 3;
        foreach ($riesgoData['riesgoPorGrado'] as $grado => $count) {
            $totalGrado = (int) ($matriculasPorGrado[$grado] ?? 0);
            $pct = $totalGrado > 0 ? round($count / $totalGrado * 100, 1) : 0;
            $ws4->setCellValue('A' . $row4, $grado);
            $ws4->setCellValue('B' . $row4, $count);
            $ws4->setCellValue('C' . $row4, $pct . '%');
            $ws4->getStyle('B' . $row4 . ':C' . $row4)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if ($pct >= 20) {
                $ws4->getStyle('B' . $row4 . ':C' . $row4)->getFont()->getColor()->setRGB('dc2626');
            }
            $row4++;
        }
        $ws4->setCellValue('A' . $row4, 'TOTAL');
        $ws4->setCellValue('B' . $row4, $riesgoData['totalEnRiesgo']);
        $ws4->getStyle('A' . $row4 . ':C' . $row4)->getFont()->setBold(true);
        foreach (range(1, 3) as $c) {
            $ws4->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $ss->setActiveSheetIndex(0);

        $filename = 'ejecutivo_' . now()->format('Ymd') . '.xlsx';
        return response()->streamDownload(function () use ($ss) {
            $writer = new Xlsx($ss);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function promediosPorAsignatura(?SchoolYear $schoolYear): \Illuminate\Support\Collection
    {
        if (! $schoolYear) return collect();

        return CalificacionAcademica::join('asignaciones', 'asignaciones.id', '=', 'calificaciones_academicas.asignacion_id')
            ->join('asignaturas', 'asignaturas.id', '=', 'asignaciones.asignatura_id')
            ->join('matriculas', 'matriculas.id', '=', 'calificaciones_academicas.matricula_id')
            ->where('matriculas.school_year_id', $schoolYear->id)
            ->where('matriculas.estado', 'activa')
            ->whereNotNull('calificaciones_academicas.nota_final')
            ->selectRaw('asignaturas.id, asignaturas.nombre, ROUND(AVG(calificaciones_academicas.nota_final),1) as promedio, COUNT(*) as total_estudiantes')
            ->groupBy('asignaturas.id', 'asignaturas.nombre')
            ->orderBy('promedio')
            ->get();
    }

    private function riesgoAcademico(?SchoolYear $schoolYear): array
    {
        if (! $schoolYear) {
            return ['totalEnRiesgo' => 0, 'riesgoPorGrado' => collect()];
        }

        $fallos = CalificacionAcademica::join('matriculas', 'matriculas.id', '=', 'calificaciones_academicas.matricula_id')
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->where('matriculas.school_year_id', $schoolYear->id)
            ->where('matriculas.estado', 'activa')
            ->whereNotNull('calificaciones_academicas.nota_final')
            ->where('calificaciones_academicas.nota_final', '<', 70)
            ->selectRaw('matriculas.estudiante_id, grados.nombre as grado_nombre, COUNT(*) as materias_bajas')
            ->groupBy('matriculas.estudiante_id', 'grados.nombre')
            ->having('materias_bajas', '>=', 2)
            ->get();

        return [
            'totalEnRiesgo' => $fallos->count(),
            'riesgoPorGrado' => $fallos->groupBy('grado_nombre')->map->count(),
        ];
    }

    private function statsDocentes(?SchoolYear $schoolYear): array
    {
        $activos = Docente::activos()->count();
        if (! $schoolYear) {
            return ['activos' => $activos, 'con_notas' => 0, 'sin_notas' => $activos];
        }

        $conNotas = (int) CalificacionAcademica::join(
                'asignaciones', 'asignaciones.id', '=', 'calificaciones_academicas.asignacion_id'
            )
            ->join('matriculas', 'matriculas.id', '=', 'calificaciones_academicas.matricula_id')
            ->where('matriculas.school_year_id', $schoolYear->id)
            ->whereNotNull('calificaciones_academicas.nota_final')
            ->where('calificaciones_academicas.publicado', true)
            ->distinct()
            ->count('asignaciones.docente_id');

        return [
            'activos'   => $activos,
            'con_notas' => $conNotas,
            'sin_notas' => max(0, $activos - $conNotas),
        ];
    }

    private function comparativaAnual(
        ?SchoolYear $schoolYear,
        ?float $promedioActual,
        float $tasaActual,
        ?float $asistActual
    ): array {
        if (! $schoolYear) return [];

        $prev = SchoolYear::where('activo', false)
            ->where('id', '<', $schoolYear->id)
            ->orderByDesc('id')
            ->first();

        if (! $prev) return [];

        $prevRend    = RendimientoCache::where('school_year_id', $prev->id)->whereNull('periodo_id')->get();
        $prevPromedio = $prevRend->avg('promedio_grupo');
        $prevTotal    = max($prevRend->sum('total_estudiantes'), 1);
        $prevTasa     = round($prevRend->sum('total_aprobados') / $prevTotal * 100, 1);

        return [
            'nombre'   => $prev->nombre,
            'promedio' => ($promedioActual && $prevPromedio) ? round($promedioActual - $prevPromedio, 1) : null,
            'tasa'     => round($tasaActual - $prevTasa, 1),
        ];
    }

    private function tendenciaAsistencia(): array
    {
        $desde = now()->subMonths(5)->startOfMonth();

        $rows = Asistencia::where('fecha', '>=', $desde)
            ->selectRaw('YEAR(fecha) as yr, MONTH(fecha) as mo, estado, COUNT(*) as total')
            ->groupBy('yr', 'mo', 'estado')
            ->orderBy('yr')->orderBy('mo')
            ->get()
            ->groupBy(fn($r) => $r->yr . '-' . str_pad($r->mo, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data   = ['presente' => [], 'tardanza' => [], 'ausente' => []];

        for ($i = 5; $i >= 0; $i--) {
            $fecha    = now()->subMonths($i);
            $labels[] = ucfirst($fecha->locale('es')->translatedFormat('M Y'));
            $key      = $fecha->format('Y') . '-' . $fecha->format('m');
            $byEstado = $rows->get($key, collect())->pluck('total', 'estado');

            $data['presente'][] = (int) ($byEstado['presente'] ?? 0);
            $data['tardanza'][] = (int) ($byEstado['tardanza'] ?? 0);
            $data['ausente'][]  = (int) ($byEstado['ausente'] ?? 0);
        }

        return compact('labels', 'data');
    }
}
