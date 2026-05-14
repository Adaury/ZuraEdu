<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalificacionAcademica;
use App\Models\Grupo;
use App\Models\Grado;
use App\Models\Periodo;
use App\Models\RendimientoCache;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RendimientoController extends Controller
{
    public function dashboard(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) {
            return view('admin.rendimiento.dashboard', ['sinAnio' => true]);
        }

        $periodos  = $this->getPeriodos($schoolYear);
        $periodoId = $request->periodo_id;

        $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                              fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()
            ->sortBy('grupo.grado.nivel');

        // KPIs institucionales
        $promedioInstitucional = $cacheData->avg('promedio_grupo');
        $totalRiesgo           = $cacheData->sum('total_riesgo');
        $totalEstudiantes      = $cacheData->sum('total_estudiantes');
        $tasaAprobacion        = $totalEstudiantes > 0
            ? round(($totalEstudiantes - $totalRiesgo) / $totalEstudiantes * 100, 1)
            : null;
        $gruposAlerta          = $cacheData->filter(fn($c) => $c->semaforo === 'danger')->count();

        // Si no hay cache, calcular para los grupos del año
        if ($cacheData->isEmpty()) {
            $grupos = Grupo::where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->with(['grado', 'seccion'])
                ->get();
            foreach ($grupos as $grupo) {
                RendimientoCache::recalcularParaGrupo($grupo->id, $schoolYear->id, $periodoId ?: null);
            }
            $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                                  fn($q) => $q->whereNull('periodo_id'))
                ->with(['grupo.grado', 'grupo.seccion'])
                ->get()
                ->sortBy('grupo.grado.nivel');
            $promedioInstitucional = $cacheData->avg('promedio_grupo');
            $totalRiesgo           = $cacheData->sum('total_riesgo');
            $totalEstudiantes      = $cacheData->sum('total_estudiantes');
            $tasaAprobacion        = $totalEstudiantes > 0
                ? round(($totalEstudiantes - $totalRiesgo) / $totalEstudiantes * 100, 1)
                : null;
            $gruposAlerta = $cacheData->filter(fn($c) => $c->semaforo === 'danger')->count();
        }

        return view('admin.rendimiento.dashboard', compact(
            'schoolYear', 'periodos', 'periodoId',
            'cacheData', 'promedioInstitucional', 'totalRiesgo',
            'totalEstudiantes', 'tasaAprobacion', 'gruposAlerta'
        ));
    }

    public function semaforo()
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) {
            return view('admin.rendimiento.semaforo', ['sinAnio' => true]);
        }

        $grupos = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->whereNull('periodo_id')
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()
            ->sortBy('promedio_grupo');

        return view('admin.rendimiento.semaforo', compact('grupos', 'schoolYear'));
    }

    // ── Semáforo PDF ──────────────────────────────────────────────────────
    public function semaforoPdf()
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupos = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->whereNull('periodo_id')
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()->sortBy('promedio_grupo');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.semaforo_pdf',
            compact('grupos', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('semaforo_rendimiento_' . now()->format('Ymd') . '.pdf');
    }

    // ── Excel semáforo ────────────────────────────────────────────────────
    public function semaforoExcel()
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupos = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->whereNull('periodo_id')
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()->sortBy('promedio_grupo');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Semáforo');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Semáforo de Rendimiento — ' . $schoolYear->nombre);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Grupo', 'Grado', 'Total Est.', 'Promedio', '% Aprobados', 'Semáforo'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        $semaforoColores = [
            'success' => ['fill' => 'd1fae5', 'font' => '065f46', 'label' => 'Verde (Bien)'],
            'warning' => ['fill' => 'fef9c3', 'font' => '854d0e', 'label' => 'Amarillo (Regular)'],
            'danger'  => ['fill' => 'fee2e2', 'font' => '991b1b', 'label' => 'Rojo (Alerta)'],
        ];

        foreach ($grupos->values() as $idx => $cache) {
            $row = $idx + 5;
            $pct = $cache->total_estudiantes > 0
                ? round(($cache->total_estudiantes - $cache->total_riesgo) / $cache->total_estudiantes * 100, 1)
                : null;
            $sem = $cache->semaforo ?? 'warning';
            $sc  = $semaforoColores[$sem] ?? $semaforoColores['warning'];

            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $cache->grupo?->seccion?->nombre ?? '—');
            $sheet->setCellValue('C' . $row, $cache->grupo?->grado?->nombre ?? '—');
            $sheet->setCellValue('D' . $row, $cache->total_estudiantes ?? 0);
            $sheet->setCellValue('E' . $row, $cache->promedio_grupo !== null ? number_format($cache->promedio_grupo, 1) : '—');
            $sheet->setCellValue('F' . $row, $pct !== null ? $pct . '%' : '—');
            $sheet->setCellValue('G' . $row, $sc['label']);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($idx % 2 === 0 ? 'f0f4ff' : 'ffffff');
            $sheet->getStyle('G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($sc['fill']);
            $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB($sc['font']);
            $sheet->getStyle('G' . $row)->getFont()->setBold(true);
        }

        foreach (['A'=>5,'B'=>14,'C'=>22,'D'=>12,'E'=>12,'F'=>14,'G'=>20] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'semaforo_rendimiento_' . now()->format('Ymd') . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function porArea(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) {
            return view('admin.rendimiento.por_area', ['sinAnio' => true]);
        }

        // Promedios académica vs técnica calculados desde calificaciones
        $academica = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as a', 'a.id', '=', 'ca.asignacion_id')
            ->join('matriculas as m', 'm.id', '=', 'ca.matricula_id')
            ->where('ca.school_year_id', $schoolYear->id)
            ->where('a.area', 'academica')
            ->whereNotNull('ca.nota_final')
            ->selectRaw('AVG(ca.nota_final) as promedio, COUNT(*) as total')
            ->first();

        $tecnica = DB::table('calificaciones as c')
            ->join('asignaciones as a', 'a.id', '=', 'c.asignacion_id')
            ->join('matriculas as m', 'm.id', '=', 'c.matricula_id')
            ->where('a.school_year_id', $schoolYear->id)
            ->where('a.area', 'tecnica')
            ->whereNotNull('c.nota_final')
            ->selectRaw('AVG(c.nota_final) as promedio, COUNT(*) as total')
            ->first();

        return view('admin.rendimiento.por_area', compact('schoolYear', 'academica', 'tecnica'));
    }

    // ── Rendimiento por Área PDF ──────────────────────────────────────────
    public function porAreaPdf()
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404, 'No hay año escolar activo.');

        $academica = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as a', 'a.id', '=', 'ca.asignacion_id')
            ->join('matriculas as m', 'm.id', '=', 'ca.matricula_id')
            ->where('ca.school_year_id', $schoolYear->id)
            ->where('a.area', 'academica')
            ->whereNotNull('ca.nota_final')
            ->selectRaw('AVG(ca.nota_final) as promedio, COUNT(*) as total')
            ->first();

        $tecnica = DB::table('calificaciones as c')
            ->join('asignaciones as a', 'a.id', '=', 'c.asignacion_id')
            ->join('matriculas as m', 'm.id', '=', 'c.matricula_id')
            ->where('a.school_year_id', $schoolYear->id)
            ->where('a.area', 'tecnica')
            ->whereNotNull('c.nota_final')
            ->selectRaw('AVG(c.nota_final) as promedio, COUNT(*) as total')
            ->first();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.por_area_pdf',
            compact('schoolYear', 'academica', 'tecnica', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('rendimiento_por_area_' . now()->format('Ymd') . '.pdf');
    }

    // ── Rendimiento por Área Excel ────────────────────────────────────────
    public function porAreaExcel()
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $academica = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as a', 'a.id', '=', 'ca.asignacion_id')
            ->where('ca.school_year_id', $schoolYear->id)->where('a.area', 'academica')
            ->whereNotNull('ca.nota_final')
            ->selectRaw('AVG(ca.nota_final) as promedio, COUNT(*) as total')->first();

        $tecnica = DB::table('calificaciones as c')
            ->join('asignaciones as a', 'a.id', '=', 'c.asignacion_id')
            ->where('a.school_year_id', $schoolYear->id)->where('a.area', 'tecnica')
            ->whereNotNull('c.nota_final')
            ->selectRaw('AVG(c.nota_final) as promedio, COUNT(*) as total')->first();

        // Por asignatura
        $porAsig = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as a', 'a.id', '=', 'ca.asignacion_id')
            ->join('asignaturas as asig', 'asig.id', '=', 'a.asignatura_id')
            ->where('ca.school_year_id', $schoolYear->id)->whereNotNull('ca.nota_final')
            ->selectRaw('asig.nombre, a.area, AVG(ca.nota_final) as promedio, COUNT(*) as total')
            ->groupBy('asig.id', 'asig.nombre', 'a.area')
            ->orderByDesc('promedio')->get();

        $ss = new Spreadsheet(); $ws = $ss->getActiveSheet()->setTitle('Por Área');
        $hdr = ['font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']]];

        $ws->setCellValue('A1', 'Rendimiento por Área — ' . $schoolYear->nombre);
        $ws->mergeCells('A1:E1'); $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $ws->setCellValue('A3', 'Resumen');
        $ws->getStyle('A3:E3')->applyFromArray($hdr);
        $ws->setCellValue('A4', 'Área');  $ws->setCellValue('B4', 'Promedio'); $ws->setCellValue('C4', 'Registros');
        $ws->getStyle('A4:C4')->getFont()->setBold(true);
        $ws->setCellValue('A5', 'Académica'); $ws->setCellValue('B5', round($academica?->promedio ?? 0, 2)); $ws->setCellValue('C5', $academica?->total ?? 0);
        $ws->setCellValue('A6', 'Técnica');   $ws->setCellValue('B6', round($tecnica?->promedio ?? 0, 2));   $ws->setCellValue('C6', $tecnica?->total ?? 0);

        $ws->setCellValue('A8', 'Detalle por Asignatura');
        $ws->getStyle('A8:E8')->applyFromArray($hdr);
        foreach (['#', 'Asignatura', 'Área', 'Promedio', 'Registros'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '9', $h);
        }
        $ws->getStyle('A9:E9')->getFont()->setBold(true);

        foreach ($porAsig as $i => $row) {
            $r = $i + 10;
            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $row->nombre);
            $ws->setCellValue("C{$r}", $row->area === 'tecnica' ? 'Técnica' : 'Académica');
            $ws->setCellValue("D{$r}", round($row->promedio, 2));
            $ws->setCellValue("E{$r}", $row->total);
            if ($row->promedio < 60) {
                $ws->getStyle("A{$r}:E{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('fee2e2');
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$r}:E{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
        }
        foreach (range('A', 'E') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($ss); $tmp = tempnam(sys_get_temp_dir(), 'rend_') . '.xlsx'; $writer->save($tmp);
        return response()->download($tmp, 'rendimiento_por_area_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function porGrupo(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupos     = Grupo::where('school_year_id', $schoolYear?->id ?? 0)
            ->where('activo', true)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get();

        $grupoId = $request->grupo_id;
        $detalle = null;

        if ($grupoId) {
            $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->whereNull('periodo_id')
                ->with(['grupo.grado', 'grupo.seccion'])
                ->first();

            if (!$detalle) {
                RendimientoCache::recalcularParaGrupo($grupoId, $schoolYear->id, null);
                $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
                    ->where('grupo_id', $grupoId)
                    ->whereNull('periodo_id')
                    ->with(['grupo.grado', 'grupo.seccion'])
                    ->first();
            }
        }

        return view('admin.rendimiento.por_grupo', compact('schoolYear', 'grupos', 'grupoId', 'detalle'));
    }

    // ── PDF por grupo ─────────────────────────────────────────────────────
    public function porGrupoPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupoId = $request->grupo_id;
        if (! $grupoId) abort(404, 'Selecciona un grupo.');

        $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->where('grupo_id', $grupoId)
            ->whereNull('periodo_id')
            ->with(['grupo.grado', 'grupo.seccion'])
            ->first();

        if (! $detalle) {
            RendimientoCache::recalcularParaGrupo($grupoId, $schoolYear->id, null);
            $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->whereNull('periodo_id')
                ->with(['grupo.grado', 'grupo.seccion'])
                ->first();
        }

        if (! $detalle) abort(404, 'Sin datos de rendimiento para este grupo.');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.por_grupo_pdf',
            compact('detalle', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($detalle->grupo?->nombre_completo ?? 'grupo');
        return $pdf->download("rendimiento_{$slug}.pdf");
    }

    // ── Excel por grupo ───────────────────────────────────────────────────
    public function porGrupoExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupoId = $request->grupo_id;
        if (! $grupoId) abort(404, 'Selecciona un grupo.');

        $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->where('grupo_id', $grupoId)
            ->whereNull('periodo_id')
            ->with(['grupo.grado', 'grupo.seccion'])
            ->first();

        if (! $detalle) {
            RendimientoCache::recalcularParaGrupo($grupoId, $schoolYear->id, null);
            $detalle = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->whereNull('periodo_id')
                ->with(['grupo.grado', 'grupo.seccion'])
                ->first();
        }

        if (! $detalle) abort(404, 'Sin datos de rendimiento para este grupo.');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $grupo = $detalle->grupo;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rendimiento Grupo');

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Rendimiento por Grupo — ' . ($grupo?->nombre_completo ?? '') . ' · ' . $schoolYear->nombre);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Generado: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $metrics = [
            ['Grupo',             $grupo?->nombre_completo ?? '—'],
            ['Total Estudiantes', $detalle->total_estudiantes],
            ['Promedio General',  number_format($detalle->promedio_grupo ?? 0, 2)],
            ['Aprobados',         $detalle->total_aprobados],
            ['Reprobados',        $detalle->total_reprobados],
            ['En Riesgo',         $detalle->total_riesgo],
            ['% Excelente (≥90)', $detalle->pct_excelente . '%'],
            ['% Bueno (70–89)',   $detalle->pct_bueno . '%'],
            ['% Regular (60–69)', $detalle->pct_regular . '%'],
            ['% Bajo (<60)',      $detalle->pct_bajo . '%'],
        ];

        $sheet->setCellValue('A5', 'Indicador');
        $sheet->setCellValue('B5', 'Valor');
        $sheet->getStyle('A5:B5')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
        $sheet->getStyle('A5:B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a6e');

        foreach ($metrics as $i => [$label, $value]) {
            $row = $i + 6;
            $bg = ($i % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle("A{$row}:B{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);

        $writer = new Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($grupo?->nombre_completo ?? 'grupo');
        $filename = "rendimiento_{$slug}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Dashboard de recuperaciones ───────────────────────────────────────
    public function recuperaciones(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return view('admin.rendimiento.recuperaciones', ['sinAnio' => true]);
        }

        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get();

        $grupoId = $request->grupo_id ?? $grupos->first()?->id;

        // Estudiantes con calificaciones reprobadas (situacion = 'R')
        $reprobados = CalificacionAcademica::with([
                'matricula.estudiante',
                'asignacion.asignatura',
            ])
            ->where('school_year_id', $schoolYear->id)
            ->where('situacion', 'R')
            ->when($grupoId, fn($q) => $q->whereHas('matricula',
                fn($m) => $m->where('grupo_id', $grupoId)
            ))
            ->get()
            ->groupBy('matricula_id');

        // Calcular cuántas materias reprueba cada estudiante
        $estudiantesRiesgo = $reprobados->map(function ($califs) {
            $est = $califs->first()->matricula?->estudiante;
            return [
                'estudiante'   => $est,
                'matricula'    => $califs->first()->matricula,
                'materias'     => $califs->map(fn($c) => $c->asignacion?->asignatura?->nombre)->filter()->sort()->values(),
                'total_repr'   => $califs->count(),
                'nota_minima'  => $califs->whereNotNull('nota_final')->min('nota_final'),
            ];
        })->sortByDesc('total_repr')->values();

        $resumen = [
            'total_reprobados' => $estudiantesRiesgo->count(),
            'reprueba_1'       => $estudiantesRiesgo->where('total_repr', 1)->count(),
            'reprueba_2'       => $estudiantesRiesgo->where('total_repr', 2)->count(),
            'reprueba_3plus'   => $estudiantesRiesgo->where('total_repr', '>=', 3)->count(),
        ];

        return view('admin.rendimiento.recuperaciones', compact(
            'schoolYear', 'grupos', 'grupoId',
            'estudiantesRiesgo', 'resumen'
        ));
    }

    // ── Recuperaciones PDF ────────────────────────────────────────────────
    public function recuperacionesPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupos  = Grupo::with(['grado', 'seccion'])->where('school_year_id', $schoolYear->id)->where('activo', true)->get();
        $grupoId = $request->grupo_id;
        $grupo   = $grupoId ? $grupos->find($grupoId) : null;

        $reprobados = CalificacionAcademica::with(['matricula.estudiante', 'asignacion.asignatura'])
            ->where('school_year_id', $schoolYear->id)
            ->where('situacion', 'R')
            ->when($grupoId, fn($q) => $q->whereHas('matricula', fn($m) => $m->where('grupo_id', $grupoId)))
            ->get()->groupBy('matricula_id');

        $estudiantesRiesgo = $reprobados->map(function ($califs) {
            return [
                'estudiante' => $califs->first()->matricula?->estudiante,
                'matricula'  => $califs->first()->matricula,
                'materias'   => $califs->map(fn($c) => $c->asignacion?->asignatura?->nombre)->filter()->sort()->values(),
                'total_repr' => $califs->count(),
                'nota_minima'=> $califs->whereNotNull('nota_final')->min('nota_final'),
            ];
        })->sortByDesc('total_repr')->values();

        $resumen = [
            'total_reprobados' => $estudiantesRiesgo->count(),
            'reprueba_1'       => $estudiantesRiesgo->where('total_repr', 1)->count(),
            'reprueba_2'       => $estudiantesRiesgo->where('total_repr', 2)->count(),
            'reprueba_3plus'   => $estudiantesRiesgo->where('total_repr', '>=', 3)->count(),
        ];

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.recuperaciones_pdf',
            compact('schoolYear', 'grupo', 'estudiantesRiesgo', 'resumen', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('recuperaciones_' . now()->format('Ymd') . '.pdf');
    }

    // ── Recuperaciones Excel ─────────────────────────────────────────────
    public function recuperacionesExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupoId = $request->grupo_id;

        $reprobados = CalificacionAcademica::with(['matricula.estudiante', 'asignacion.asignatura'])
            ->where('school_year_id', $schoolYear->id)->where('situacion', 'R')
            ->when($grupoId, fn($q) => $q->whereHas('matricula', fn($m) => $m->where('grupo_id', $grupoId)))
            ->get()->groupBy('matricula_id');

        $estudiantesRiesgo = $reprobados->map(function ($califs) {
            return [
                'estudiante' => $califs->first()->matricula?->estudiante,
                'materias'   => $califs->map(fn($c) => $c->asignacion?->asignatura?->nombre)->filter()->sort()->values(),
                'total_repr' => $califs->count(),
                'nota_minima'=> $califs->whereNotNull('nota_final')->min('nota_final'),
            ];
        })->sortByDesc('total_repr')->values();

        $ss = new Spreadsheet(); $ws = $ss->getActiveSheet()->setTitle('Recuperaciones');
        $hdr = ['font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']]];

        $ws->setCellValue('A1', 'Estudiantes en Recuperación — ' . $schoolYear->nombre);
        $ws->mergeCells('A1:E1'); $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        foreach (['#', 'Estudiante', 'Cédula', 'Materias Reprobadas', 'Nota Mínima'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:E3')->applyFromArray($hdr);

        foreach ($estudiantesRiesgo as $i => $data) {
            $row = $i + 4;
            $est = $data['estudiante'];
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", ($est?->apellidos ?? '') . ', ' . ($est?->nombres ?? ''));
            $ws->setCellValue("C{$row}", $est?->cedula ?? '—');
            $ws->setCellValue("D{$row}", $data['materias']->implode(', '));
            $ws->setCellValue("E{$row}", $data['nota_minima'] ?? '—');
            $bg = $data['total_repr'] >= 3 ? 'fee2e2' : ($data['total_repr'] == 2 ? 'fef3c7' : 'fff');
            if ($bg !== 'fff') {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
        }
        foreach (range('A', 'E') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($ss); $tmp = tempnam(sys_get_temp_dir(), 'recup_') . '.xlsx'; $writer->save($tmp);
        return response()->download($tmp, 'recuperaciones_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Docentes rezagados en notas ───────────────────────────────────────
    public function rezagados(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return view('admin.rendimiento.rezagados', ['sinAnio' => true]);
        }

        $periodos = $this->getPeriodos($schoolYear);
        $periodoId = $request->periodo_id ?? $periodos->where('activo', true)->first()?->id;

        // Todas las asignaciones activas del año
        $asignaciones = Asignacion::with(['docente', 'asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get();

        // Cuáles tienen calificaciones ingresadas (al menos 1)
        $conCalTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        $conCalAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        // Cuáles tienen calificaciones publicadas
        $publicadasTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        $publicadasAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        $items = $asignaciones->map(function ($asig) use ($conCalTec, $conCalAcad, $publicadasTec, $publicadasAcad) {
            $esTec    = $asig->area === 'tecnica';
            $conCal   = $esTec ? isset($conCalTec[$asig->id])   : isset($conCalAcad[$asig->id]);
            $publicada = $esTec ? isset($publicadasTec[$asig->id]) : isset($publicadasAcad[$asig->id]);
            return [
                'asignacion' => $asig,
                'con_cal'    => $conCal,
                'publicada'  => $publicada,
                'estado'     => $publicada ? 'publicado' : ($conCal ? 'sin_publicar' : 'sin_notas'),
            ];
        });

        $resumen = [
            'total'       => $items->count(),
            'publicados'  => $items->where('estado', 'publicado')->count(),
            'sin_publicar'=> $items->where('estado', 'sin_publicar')->count(),
            'sin_notas'   => $items->where('estado', 'sin_notas')->count(),
        ];

        // Solo mostrar los rezagados (sin notas o sin publicar)
        $rezagados = $items->whereIn('estado', ['sin_notas', 'sin_publicar'])
            ->sortBy(fn($i) => $i['estado'] === 'sin_notas' ? 0 : 1)
            ->values();

        return view('admin.rendimiento.rezagados', compact(
            'schoolYear', 'periodos', 'periodoId', 'rezagados', 'resumen'
        ));
    }

    // ── Informe de rezagados PDF ──────────────────────────────────────────
    public function rezagadosPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $periodos  = $this->getPeriodos($schoolYear);
        $periodoId = $request->periodo_id ?? $periodos->where('activo', true)->first()?->id;
        $periodo   = $periodoId ? $periodos->find($periodoId) : null;

        $asignaciones = Asignacion::with(['docente', 'asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear->id)->where('activo', true)->get();

        $conCalTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $conCalAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $publicadasTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $publicadasAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        $items = $asignaciones->map(function ($asig) use ($conCalTec, $conCalAcad, $publicadasTec, $publicadasAcad) {
            $esTec     = $asig->area === 'tecnica';
            $conCal    = $esTec ? isset($conCalTec[$asig->id])    : isset($conCalAcad[$asig->id]);
            $publicada = $esTec ? isset($publicadasTec[$asig->id]) : isset($publicadasAcad[$asig->id]);
            return ['asignacion' => $asig, 'estado' => $publicada ? 'publicado' : ($conCal ? 'sin_publicar' : 'sin_notas')];
        });

        $rezagados = $items->whereIn('estado', ['sin_notas', 'sin_publicar'])
            ->sortBy(fn($i) => $i['estado'] === 'sin_notas' ? 0 : 1)->values();
        $resumen = [
            'total' => $items->count(), 'publicados' => $items->where('estado', 'publicado')->count(),
            'sin_publicar' => $items->where('estado', 'sin_publicar')->count(),
            'sin_notas' => $items->where('estado', 'sin_notas')->count(),
        ];

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.rezagados_pdf',
            compact('schoolYear', 'periodo', 'rezagados', 'resumen', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('rezagados_' . now()->format('Ymd') . '.pdf');
    }

    // ── Excel rezagados ───────────────────────────────────────────────────
    public function rezagadosExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $periodos  = $this->getPeriodos($schoolYear);
        $periodoId = $request->periodo_id ?? $periodos->where('activo', true)->first()?->id;
        $periodo   = $periodoId ? $periodos->find($periodoId) : null;

        $asignaciones = Asignacion::with(['docente', 'asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear->id)->where('activo', true)->get();

        $conCalTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $conCalAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $publicadasTec  = \App\Models\Calificacion::where('periodo_id', $periodoId)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();
        $publicadasAcad = CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))->where('publicado', true)
            ->distinct('asignacion_id')->pluck('asignacion_id')->flip();

        $items = $asignaciones->map(function ($asig) use ($conCalTec, $conCalAcad, $publicadasTec, $publicadasAcad) {
            $esTec     = $asig->area === 'tecnica';
            $conCal    = $esTec ? isset($conCalTec[$asig->id])    : isset($conCalAcad[$asig->id]);
            $publicada = $esTec ? isset($publicadasTec[$asig->id]) : isset($publicadasAcad[$asig->id]);
            return ['asignacion' => $asig, 'estado' => $publicada ? 'publicado' : ($conCal ? 'sin_publicar' : 'sin_notas')];
        });

        $rezagados = $items->whereIn('estado', ['sin_notas', 'sin_publicar'])
            ->sortBy(fn($i) => $i['estado'] === 'sin_notas' ? 0 : 1)->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rezagados');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Docentes Rezagados en Notas — ' . $schoolYear->nombre . ($periodo ? ' · Período ' . $periodo->numero : ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'Generado: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Docente', 'Asignatura', 'Grupo', 'Tipo', 'Estado'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        $row = 5;
        foreach ($rezagados as $idx => $item) {
            $row++;
            $asig = $item['asignacion'];
            $bg = $item['estado'] === 'sin_notas' ? 'fee2e2' : 'fef9c3';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $asig->docente?->nombre_completo);
            $sheet->setCellValue('C' . $row, $asig->asignatura?->nombre);
            $sheet->setCellValue('D' . $row, $asig->grupo?->nombre_completo);
            $sheet->setCellValue('E' . $row, $asig->area === 'tecnica' ? 'Técnica' : 'Académica');
            $sheet->setCellValue('F' . $row, $item['estado'] === 'sin_notas' ? 'Sin Notas' : 'Sin Publicar');
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (['A'=>5,'B'=>30,'C'=>28,'D'=>20,'E'=>12,'F'=>14] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'rezagados_' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Comparativo por Período ───────────────────────────────────────────
    public function comparativo(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return view('admin.rendimiento.comparativo', ['sinAnio' => true]);
        }

        $grupos  = Grupo::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get();

        $grupoId = $request->grupo_id ?? $grupos->first()?->id;
        $chartData = [];
        $tablaData = [];

        if ($grupoId) {
            // Asignaciones del grupo con calificaciones académicas
            $asignaciones = \App\Models\Asignacion::with('asignatura')
                ->where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->where('activo', true)
                ->get();

            foreach ($asignaciones as $asig) {
                $nombre = $asig->asignatura?->nombre ?? 'S/N';
                $promedios = [];

                for ($p = 1; $p <= 4; $p++) {
                    // Promedio del grupo para esta asignatura en este período
                    // Nota período = promedio de los 4 avg_compC_pP no nulos
                    $rows = CalificacionAcademica::where('school_year_id', $schoolYear->id)
                        ->where('asignacion_id', $asig->id)
                        ->get(['avg_comp1_p' . $p, 'avg_comp2_p' . $p, 'avg_comp3_p' . $p, 'avg_comp4_p' . $p]);

                    $sumaGrupo = 0;
                    $cntGrupo  = 0;
                    foreach ($rows as $row) {
                        $vals = array_filter([
                            $row->{"avg_comp1_p{$p}"},
                            $row->{"avg_comp2_p{$p}"},
                            $row->{"avg_comp3_p{$p}"},
                            $row->{"avg_comp4_p{$p}"},
                        ], fn($v) => $v !== null);

                        if (count($vals) > 0) {
                            $sumaGrupo += array_sum($vals) / count($vals);
                            $cntGrupo++;
                        }
                    }

                    $promedios[$p] = $cntGrupo > 0 ? round($sumaGrupo / $cntGrupo, 1) : null;
                }

                $tablaData[] = [
                    'asignatura' => $nombre,
                    'p1' => $promedios[1],
                    'p2' => $promedios[2],
                    'p3' => $promedios[3],
                    'p4' => $promedios[4],
                ];
            }

            $chartData = [
                'labels'   => array_column($tablaData, 'asignatura'),
                'datasets' => [
                    ['label' => 'P1', 'data' => array_column($tablaData, 'p1'),
                     'backgroundColor' => 'rgba(37,99,235,0.75)'],
                    ['label' => 'P2', 'data' => array_column($tablaData, 'p2'),
                     'backgroundColor' => 'rgba(5,150,105,0.75)'],
                    ['label' => 'P3', 'data' => array_column($tablaData, 'p3'),
                     'backgroundColor' => 'rgba(217,119,6,0.75)'],
                    ['label' => 'P4', 'data' => array_column($tablaData, 'p4'),
                     'backgroundColor' => 'rgba(220,38,38,0.75)'],
                ],
            ];
        }

        return view('admin.rendimiento.comparativo', compact(
            'schoolYear', 'grupos', 'grupoId', 'chartData', 'tablaData'
        ));
    }

    // ── Ranking de Asignaturas ────────────────────────────────────────────
    public function rankingAsignaturas(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return view('admin.rendimiento.ranking_asignaturas', ['sinAnio' => true]);
        }

        $grupos  = Grupo::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get();

        $grupoId  = $request->grupo_id  ?? $grupos->first()?->id;
        $periodo  = (int) ($request->periodo ?? 1);
        $ranking  = [];

        if ($grupoId) {
            $asignaciones = \App\Models\Asignacion::with('asignatura')
                ->where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->where('activo', true)
                ->get();

            $p = $periodo;

            foreach ($asignaciones as $asig) {
                $rows = CalificacionAcademica::where('school_year_id', $schoolYear->id)
                    ->where('asignacion_id', $asig->id)
                    ->get(['avg_comp1_p' . $p, 'avg_comp2_p' . $p,
                           'avg_comp3_p' . $p, 'avg_comp4_p' . $p]);

                $sumaGrupo = 0;
                $cntGrupo  = 0;
                $reprobados = 0;

                foreach ($rows as $row) {
                    $vals = array_filter([
                        $row->{"avg_comp1_p{$p}"},
                        $row->{"avg_comp2_p{$p}"},
                        $row->{"avg_comp3_p{$p}"},
                        $row->{"avg_comp4_p{$p}"},
                    ], fn($v) => $v !== null);

                    if (count($vals) > 0) {
                        $notaEst = array_sum($vals) / count($vals);
                        $sumaGrupo += $notaEst;
                        $cntGrupo++;
                        if ($notaEst < 70) $reprobados++;
                    }
                }

                if ($cntGrupo === 0) continue;

                $promedio = round($sumaGrupo / $cntGrupo, 1);
                $pctReprobados = round($reprobados / $cntGrupo * 100, 1);

                $ranking[] = [
                    'asignatura'     => $asig->asignatura?->nombre ?? 'S/N',
                    'promedio'       => $promedio,
                    'total'          => $cntGrupo,
                    'reprobados'     => $reprobados,
                    'pct_reprobados' => $pctReprobados,
                    'semaforo'       => $promedio >= 70 ? 'success' : ($promedio >= 60 ? 'warning' : 'danger'),
                ];
            }

            usort($ranking, fn($a, $b) => $b['promedio'] <=> $a['promedio']);
        }

        return view('admin.rendimiento.ranking_asignaturas', compact(
            'schoolYear', 'grupos', 'grupoId', 'periodo', 'ranking'
        ));
    }

    // ── Tendencia por Grupo ───────────────────────────────────────────────
    public function tendenciaGrupo(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return view('admin.rendimiento.tendencia', ['sinAnio' => true]);
        }

        $grupos  = Grupo::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get();

        $grupoId  = $request->grupo_id ?? $grupos->first()?->id;
        $promediosPeriodos = [];
        $tendencia = null;

        if ($grupoId) {
            // Obtener todas las asignaciones del grupo
            $asigIds = \App\Models\Asignacion::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->where('activo', true)
                ->pluck('id');

            for ($p = 1; $p <= 4; $p++) {
                $rows = CalificacionAcademica::where('school_year_id', $schoolYear->id)
                    ->whereIn('asignacion_id', $asigIds)
                    ->get(['avg_comp1_p' . $p, 'avg_comp2_p' . $p,
                           'avg_comp3_p' . $p, 'avg_comp4_p' . $p]);

                $suma = 0;
                $cnt  = 0;

                foreach ($rows as $row) {
                    $vals = array_filter([
                        $row->{"avg_comp1_p{$p}"},
                        $row->{"avg_comp2_p{$p}"},
                        $row->{"avg_comp3_p{$p}"},
                        $row->{"avg_comp4_p{$p}"},
                    ], fn($v) => $v !== null);

                    if (count($vals) > 0) {
                        $suma += array_sum($vals) / count($vals);
                        $cnt++;
                    }
                }

                $promediosPeriodos["P{$p}"] = $cnt > 0 ? round($suma / $cnt, 1) : null;
            }

            // Calcular tendencia comparando primer y último período con dato
            $conDatos = array_filter($promediosPeriodos, fn($v) => $v !== null);
            if (count($conDatos) >= 2) {
                $vals = array_values($conDatos);
                $diff = end($vals) - reset($vals);
                $tendencia = [
                    'valor'    => round($diff, 1),
                    'positiva' => $diff >= 0,
                ];
            }
        }

        $chartData = [
            'labels' => array_keys($promediosPeriodos),
            'data'   => array_values($promediosPeriodos),
        ];

        return view('admin.rendimiento.tendencia', compact(
            'schoolYear', 'grupos', 'grupoId', 'promediosPeriodos', 'chartData', 'tendencia'
        ));
    }

    public function recalcular(Request $request)
    {
        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');

        $grupos = Grupo::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get();

        foreach ($grupos as $grupo) {
            RendimientoCache::recalcularParaGrupo($grupo->id, $schoolYear->id, null);
        }

        return response()->json(['ok' => true, 'grupos' => $grupos->count()]);
    }

    // ── Informe de rendimiento PDF ────────────────────────────────────────
    public function dashboardPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) abort(404, 'Sin año escolar activo.');

        $periodoId = $request->periodo_id;
        $periodo   = $periodoId ? Periodo::find($periodoId) : null;

        $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                              fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()
            ->sortBy('grupo.grado.nivel');

        if ($cacheData->isEmpty()) {
            $grupos = Grupo::where('school_year_id', $schoolYear->id)->where('activo', true)->get();
            foreach ($grupos as $g) {
                RendimientoCache::recalcularParaGrupo($g->id, $schoolYear->id, $periodoId ?: null);
            }
            $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                                  fn($q) => $q->whereNull('periodo_id'))
                ->with(['grupo.grado', 'grupo.seccion'])
                ->get()->sortBy('grupo.grado.nivel');
        }

        $promedioInst = $cacheData->avg('promedio_grupo');
        $totalRiesgo  = $cacheData->sum('total_riesgo');
        $totalEst     = $cacheData->sum('total_estudiantes');
        $tasaAprobacion = $totalEst > 0 ? round(($totalEst - $totalRiesgo) / $totalEst * 100, 1) : null;

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.rendimiento.dashboard_pdf',
            compact('schoolYear', 'periodo', 'cacheData', 'promedioInst',
                    'totalRiesgo', 'totalEst', 'tasaAprobacion', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $suffix = $periodo ? '_' . \Illuminate\Support\Str::slug($periodo->nombre) : '_anual';
        return $pdf->download("rendimiento{$suffix}.pdf");
    }

    // ── Excel del dashboard de rendimiento (todos los grupos) ────────────
    public function dashboardExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) abort(404);

        $periodoId = $request->periodo_id;

        $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
            ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                              fn($q) => $q->whereNull('periodo_id'))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->get()->sortBy('grupo.grado.nivel');

        if ($cacheData->isEmpty()) {
            $grupos = Grupo::where('school_year_id', $schoolYear->id)->where('activo', true)->get();
            foreach ($grupos as $g) {
                RendimientoCache::recalcularParaGrupo($g->id, $schoolYear->id, $periodoId ?: null);
            }
            $cacheData = RendimientoCache::where('school_year_id', $schoolYear->id)
                ->when($periodoId, fn($q) => $q->where('periodo_id', $periodoId),
                                  fn($q) => $q->whereNull('periodo_id'))
                ->with(['grupo.grado', 'grupo.seccion'])->get()->sortBy('grupo.grado.nivel');
        }

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Rendimiento');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'RENDIMIENTO POR GRUPO — ' . $schoolYear->nombre);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Grupo', 'Grado', 'Total Est.', 'Promedio', '% Aprobados', 'Semáforo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:G2')->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $semaforoColores = [
            'success' => ['fill' => 'd1fae5', 'font' => '065f46', 'label' => 'Verde'],
            'warning' => ['fill' => 'fef9c3', 'font' => '854d0e', 'label' => 'Amarillo'],
            'danger'  => ['fill' => 'fee2e2', 'font' => '991b1b', 'label' => 'Rojo'],
        ];

        foreach ($cacheData->values() as $i => $cache) {
            $r = $i + 3;
            $pct = $cache->total_estudiantes > 0
                ? round(($cache->total_estudiantes - $cache->total_riesgo) / $cache->total_estudiantes * 100, 1)
                : null;

            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $cache->grupo?->seccion?->nombre ?? '—');
            $sheet->setCellValue("C{$r}", $cache->grupo?->grado?->nombre ?? '—');
            $sheet->setCellValue("D{$r}", $cache->total_estudiantes ?? 0);
            $sheet->setCellValue("E{$r}", $cache->promedio_grupo !== null ? number_format($cache->promedio_grupo, 1) : '—');
            $sheet->setCellValue("F{$r}", $pct !== null ? $pct . '%' : '—');

            $sem = $cache->semaforo ?? 'warning';
            $sc  = $semaforoColores[$sem] ?? $semaforoColores['warning'];
            $sheet->setCellValue("G{$r}", $sc['label']);
            $sheet->getStyle("G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($sc['fill']);
            $sheet->getStyle("G{$r}")->getFont()->getColor()->setRGB($sc['font']);
            $sheet->getStyle("G{$r}")->getFont()->setBold(true);
            $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
        }

        foreach (range('A', 'G') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rend_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'Rendimiento_' . $schoolYear->nombre . '.xlsx')->deleteFileAfterSend();
    }
}
