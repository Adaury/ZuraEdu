<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AsistenciaController extends Controller
{
    // ── Index: list asignaciones for current year ─────────────────────────
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $user    = auth()->user();
        $docente = null;
        if ($user->hasRole('Docente')) {
            $docente = \App\Models\Docente::where('user_id', $user->id)->first()
                ?? \App\Models\Docente::where('email', $user->email)->first();
        }

        $ciclo = $request->input('ciclo');
        $area  = $request->input('area');

        $query = Asignacion::with(['grupo.grado','grupo.seccion','asignatura','docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true);

        if ($docente) {
            $query->where('docente_id', $docente->id);
        }

        // Cycle/area filter for coordinators/admin
        if ($ciclo == 1) {
            $query->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [1, 3]));
        } elseif ($ciclo == 2) {
            $query->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [4, 6]));
            if ($area) {
                $query->where('area', $area);
            }
        }

        $asignaciones = $query->orderBy('grupo_id')->get();

        $contexto = null;
        if ($ciclo == 1) $contexto = 'Primer Ciclo (1ro–3ro)';
        elseif ($ciclo == 2 && $area === 'academica') $contexto = 'Segundo Ciclo — Área Académica';
        elseif ($ciclo == 2 && $area === 'tecnica')   $contexto = 'Segundo Ciclo — Área Técnica';

        return view('admin.asistencia.index', compact('asignaciones', 'schoolYear', 'docente', 'ciclo', 'area', 'contexto'));
    }

    // ── Registrar: attendance entry for a date ────────────────────────────
    public function registrar(Request $request, Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);

        $fecha = $request->fecha ?? now()->format('Y-m-d');

        // ── Verificar horario publicado (informativo, no bloqueante) ──────
        $diaEspanol = [
            'Monday'    => 'lunes',
            'Tuesday'   => 'martes',
            'Wednesday' => 'miercoles',
            'Thursday'  => 'jueves',
            'Friday'    => 'viernes',
        ];
        $diaKey     = $diaEspanol[Carbon::parse($fecha)->englishDayOfWeek] ?? null;
        $tieneClase = false;
        if ($diaKey) {
            $horarioActivo = Horario::where('estado', 'publicado')
                ->whereHas('detalles', fn($q) => $q
                    ->where('dia', $diaKey)
                    ->whereHas('asignacion', fn($q2) => $q2->where('id', $asignacion->id))
                )
                ->exists();
            $tieneClase = $horarioActivo;
        }
        // $tieneClase se pasa a la vista como contexto informativo

        $matriculas = $asignacion->grupo
            ->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        // Existing attendance records keyed by matricula_id
        $existentes = Asistencia::where('asignacion_id', $asignacion->id)
            ->where('fecha', $fecha)
            ->pluck('estado', 'matricula_id');

        // Cumulative absence stats per student (for inline risk badges)
        $matriculaIds = $matriculas->pluck('id');
        $totalesPorMatricula = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereIn('matricula_id', $matriculaIds)
            ->selectRaw("matricula_id, COUNT(*) as total, SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as ausentes")
            ->groupBy('matricula_id')
            ->get()
            ->keyBy('matricula_id')
            ->map(fn($r) => [
                'total'    => (int) $r->total,
                'ausentes' => (int) $r->ausentes,
                'pct'      => $r->total > 0 ? round((($r->total - $r->ausentes) / $r->total) * 100) : null,
            ]);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        return view('admin.asistencia.registrar', compact(
            'asignacion', 'matriculas', 'existentes', 'fecha', 'totalesPorMatricula', 'tieneClase'
        ));
    }

    // ── Guardar: persist attendance records ───────────────────────────────
    public function guardar(Request $request, Asignacion $asignacion)
    {
        $this->authorize('ingresarAsistencia', $asignacion);

        $request->validate([
            'fecha'       => 'required|date',
            'asistencia'  => 'required|array',
            'asistencia.*'=> 'required|in:presente,ausente,tarde,excusa,retiro',
        ]);

        $fecha = $request->fecha;

        foreach ($request->asistencia as $matriculaId => $estado) {
            Asistencia::updateOrCreate(
                [
                    'fecha'         => $fecha,
                    'matricula_id'  => $matriculaId,
                    'asignacion_id' => $asignacion->id,
                ],
                [
                    'estado'          => $estado,
                    'registrado_por'  => auth()->id(),
                ]
            );
        }

        // ── Alerta de asistencia crítica (<75%) ───────────────────────────
        $this->verificarAlertasAsistencia($request->asistencia, $asignacion);

        return redirect()
            ->route('admin.asistencia.registrar', ['asignacion' => $asignacion->id, 'fecha' => $fecha])
            ->with('success', 'Asistencia guardada correctamente para el ' . $fecha . '.');
    }

    // ── Historial: calendar grid of attendance ────────────────────────────
    public function historial(Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        $matriculas = $asignacion->grupo
            ->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        // All asistencia records for this asignacion
        $todasAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->orderBy('fecha')
            ->get();

        // Unique dates
        $fechas = $todasAsistencias->pluck('fecha')->unique()->sort()->values();

        // Build matrix: [matricula_id][fecha_string] = estado
        $matriz = [];
        foreach ($matriculas as $m) {
            $matriz[$m->id] = [];
        }

        foreach ($todasAsistencias as $a) {
            $fechaStr = $a->fecha->format('Y-m-d');
            $matriz[$a->matricula_id][$fechaStr] = $a->estado;
        }

        // Stats per student
        $stats = [];
        foreach ($matriculas as $m) {
            $registros = $todasAsistencias->where('matricula_id', $m->id);
            $total     = $registros->count();

            $stats[$m->id] = [
                'total'       => $total,
                'presente'    => $registros->where('estado', 'presente')->count(),
                'ausente'     => $registros->where('estado', 'ausente')->count(),
                'tardanza'    => $registros->where('estado', 'tardanza')->count(),
                'justificado' => $registros->where('estado', 'justificado')->count(),
                'pct_asistencia' => $total > 0
                    ? round(
                        ($registros->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count() / $total) * 100,
                        1
                    )
                    : null,
            ];
        }

        return view('admin.asistencia.historial', compact(
            'asignacion', 'matriculas', 'fechas', 'matriz', 'stats'
        ));
    }

    // ── Reporte estudiante: all attendance across asignaciones ───────────
    public function reporteEstudiante(Matricula $matricula)
    {
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion']);

        // Asistencias grouped by asignacion
        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->orderBy('fecha')
            ->get();

        $porAsignacion = $asistencias->groupBy('asignacion_id');

        $stats = [];
        foreach ($porAsignacion as $asignacionId => $registros) {
            $total = $registros->count();
            $stats[$asignacionId] = [
                'asignacion'  => $registros->first()->asignacion,
                'total'       => $total,
                'presente'    => $registros->where('estado', 'presente')->count(),
                'ausente'     => $registros->where('estado', 'ausente')->count(),
                'tardanza'    => $registros->where('estado', 'tardanza')->count(),
                'justificado' => $registros->where('estado', 'justificado')->count(),
                'pct_asistencia' => $total > 0
                    ? round(
                        ($registros->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count() / $total) * 100,
                        1
                    )
                    : null,
            ];
        }

        return view('admin.asistencia.reporte_estudiante', compact('matricula', 'stats'));
    }

    // ── Monthly grid view ─────────────────────────────────────────────────
    public function grilla(Request $request, Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $schoolYear = SchoolYear::actual();
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $mes  = (int) ($request->mes  ?? now()->month);
        $anio = (int) ($request->anio ?? now()->year);

        $diasEnMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->format('Y-m-d');
        $fechaFin    = Carbon::createFromDate($anio, $mes, $diasEnMes)->format('Y-m-d');

        $asistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->groupBy('matricula_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($r) => Carbon::parse($r->fecha)->day));

        return view('admin.asistencia.grilla', compact(
            'asignacion', 'matriculas', 'asistencias', 'mes', 'anio', 'diasEnMes', 'schoolYear'
        ));
    }

    // ── Grilla PDF (vista mensual) ───────────────────────────────────────
    public function grillaPdf(Request $request, Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $mes  = (int) ($request->mes  ?? now()->month);
        $anio = (int) ($request->anio ?? now()->year);

        $diasEnMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->format('Y-m-d');
        $fechaFin    = Carbon::createFromDate($anio, $mes, $diasEnMes)->format('Y-m-d');

        $asistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->groupBy('matricula_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($r) => Carbon::parse($r->fecha)->day));

        $nombreMes = Carbon::createFromDate($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY');
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.asistencia.grilla_pdf', compact(
            'asignacion', 'matriculas', 'asistencias', 'mes', 'anio', 'diasEnMes', 'nombreMes', 'inst'
        ))->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'asistencia');
        return $pdf->download("grilla_{$slug}_{$mes}_{$anio}.pdf");
    }

    // ── Lista de asistencia en blanco PDF ────────────────────────────────
    public function listaBlancoPdf(Request $request, Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $numColumnas = max(5, (int)($request->columnas ?? 10));  // número de fechas/clases en blanco
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asistencia.lista_blanco_pdf',
            compact('asignacion', 'matriculas', 'numColumnas', 'inst', 'config', 'sy')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug(($asignacion->asignatura?->nombre ?? 'asistencia') . '-' . ($asignacion->grupo?->nombre_completo ?? ''));
        return $pdf->download("lista_asistencia_{$slug}.pdf");
    }

    // ── AJAX: toggle single cell ──────────────────────────────────────────
    public function toggleEstado(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'matricula_id'  => 'required|exists:matriculas,id',
            'fecha'         => 'required|date',
            'estado'        => 'required|in:presente,ausente,tarde,excusa,retiro',
        ]);

        $asignacion = Asignacion::findOrFail($request->asignacion_id);
        $this->authorize('ingresarAsistencia', $asignacion);

        $record = Asistencia::updateOrCreate(
            [
                'asignacion_id' => $request->asignacion_id,
                'matricula_id'  => $request->matricula_id,
                'fecha'         => $request->fecha,
            ],
            [
                'estado'         => $request->estado,
                'registrado_por' => auth()->id(),
            ]
        );

        return response()->json(['success' => true, 'estado' => $record->estado]);
    }

    // ── AJAX: mark all for a date ─────────────────────────────────────────
    public function marcarTodos(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'fecha'         => 'required|date',
            'estado'        => 'required|in:presente,ausente,tarde,excusa,retiro',
        ]);

        $asignacion = Asignacion::findOrFail($request->asignacion_id);
        $this->authorize('ingresarAsistencia', $asignacion);
        $matriculas = $asignacion->grupo->matriculas()->activas()->pluck('id');

        foreach ($matriculas as $mid) {
            Asistencia::updateOrCreate(
                [
                    'asignacion_id' => $request->asignacion_id,
                    'matricula_id'  => $mid,
                    'fecha'         => $request->fecha,
                ],
                [
                    'estado'         => $request->estado,
                    'registrado_por' => auth()->id(),
                ]
            );
        }

        return response()->json(['success' => true, 'count' => $matriculas->count()]);
    }

    // ── Reporte de asistencia por asignacion ──────────────────────────────
    public function reporte(Request $request, Asignacion $asignacion)
    {
        $schoolYear = SchoolYear::actual();
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        // Bulk load — one query instead of N (one per matricula)
        $allAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()
            ->groupBy('matricula_id');

        $stats = [];
        foreach ($matriculas as $m) {
            $asis  = $allAsistencias->get($m->id, collect());
            $total = $asis->count();
            $presentes = $asis->whereIn('estado', ['presente', 'tarde'])->count();
            $pct      = $total > 0 ? round(($presentes / $total) * 100, 1) : null;
            $stats[$m->id] = [
                'matricula'  => $m,
                'presente'   => $asis->where('estado', 'presente')->count(),
                'ausente'    => $asis->where('estado', 'ausente')->count(),
                'tarde'      => $asis->where('estado', 'tarde')->count(),
                'excusa'     => $asis->where('estado', 'excusa')->count(),
                'retiro'     => $asis->where('estado', 'retiro')->count(),
                'total'      => $total,
                'porcentaje' => $pct,
                'alerta'     => $pct !== null && $pct < 75,
            ];
        }

        return view('admin.asistencia.reporte', compact('asignacion', 'stats', 'schoolYear'));
    }

    // ── Historial de asistencia Excel (grilla día a día) ─────────────────
    public function historialExcel(Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $todasAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->orderBy('fecha')->get();

        $fechas = $todasAsistencias->pluck('fecha')->unique()->sort()->values();
        $matriz = [];
        foreach ($matriculas as $m) $matriz[$m->id] = [];
        foreach ($todasAsistencias as $a) {
            $matriz[$a->matricula_id][$a->fecha->format('Y-m-d')] = $a->estado;
        }

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Historial');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        // Título
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $fechas->count());
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'HISTORIAL ASISTENCIA: ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

        // Encabezados
        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Estudiante');
        foreach ($fechas as $i => $fecha) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $i);
            $sheet->setCellValue("{$col}2", Carbon::parse($fecha)->format('d/m'));
            $sheet->getColumnDimension($col)->setWidth(6);
        }
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $fechas->count());
        $pctCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $fechas->count());
        $sheet->setCellValue("{$totalCol}2", 'Asist.');
        $sheet->setCellValue("{$pctCol}2", '%');

        $headerRange = "A2:{$pctCol}2";
        $sheet->getStyle($headerRange)->applyFromArray($hdrStyle);
        $sheet->getColumnDimension('B')->setWidth(24);

        // Mapeo de estados a siglas
        $siglas = ['presente'=>'P', 'ausente'=>'A', 'tardanza'=>'T', 'justificado'=>'J', 'retiro'=>'R', 'excusa'=>'E', 'tarde'=>'T'];
        $colors = ['presente'=>'dcfce7', 'ausente'=>'fee2e2', 'tardanza'=>'fef9c3', 'justificado'=>'dbeafe', 'tarde'=>'fef9c3'];

        foreach ($matriculas as $i => $m) {
            $row      = $i + 3;
            $registros= $todasAsistencias->where('matricula_id', $m->id);
            $presentes= $registros->whereIn('estado', ['presente', 'tardanza', 'tarde', 'justificado'])->count();
            $total    = $registros->count();
            $pct      = $total > 0 ? round($presentes / $total * 100, 1) : null;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $m->estudiante->nombre_completo ?? '');

            foreach ($fechas as $j => $fecha) {
                $col    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + $j);
                $estado = $matriz[$m->id][$fecha->format('Y-m-d')] ?? '';
                $sigla  = $siglas[$estado] ?? '';
                $sheet->setCellValue("{$col}{$row}", $sigla);
                if ($estado && isset($colors[$estado])) {
                    $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($colors[$estado]);
                }
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            $sheet->setCellValue("{$totalCol}{$row}", $presentes . '/' . $total);
            $sheet->setCellValue("{$pctCol}{$row}", $pct !== null ? $pct . '%' : '');
            if ($pct !== null && $pct < 75) {
                $sheet->getStyle("{$pctCol}{$row}")->getFont()->getColor()->setRGB('dc2626');
                $sheet->getStyle("{$pctCol}{$row}")->getFont()->setBold(true);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->freezePane('C3');

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'hist_') . '.xlsx';
        $writer->save($tmp);
        $slug = \Illuminate\Support\Str::slug(($asignacion->asignatura?->nombre ?? '') . '-' . ($asignacion->grupo?->nombre_completo ?? ''));

        return response()->download($tmp, "historial_asistencia_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Historial de asistencia PDF (grilla día a día) ───────────────────
    public function historialPdf(Asignacion $asignacion)
    {
        $this->authorize('verAsistencia', $asignacion);
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $todasAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->orderBy('fecha')->get();

        $fechas = $todasAsistencias->pluck('fecha')->unique()->sort()->values();
        $matriz = [];
        foreach ($matriculas as $m) $matriz[$m->id] = [];
        foreach ($todasAsistencias as $a) {
            $matriz[$a->matricula_id][$a->fecha->format('Y-m-d')] = $a->estado;
        }

        $stats = [];
        foreach ($matriculas as $m) {
            $registros = $todasAsistencias->where('matricula_id', $m->id);
            $total     = $registros->count();
            $presentes = $registros->whereIn('estado', ['presente', 'tarde'])->count();
            $stats[$m->id] = [
                'total'     => $total,
                'presentes' => $presentes,
                'ausentes'  => $registros->where('estado', 'ausente')->count(),
                'tarde'     => $registros->where('estado', 'tarde')->count(),
                'excusa'    => $registros->where('estado', 'excusa')->count(),
                'pct'       => $total > 0 ? round($presentes / $total * 100, 1) : null,
            ];
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asistencia.historial_pdf',
            compact('asignacion', 'matriculas', 'fechas', 'matriz', 'stats', 'inst')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug(($asignacion->asignatura?->nombre ?? '') . '-' . ($asignacion->grupo?->nombre_completo ?? ''));
        return $pdf->download("historial_asistencia_{$slug}.pdf");
    }

    // ── Reporte de asistencia PDF ────────────────────────────────────────
    public function reportePdf(Request $request, Asignacion $asignacion)
    {
        $schoolYear = SchoolYear::actual();
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $allAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()->groupBy('matricula_id');

        $stats = [];
        foreach ($matriculas as $m) {
            $asis  = $allAsistencias->get($m->id, collect());
            $total = $asis->count();
            $presentes = $asis->whereIn('estado', ['presente', 'tarde'])->count();
            $pct = $total > 0 ? round(($presentes / $total) * 100, 1) : null;
            $stats[$m->id] = [
                'matricula'  => $m,
                'presente'   => $asis->where('estado', 'presente')->count(),
                'ausente'    => $asis->where('estado', 'ausente')->count(),
                'tarde'      => $asis->where('estado', 'tarde')->count(),
                'excusa'     => $asis->where('estado', 'excusa')->count(),
                'total'      => $total,
                'porcentaje' => $pct,
                'alerta'     => $pct !== null && $pct < 75,
            ];
        }

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asistencia.reporte_pdf',
            compact('asignacion', 'stats', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'asistencia');
        return $pdf->download("asistencia_{$slug}.pdf");
    }

    // ── Reporte de asistencia por asignación (Excel) ──────────────────────
    public function reporteExcel(Request $request, Asignacion $asignacion)
    {
        $asignacion->load(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente']);

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $allAsistencias = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()->groupBy('matricula_id');

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Asistencia');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $title = 'ASISTENCIA: ' . ($asignacion->asignatura?->nombre ?? '') . ' — '
               . ($asignacion->grupo?->grado?->nombre ?? '') . ' ' . ($asignacion->grupo?->seccion?->nombre ?? '');
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Estudiante', 'Presente', 'Ausente', 'Tardanza', 'Excusa', 'Retiro', 'Total', '% Asistencia'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:I2')->applyFromArray($hdrStyle);

        foreach ($matriculas as $i => $m) {
            $row  = $i + 3;
            $asis = $allAsistencias->get($m->id, collect());
            $total    = $asis->count();
            $presentes= $asis->whereIn('estado', ['presente', 'tarde'])->count();
            $pct      = $total > 0 ? round($presentes / $total * 100, 1) : null;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $m->estudiante->nombre_completo ?? '');
            $sheet->setCellValue("C{$row}", $asis->where('estado', 'presente')->count());
            $sheet->setCellValue("D{$row}", $asis->where('estado', 'ausente')->count());
            $sheet->setCellValue("E{$row}", $asis->where('estado', 'tardanza')->count() + $asis->where('estado', 'tarde')->count());
            $sheet->setCellValue("F{$row}", $asis->where('estado', 'excusa')->count());
            $sheet->setCellValue("G{$row}", $asis->where('estado', 'retiro')->count());
            $sheet->setCellValue("H{$row}", $total);
            $sheet->setCellValue("I{$row}", $pct !== null ? $pct . '%' : '');

            if ($pct !== null && $pct < 75) {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('dc2626');
                $sheet->getStyle("I{$row}")->getFont()->setBold(true);
            }
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'asis_') . '.xlsx';
        $writer->save($tmp);
        $slug = \Illuminate\Support\Str::slug(($asignacion->asignatura?->nombre ?? 'asistencia') . '-' . ($asignacion->grupo?->nombre_completo ?? ''));

        return response()->download($tmp, "asistencia_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Reporte mensual de asistencia por estudiante (PDF) ─────────────────
    public function reporteMensualPdf(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'mes'          => 'required|integer|between:1,12',
            'anio'         => 'required|integer|min:2020|max:2099',
        ]);

        $matricula = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->findOrFail($request->matricula_id);

        $mes  = (int) $request->mes;
        $anio = (int) $request->anio;

        $asistencias = Asistencia::where('matricula_id', $matricula->id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->with('asignacion.asignatura')
            ->orderBy('fecha')
            ->get();

        // Group by asignacion
        $porAsignacion = $asistencias->groupBy('asignacion_id')->map(function ($items) {
            $total    = $items->count();
            $presente = $items->whereIn('estado', ['presente', 'tarde'])->count();
            return [
                'asignatura' => $items->first()->asignacion?->asignatura?->nombre ?? '—',
                'registros'  => $items->sortBy('fecha'),
                'total'      => $total,
                'presente'   => $presente,
                'ausente'    => $items->where('estado', 'ausente')->count(),
                'tarde'      => $items->where('estado', 'tarde')->count(),
                'excusa'     => $items->where('estado', 'excusa')->count(),
                'pct'        => $total > 0 ? round(($presente / $total) * 100, 1) : null,
            ];
        });

        $nombreMes = \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asistencia.reporte_mensual_pdf',
            compact('matricula', 'mes', 'anio', 'nombreMes', 'porAsignacion')
        )->setPaper('letter', 'portrait');

        $apellido = $matricula->estudiante?->apellidos ?? 'estudiante';
        return $pdf->download("asistencia_{$apellido}_{$nombreMes}.pdf");
    }

    // ── Reporte mensual Excel ─────────────────────────────────────────────
    public function reporteMensualExcel(Request $request)
    {
        $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'mes'          => 'required|integer|between:1,12',
            'anio'         => 'required|integer|min:2020|max:2099',
        ]);

        $matricula = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->findOrFail($request->matricula_id);

        $mes  = (int) $request->mes;
        $anio = (int) $request->anio;

        $asistencias = Asistencia::where('matricula_id', $matricula->id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->with('asignacion.asignatura')
            ->orderBy('fecha')
            ->get();

        $porAsignacion = $asistencias->groupBy('asignacion_id')->map(function ($items) {
            $total    = $items->count();
            $presente = $items->whereIn('estado', ['presente', 'tarde'])->count();
            return [
                'asignatura' => $items->first()->asignacion?->asignatura?->nombre ?? '—',
                'total'      => $total,
                'presente'   => $presente,
                'ausente'    => $items->where('estado', 'ausente')->count(),
                'tarde'      => $items->where('estado', 'tarde')->count(),
                'excusa'     => $items->where('estado', 'excusa')->count(),
                'pct'        => $total > 0 ? round(($presente / $total) * 100, 1) : null,
            ];
        });

        $nombreMes = Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY');

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Asistencia');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->setCellValue('A1', 'Reporte de Asistencia — ' . $matricula->estudiante?->nombre_completo . ' — ' . $nombreMes);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->mergeCells('A1:G1');

        foreach (['Asignatura', 'Total Clases', 'Presentes', 'Tardanzas', 'Ausentes', 'Excusas', '% Asistencia'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        $row = 4;
        foreach ($porAsignacion as $data) {
            $ws->setCellValue("A{$row}", $data['asignatura']);
            $ws->setCellValue("B{$row}", $data['total']);
            $ws->setCellValue("C{$row}", $data['presente']);
            $ws->setCellValue("D{$row}", $data['tarde']);
            $ws->setCellValue("E{$row}", $data['ausente']);
            $ws->setCellValue("F{$row}", $data['excusa']);
            $ws->setCellValue("G{$row}", $data['pct'] !== null ? $data['pct'] . '%' : '—');

            if ($data['pct'] !== null && $data['pct'] < 80) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('fee2e2');
            } elseif ($row % 2 === 0) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'asist_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($matricula->estudiante?->apellidos ?? 'estudiante');
        return response()->download($tmp, "asistencia_{$slug}_{$mes}_{$anio}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Import: show form ─────────────────────────────────────────────────
    public function import(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $user    = auth()->user();
        $docente = null;
        if ($user->hasRole('Docente')) {
            $docente = Docente::where('user_id', $user->id)->first()
                ?? Docente::where('email', $user->email)->first();
        }

        $query = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true);

        if ($docente) {
            $query->where('docente_id', $docente->id);
        }

        $asignaciones = $query->get()
            ->sortBy(fn ($a) => ($a->grupo->grado->nombre ?? '') . ' ' . ($a->grupo->seccion->nombre ?? '') . ' ' . ($a->asignatura->nombre ?? ''));

        return view('admin.asistencia.import', compact('schoolYear', 'asignaciones'));
    }

    // ── Import: download template ─────────────────────────────────────────
    public function downloadTemplate(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $format       = $request->input('format', 'csv');
        $asignacion   = null;
        $matriculas   = collect();

        if ($asignacionId) {
            $asignacion = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])->find($asignacionId);
            if ($asignacion) {
                $matriculas = $asignacion->grupo->matriculas()
                    ->activas()
                    ->with('estudiante')
                    ->orderBy('numero_orden')
                    ->get();
            }
        }

        $headers = ['numero_matricula', 'cedula', 'nombres', 'apellidos', 'fecha', 'estado'];
        $estados = 'presente | ausente | tardanza | excusa | retiro';
        $rows    = [];

        if ($matriculas->count()) {
            foreach ($matriculas as $mat) {
                $rows[] = [
                    $mat->numero_matricula ?? '',
                    $mat->estudiante->cedula ?? '',
                    $mat->estudiante->nombres ?? '',
                    $mat->estudiante->apellidos ?? '',
                    now()->format('Y-m-d'),
                    'presente',
                ];
            }
        } else {
            $rows[] = ['2024-00001', '001-1234567-8', 'Juan',  'Pérez',    now()->format('Y-m-d'), 'presente'];
            $rows[] = ['2024-00002', '001-2345678-9', 'María', 'González', now()->format('Y-m-d'), 'ausente'];
        }

        $nombreBase = 'plantilla_asistencia' . ($asignacion ? '_' . \Illuminate\Support\Str::slug($asignacion->asignatura->nombre ?? 'asistencia') : '');

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Asistencia');
            $sheet->fromArray([$headers], null, 'A1');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            ];
            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

            // Freeze header and set column widths
            $sheet->freezePane('A2');
            foreach (['A' => 20, 'B' => 18, 'C' => 22, 'D' => 22, 'E' => 14, 'F' => 12] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // Nota row with state hint
            $sheet->setCellValue('F1', 'estado (' . $estados . ')');
            $sheet->fromArray($rows, null, 'A2');

            // Gray out reference columns (nombres/apellidos) with note
            $refStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f3f4f6']], 'font' => ['color' => ['rgb' => '6b7280']]];
            $lastRow  = count($rows) + 1;
            if ($lastRow > 1) {
                $sheet->getStyle("C2:D{$lastRow}")->applyFromArray($refStyle);
            }

            $writer = new Xlsx($spreadsheet);
            $tmp    = tempnam(sys_get_temp_dir(), 'asistencia_') . '.xlsx';
            $writer->save($tmp);

            return response()->download($tmp, $nombreBase . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV (UTF-8 BOM)
        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombreBase . '.csv"',
        ]);
    }

    // ── Import: process uploaded file ─────────────────────────────────────
    public function importStore(Request $request)
    {
        $request->validate([
            'archivo'       => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'asignacion_id' => 'required|exists:asignaciones,id',
        ]);

        $asignacion = Asignacion::with(['grupo'])->findOrFail($request->asignacion_id);
        $archivo    = $request->file('archivo');
        $ext        = strtolower($archivo->getClientOriginalExtension());

        // ── Read rows ────────────────────────────────────────────────────
        $rows = [];
        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname());
            $sheet       = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $header      = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), null));
            }
        } else {
            $raw      = file_get_contents($archivo->getPathname());
            $encoding = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
            }
            $lines  = array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF"))));
            $lines  = array_values($lines);
            $delim  = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
            $header = array_map('trim', str_getcsv($lines[0] ?? '', $delim));
            $header = array_map('strtolower', $header);
            foreach (array_slice($lines, 1) as $line) {
                if (trim($line) === '') continue;
                $cols = str_getcsv($line, $delim);
                $rows[] = array_combine($header, array_pad($cols, count($header), ''));
            }
        }

        $estadosValidos = ['presente', 'ausente', 'tardanza', 'tarde', 'excusa', 'justificado', 'retiro'];

        // Pre-load all active matriculas for this group, keyed by numero_matricula and cedula
        $matriculasPorNum   = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->get()->keyBy('numero_matricula');
        $matriculasPorCedula = $matriculasPorNum->groupBy(fn ($m) => $m->estudiante->cedula ?? '');

        $importados = 0;
        $omitidos   = 0;
        $errores    = [];

        foreach ($rows as $i => $row) {
            $linea = $i + 2;

            // Resolve matricula
            $numMat  = trim($row['numero_matricula'] ?? '');
            $cedula  = trim($row['cedula'] ?? '');
            $matricula = null;

            if ($numMat && $matriculasPorNum->has($numMat)) {
                $matricula = $matriculasPorNum->get($numMat);
            } elseif ($cedula && $matriculasPorCedula->has($cedula)) {
                $matricula = $matriculasPorCedula->get($cedula)->first();
            }

            if (! $matricula) {
                $errores[] = "Fila {$linea}: Estudiante no encontrado en el grupo (matrícula: '{$numMat}', cédula: '{$cedula}').";
                $omitidos++;
                continue;
            }

            // Resolve fecha
            $fechaStr = trim($row['fecha'] ?? '');
            try {
                $fecha = Carbon::parse($fechaStr)->format('Y-m-d');
            } catch (\Exception) {
                $errores[] = "Fila {$linea}: Fecha inválida '{$fechaStr}'.";
                $omitidos++;
                continue;
            }

            // Resolve estado
            $estado = strtolower(trim($row['estado'] ?? 'presente'));
            if (! in_array($estado, $estadosValidos)) {
                $errores[] = "Fila {$linea}: Estado '{$estado}' no válido. Se usó 'presente'.";
                $estado = 'presente';
            }

            Asistencia::updateOrCreate(
                [
                    'asignacion_id' => $asignacion->id,
                    'matricula_id'  => $matricula->id,
                    'fecha'         => $fecha,
                ],
                [
                    'estado'         => $estado,
                    'registrado_por' => auth()->id(),
                ]
            );

            $importados++;
        }

        $msg = "Se importaron {$importados} registro(s) de asistencia.";
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        return back()
            ->with('success', $msg)
            ->with('errores_import', $errores);
    }

    // ── Verificar y emitir alertas de asistencia crítica ─────────────────
    private function verificarAlertasAsistencia(array $asistencias, Asignacion $asignacion): void
    {
        try {
            $matriculaIds = array_keys($asistencias);

            $todosPorMatricula = Asistencia::where('asignacion_id', $asignacion->id)
                ->whereIn('matricula_id', $matriculaIds)
                ->get()
                ->groupBy('matricula_id');

            foreach ($matriculaIds as $matriculaId) {
                $registros = $todosPorMatricula->get($matriculaId, collect());

                $total    = $registros->count();
                if ($total < 5) continue; // No alertar con pocos registros

                $presentes = $registros->whereIn('estado', ['presente', 'tarde', 'excusa'])->count();
                $pct       = round($presentes / $total * 100, 1);

                // Solo alerta cuando cruza el umbral por primera vez (entre 80% y 74%)
                if ($pct < 75 && $pct >= 70) {
                    $matricula = Matricula::with(['estudiante', 'estudiante.representantes'])->find($matriculaId);
                    if (! $matricula) continue;

                    $est       = $matricula->estudiante;
                    $asignName = $asignacion->asignatura->nombre ?? 'una asignatura';

                    // Alerta en el sistema (para admin/director)
                    \App\Models\AlertaSistema::firstOrCreate(
                        [
                            'tipo'           => 'asistencia_critica',
                            'referencia_tipo' => 'matricula',
                            'referencia_id'   => $matriculaId,
                        ],
                        [
                            'titulo'     => 'Asistencia Crítica: ' . ($est->nombre_completo ?? ''),
                            'mensaje'    => "{$est->nombre_completo} tiene {$pct}% de asistencia en {$asignName}. Umbral mínimo: 75%.",
                            'nivel'      => 'warning',
                            'leida'      => false,
                        ]
                    );

                    // Notificación portal al representante
                    foreach ($est->representantes as $rep) {
                        if ($rep->user_id) {
                            \App\Models\Notificacion::enviarA(
                                [$rep->user_id],
                                'asistencia',
                                'Alerta de Asistencia',
                                "Su representado/a {$est->nombre_completo} tiene {$pct}% de asistencia en {$asignName}. Requerido mínimo: 75%.",
                                ['matricula_id' => $matriculaId]
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error en alerta asistencia: ' . $e->getMessage());
        }
    }
}
