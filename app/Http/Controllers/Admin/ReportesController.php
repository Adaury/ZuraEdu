<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\BoletinConfig;
use App\Models\Asistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportesController extends Controller
{
    /**
     * Main reports dashboard.
     */
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupos     = Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))
                          ->with(['grado', 'seccion'])
                          ->orderBy('grado_id')
                          ->get();

        // Stats summary
        $totalMatriculas   = Matricula::where('estado', 'activa')
                                ->when($schoolYear, fn($q) => $q->whereHas('grupo', fn($g) => $g->where('school_year_id', $schoolYear?->id)))
                                ->count();
        $totalAsignaciones = Asignacion::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))->count();

        // Aprobados/Reprobados — 1 query con groupBy en lugar de 2
        $situaciones = CalificacionAcademica::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))
                            ->whereIn('situacion', ['A', 'R'])
                            ->selectRaw('situacion, COUNT(*) as total')
                            ->groupBy('situacion')
                            ->pluck('total', 'situacion');
        $aprobados  = $situaciones['A'] ?? 0;
        $reprobados = $situaciones['R'] ?? 0;

        return view('admin.reportes.index', compact(
            'schoolYear', 'grupos', 'totalMatriculas', 'totalAsignaciones', 'aprobados', 'reprobados'
        ));
    }

    /**
     * Consolidated grades report by group — read-only supervisor view.
     */
    public function consolidado(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupoId    = $request->grupo_id;
        $ciclo      = $request->ciclo ?? 'primer';

        $grupos = Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))
                       ->with(['grado', 'seccion'])
                       ->orderBy('grado_id')->get();

        $grupo = $grupoId
            ? Grupo::with([
                'grado', 'seccion',
                'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
                'matriculas.estudiante',
              ])->find($grupoId)
            : null;

        $registros    = [];
        $asignaciones = [];

        if ($grupo) {
            $asignaciones = Asignacion::where('grupo_id', $grupo->id)
                ->where('school_year_id', $schoolYear?->id)
                ->with('asignatura', 'docente')
                ->get();

            $matriculaIds = $grupo->matriculas->pluck('id');

            $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
                ->whereIn('matricula_id', $matriculaIds)
                ->get()
                ->groupBy('matricula_id');

            foreach ($grupo->matriculas as $m) {
                $registros[$m->id] = [
                    'estudiante' => $m->estudiante,
                    'academicas' => $calAc[$m->id] ?? collect(),
                ];
            }
        }

        return view('admin.reportes.consolidado', compact(
            'schoolYear', 'grupos', 'grupo', 'asignaciones', 'registros', 'ciclo'
        ));
    }

    /**
     * PDF — Consolidated grades report.
     */
    public function consolidadoPdf(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear   = SchoolYear::actual();
        $ciclo        = $request->ciclo ?? 'primer';
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $asignaciones = Asignacion::where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id)
            ->with('asignatura', 'docente')
            ->get();

        $matriculaIds = $grupo->matriculas->pluck('id');

        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $registros = [];
        foreach ($grupo->matriculas as $m) {
            $cals     = $calAc[$m->id] ?? collect();
            $promedio = $cals->whereNotNull('nota_final')->avg('nota_final');
            $registros[$m->id] = [
                'estudiante' => $m->estudiante,
                'academicas' => $cals,
                'promedio'   => $promedio ? round($promedio, 2) : null,
                'situacion'  => $cals->where('situacion', 'R')->count() > 0 ? 'R' : ($cals->where('situacion', 'A')->count() > 0 ? 'A' : null),
            ];
        }

        $pdf = Pdf::loadView('admin.reportes.consolidado_pdf', compact(
            'schoolYear', 'grupo', 'asignaciones', 'registros', 'ciclo', 'boletinConfig'
        ))->setPaper('legal', 'landscape');

        $filename = 'consolidado_' . \Illuminate\Support\Str::slug($grupo->nombre_completo ?? 'grupo') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Situación final — aprobados / reprobados by group.
     */
    public function situacion(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupoId    = $request->grupo_id;

        $grupos = Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))
                       ->with(['grado', 'seccion'])
                       ->orderBy('grado_id')->get();

        $grupo = $grupoId
            ? Grupo::with([
                'grado', 'seccion',
                'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
                'matriculas.estudiante',
              ])->find($grupoId)
            : null;

        $datos = [];
        if ($grupo) {
            $matriculaIds = $grupo->matriculas->pluck('id');
            $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
                ->whereIn('matricula_id', $matriculaIds)
                ->get()
                ->groupBy('matricula_id');

            foreach ($grupo->matriculas as $m) {
                $regs        = $calAc[$m->id] ?? collect();
                $aprobadas   = $regs->where('situacion', 'A')->count();
                $reprobadas  = $regs->where('situacion', 'R')->count();
                $sinRegistro = $regs->whereNull('situacion')->count();
                $totalAsig   = $regs->count();
                $promedio    = $regs->whereNotNull('nota_final')->avg('nota_final');

                $datos[] = [
                    'matricula'         => $m,
                    'estudiante'        => $m->estudiante,
                    'aprobadas'         => $aprobadas,
                    'reprobadas'        => $reprobadas,
                    'sin_registro'      => $sinRegistro,
                    'total'             => $totalAsig,
                    'promedio'          => $promedio ? round($promedio, 2) : null,
                    'pct_aprobadas'     => $totalAsig > 0 ? round($aprobadas / $totalAsig * 100) : 0,
                    'situacion_general' => $reprobadas === 0 && $aprobadas > 0
                        ? 'Aprobado'
                        : ($reprobadas > 0 ? 'Con materias reprobadas' : 'Sin registro'),
                ];
            }
        }

        return view('admin.reportes.situacion', compact('schoolYear', 'grupos', 'grupo', 'datos'));
    }

    /**
     * PDF — Situación final.
     */
    public function situacionPdf(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear    = SchoolYear::actual();
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $matriculaIds = $grupo->matriculas->pluck('id');
        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $datos = [];
        foreach ($grupo->matriculas as $m) {
            $regs        = $calAc[$m->id] ?? collect();
            $aprobadas   = $regs->where('situacion', 'A')->count();
            $reprobadas  = $regs->where('situacion', 'R')->count();
            $totalAsig   = $regs->count();
            $promedio    = $regs->whereNotNull('nota_final')->avg('nota_final');

            $datos[] = [
                'matricula'         => $m,
                'estudiante'        => $m->estudiante,
                'aprobadas'         => $aprobadas,
                'reprobadas'        => $reprobadas,
                'total'             => $totalAsig,
                'promedio'          => $promedio ? round($promedio, 2) : null,
                'pct_aprobadas'     => $totalAsig > 0 ? round($aprobadas / $totalAsig * 100) : 0,
                'situacion_general' => $reprobadas === 0 && $aprobadas > 0
                    ? 'Aprobado'
                    : ($reprobadas > 0 ? 'Con materias reprobadas' : 'Sin registro'),
            ];
        }

        $pdf = Pdf::loadView('admin.reportes.situacion_pdf', compact(
            'schoolYear', 'grupo', 'datos', 'boletinConfig'
        ))->setPaper('letter', 'portrait');

        $filename = 'situacion_final_' . \Illuminate\Support\Str::slug($grupo->nombre_completo ?? 'grupo') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Asistencia institucional report.
     */
    public function asistencia(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupoId    = $request->grupo_id;

        $grupos = Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear?->id))
                       ->with(['grado', 'seccion'])
                       ->orderBy('grado_id')->get();

        $grupo = $grupoId
            ? Grupo::with([
                'grado', 'seccion',
                'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
                'matriculas.estudiante',
              ])->find($grupoId)
            : null;

        $datos = [];
        if ($grupo) {
            $matriculaIds = $grupo->matriculas->pluck('id');
            $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
                ->whereIn('matricula_id', $matriculaIds)
                ->get()
                ->groupBy('matricula_id');

            foreach ($grupo->matriculas as $m) {
                $regs    = $calAc[$m->id] ?? collect();
                $avgPct  = $regs->whereNotNull('pct_asistencia')->avg('pct_asistencia');
                $datos[] = [
                    'estudiante'     => $m->estudiante,
                    'avg_asistencia' => $avgPct ? round($avgPct, 1) : null,
                    'estado'         => $avgPct === null
                        ? 'Sin registro'
                        : ($avgPct >= 75 ? 'Normal' : 'Crítica'),
                ];
            }
        }

        return view('admin.reportes.asistencia', compact('schoolYear', 'grupos', 'grupo', 'datos'));
    }

    /**
     * Excel — Reporte consolidado de calificaciones por grupo.
     */
    public function consolidadoExcel(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear = SchoolYear::actual();

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $asignaciones = Asignacion::where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id)
            ->with('asignatura')
            ->get();

        $matriculaIds = $grupo->matriculas->pluck('id');

        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Consolidado');

        // ── Estilos de encabezado ────────────────────────────────────────
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dbeafe']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // ── Fila 1: título ───────────────────────────────────────────────
        $sheet->setCellValue('A1', 'REPORTE CONSOLIDADO DE CALIFICACIONES');
        $sheet->setCellValue('A2', 'Grupo: ' . $grupo->nombre_completo . ' | Año: ' . ($schoolYear->nombre ?? '—'));

        // ── Fila 3: encabezados ──────────────────────────────────────────
        $col = 1;
        $sheet->setCellValue([$col++, 3], '#');
        $sheet->setCellValue([$col++, 3], 'Estudiante');

        foreach ($asignaciones as $asi) {
            $sheet->setCellValue([$col++, 3], $asi->asignatura->nombre ?? '—');
        }
        $sheet->setCellValue([$col++, 3], 'Promedio');
        $sheet->setCellValue([$col, 3], 'Situación');

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray($headerStyle);

        // ── Filas de datos ───────────────────────────────────────────────
        $row = 4;
        $num = 1;
        foreach ($grupo->matriculas as $m) {
            $cals    = $calAc[$m->id] ?? collect();
            $promedio = $cals->whereNotNull('nota_final')->avg('nota_final');
            $sit      = $cals->where('situacion', 'R')->count() > 0 ? 'REPROBADO' : ($cals->whereNotNull('situacion')->count() > 0 ? 'APROBADO' : '—');

            $col = 1;
            $sheet->setCellValue([$col++, $row], $num++);
            $sheet->setCellValue([$col++, $row], $m->estudiante->nombre_completo ?? '—');

            foreach ($asignaciones as $asi) {
                $cal  = $cals->where('asignacion_id', $asi->id)->first();
                $nota = $cal?->nota_final;
                $sheet->setCellValue([$col++, $row], $nota ?? '');
            }

            $sheet->setCellValue([$col++, $row], $promedio ? round($promedio, 2) : '');
            $sheet->setCellValue([$col, $row], $sit);

            $row++;
        }

        // ── Estilos finales ──────────────────────────────────────────────
        $sheet->getStyle("A1:A2")->getFont()->setBold(true)->setSize(11);
        foreach (range(1, $col) as $c) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $sheet->freezePane('C4');

        $writer   = new Xlsx($spreadsheet);
        $tmp      = tempnam(sys_get_temp_dir(), 'rpt_') . '.xlsx';
        $writer->save($tmp);
        $filename = 'consolidado_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '_' . date('Y-m-d') . '.xlsx';

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Excel — Reporte de asistencia por grupo.
     */
    public function asistenciaExcel(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear = SchoolYear::actual();

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $matriculaIds = $grupo->matriculas->pluck('id');

        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->setCellValue('A1', 'REPORTE DE ASISTENCIA — ' . ($grupo->nombre_completo ?? '') . ' | ' . ($schoolYear->nombre ?? ''));

        $headers = ['#', 'Estudiante', 'P1 (%)', 'P2 (%)', 'P3 (%)', 'P4 (%)', 'Promedio (%)', 'Estado'];
        $sheet->fromArray([$headers], null, 'A2');
        $sheet->getStyle('A2:H2')->applyFromArray($headerStyle);

        $row = 3;
        $num = 1;
        foreach ($grupo->matriculas as $m) {
            $regs   = $calAc[$m->id] ?? collect();
            $pcts   = [];
            foreach ([1,2,3,4] as $p) {
                // pct_asistencia viene de CalificacionAcademica — usar promedio de las asig del grupo en ese período
                // Aquí promediamos pct_asistencia de todas las asig del estudiante en ese período
                $asigIds = $regs->pluck('asignacion_id')->unique();
                $asistPeriodo = Asistencia::whereIn('matricula_id', [$m->id])
                    ->whereIn('asignacion_id', $asigIds)
                    ->whereMonth('fecha', now()->month) // aproximación — el período no tiene mes exacto aquí
                    ->get();
                // Usamos pct_asistencia de CalificacionAcademica si está guardado
                $pcts["P{$p}"] = $regs->whereNotNull("asist_p{$p}")->avg(fn($r) => $r->{"asist_p{$p}"});
            }

            $avgPct = $regs->whereNotNull('pct_asistencia')->avg('pct_asistencia');
            $estado = $avgPct === null ? '—' : ($avgPct >= 75 ? 'Normal' : 'Crítica');

            $sheet->setCellValue([1, $row], $num++);
            $sheet->setCellValue([2, $row], $m->estudiante->nombre_completo ?? '—');
            $sheet->setCellValue([3, $row], $pcts['P1'] ? round($pcts['P1'], 1) : '');
            $sheet->setCellValue([4, $row], $pcts['P2'] ? round($pcts['P2'], 1) : '');
            $sheet->setCellValue([5, $row], $pcts['P3'] ? round($pcts['P3'], 1) : '');
            $sheet->setCellValue([6, $row], $pcts['P4'] ? round($pcts['P4'], 1) : '');
            $sheet->setCellValue([7, $row], $avgPct ? round($avgPct, 1) : '');
            $sheet->setCellValue([8, $row], $estado);

            $row++;
        }

        foreach (range(1, 8) as $c) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $sheet->freezePane('C3');

        $writer   = new Xlsx($spreadsheet);
        $tmp      = tempnam(sys_get_temp_dir(), 'asis_') . '.xlsx';
        $writer->save($tmp);
        $filename = 'asistencia_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '_' . date('Y-m-d') . '.xlsx';

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * PDF — Reporte de asistencia por grupo.
     */
    public function asistenciaPdf(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear    = SchoolYear::actual();
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $matriculaIds = $grupo->matriculas->pluck('id');
        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $datos = [];
        foreach ($grupo->matriculas as $m) {
            $regs   = $calAc[$m->id] ?? collect();
            $avgPct = $regs->whereNotNull('pct_asistencia')->avg('pct_asistencia');
            $datos[] = [
                'estudiante'     => $m->estudiante,
                'avg_asistencia' => $avgPct ? round($avgPct, 1) : null,
                'estado'         => $avgPct === null ? 'Sin registro'
                                    : ($avgPct >= 75 ? 'Regular' : 'Crítica'),
            ];
        }

        $pdf = Pdf::loadView('admin.reportes.asistencia_pdf', compact(
            'schoolYear', 'grupo', 'datos', 'boletinConfig'
        ))->setPaper('letter', 'portrait');

        $filename = 'asistencia_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Excel — Situación final (aprobados/reprobados).
     */
    public function situacionExcel(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear = SchoolYear::actual();

        $grupo = Grupo::with([
            'grado', 'seccion',
            'matriculas' => fn($q) => $q->where('estado', 'activa')->orderBy('numero_orden'),
            'matriculas.estudiante',
        ])->findOrFail($request->grupo_id);

        $matriculaIds = $grupo->matriculas->pluck('id');
        $calAc = CalificacionAcademica::where('school_year_id', $schoolYear?->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->get()
            ->groupBy('matricula_id');

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Situación Final');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '15803d']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'SITUACIÓN FINAL — ' . ($grupo->nombre_completo ?? '') . ' | ' . ($schoolYear->nombre ?? ''));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

        $headers = ['#', 'Estudiante', 'Aprobadas', 'Reprobadas', 'Total Asig.', 'Promedio', 'Situación General'];
        $sheet->fromArray([$headers], null, 'A2');
        $sheet->getStyle('A2:G2')->applyFromArray($hdrStyle);

        $row = 3;
        $num = 1;
        foreach ($grupo->matriculas as $m) {
            $regs       = $calAc[$m->id] ?? collect();
            $aprobadas  = $regs->where('situacion', 'A')->count();
            $reprobadas = $regs->where('situacion', 'R')->count();
            $total      = $regs->count();
            $promedio   = $regs->whereNotNull('nota_final')->avg('nota_final');
            $sit        = $reprobadas === 0 && $aprobadas > 0
                ? 'Aprobado' : ($reprobadas > 0 ? 'Con materias reprobadas' : 'Sin registro');

            $sheet->setCellValue("A{$row}", $num++);
            $sheet->setCellValue("B{$row}", $m->estudiante->nombre_completo ?? '—');
            $sheet->setCellValue("C{$row}", $aprobadas);
            $sheet->setCellValue("D{$row}", $reprobadas);
            $sheet->setCellValue("E{$row}", $total);
            $sheet->setCellValue("F{$row}", $promedio ? round($promedio, 2) : '');
            $sheet->setCellValue("G{$row}", $sit);

            if ($reprobadas > 0) {
                $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('dc2626');
                $sheet->getStyle("G{$row}")->getFont()->setBold(true);
            } elseif ($aprobadas > 0) {
                $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('15803d');
                $sheet->getStyle("G{$row}")->getFont()->setBold(true);
            }

            if ($num % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0fdf4');
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('B3');

        $writer   = new Xlsx($ss);
        $tmp      = tempnam(sys_get_temp_dir(), 'sit_') . '.xlsx';
        $writer->save($tmp);
        $filename = 'situacion_final_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '_' . date('Y-m-d') . '.xlsx';

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
