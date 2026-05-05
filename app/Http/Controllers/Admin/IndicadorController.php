<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Asignacion;
use App\Models\Grado;
use App\Models\IndicadorAprendizaje;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IndicadorController extends Controller
{
    public function index(Request $request)
    {
        $asignaturas = Asignatura::orderBy('nombre')->get();
        $grados      = Grado::orderBy('nombre')->get();

        $query = IndicadorAprendizaje::with(['asignatura', 'grado'])
            ->orderBy('grado_id')
            ->orderBy('asignatura_id')
            ->orderBy('periodo_numero')
            ->orderBy('orden');

        if ($request->filled('asignatura_id')) {
            $query->where('asignatura_id', $request->asignatura_id);
        }
        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->grado_id);
        }
        if ($request->filled('periodo')) {
            $query->where('periodo_numero', $request->periodo);
        }

        $indicadores = $query->get();

        return view('admin.indicadores.index', compact('indicadores', 'asignaturas', 'grados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asignatura_id'  => 'required|exists:asignaturas,id',
            'grado_id'       => 'required|exists:grados,id',
            'descripcion'    => 'required|string|max:500',
            'periodo_numero' => 'required|integer|min:1|max:4',
            'orden'          => 'nullable|integer|min:1|max:99',
        ]);

        $data['activo'] = true;
        $data['orden']  = $data['orden'] ?? 1;

        IndicadorAprendizaje::create($data);

        return back()->with('success', 'Indicador creado correctamente.');
    }

    public function update(Request $request, IndicadorAprendizaje $indicador)
    {
        $data = $request->validate([
            'asignatura_id'  => 'required|exists:asignaturas,id',
            'grado_id'       => 'required|exists:grados,id',
            'descripcion'    => 'required|string|max:500',
            'periodo_numero' => 'required|integer|min:1|max:4',
            'orden'          => 'nullable|integer|min:1|max:99',
            'activo'         => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');
        $indicador->update($data);

        return back()->with('success', 'Indicador actualizado.');
    }

    public function destroy(IndicadorAprendizaje $indicador)
    {
        $indicador->delete();
        return back()->with('success', 'Indicador eliminado.');
    }

    public function evaluaciones(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $periodoId    = $request->periodo_id;

        $schoolYear  = SchoolYear::actual();
        $asignacion  = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])->findOrFail($asignacionId);

        // Protección: si es Docente, verificar que la asignación le pertenece
        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            $docente = \App\Models\Docente::where('user_id', $user->id)->first();
            if (!$docente || $asignacion->docente_id !== $docente->id) abort(403);
        }

        $periodo  = \App\Models\Periodo::findOrFail($periodoId);
        $periodos = \App\Models\Periodo::where('school_year_id', $schoolYear->id)->orderBy('numero')->get();

        // Indicadores para esta asignatura+grado+periodo
        $indicadores = IndicadorAprendizaje::where('asignatura_id', $asignacion->asignatura_id)
            ->where('grado_id', $asignacion->grupo->grado_id)
            ->where('periodo_numero', $periodo->numero)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        // Matriculas activas del grupo
        $matriculas = $asignacion->grupo->matriculas()
            ->where('activo', true)
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        // Evaluaciones existentes: indexed by [matricula_id][indicador_id]
        $evaluaciones = \App\Models\EvaluacionIndicador::where('periodo_id', $periodoId)
            ->whereIn('indicador_id', $indicadores->pluck('id'))
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()
            ->groupBy('matricula_id')
            ->map(fn($g) => $g->keyBy('indicador_id'));

        return view('admin.indicadores.evaluaciones', compact(
            'asignacion', 'periodo', 'periodos', 'indicadores', 'matriculas', 'evaluaciones', 'schoolYear'
        ));
    }

    public function guardarEvaluacion(Request $request)
    {
        $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'indicador_id' => 'required|exists:indicadores_aprendizaje,id',
            'periodo_id'   => 'required|exists:periodos,id',
            'nivel'        => 'required|in:Excelente,Bueno,En proceso,Insuficiente',
        ]);

        $eval = \App\Models\EvaluacionIndicador::updateOrCreate(
            [
                'matricula_id' => $request->matricula_id,
                'indicador_id' => $request->indicador_id,
                'periodo_id'   => $request->periodo_id,
            ],
            ['nivel' => $request->nivel]
        );

        return response()->json(['ok' => true, 'id' => $eval->id]);
    }

    // ── Excel lista de indicadores ───────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $query = IndicadorAprendizaje::with(['asignatura', 'grado'])
            ->orderBy('grado_id')->orderBy('asignatura_id')
            ->orderBy('periodo_numero')->orderBy('orden');

        if ($request->filled('asignatura_id')) $query->where('asignatura_id', $request->asignatura_id);
        if ($request->filled('grado_id'))      $query->where('grado_id', $request->grado_id);
        if ($request->filled('periodo'))       $query->where('periodo_numero', $request->periodo);

        $indicadores = $query->get();

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Indicadores');

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Indicadores de Aprendizaje');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Asignatura', 'Grado', 'Período', 'Descripción', 'Orden'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($indicadores as $i => $ind) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $ind->asignatura?->nombre ?? '—');
            $ws->setCellValue("C{$row}", $ind->grado?->nombre ?? '—');
            $ws->setCellValue("D{$row}", 'P' . ($ind->periodo_numero ?? '—'));
            $ws->setCellValue("E{$row}", $ind->descripcion ?? '—');
            $ws->setCellValue("F{$row}", $ind->orden ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f6ff');
            }
        }

        $ws->getColumnDimension('E')->setWidth(50);
        foreach (['A', 'B', 'C', 'D', 'F'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($ss);
        $filename = 'indicadores_' . now()->format('Ymd') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── PDF lista de indicadores ─────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = IndicadorAprendizaje::with(['asignatura', 'grado'])
            ->orderBy('grado_id')->orderBy('asignatura_id')
            ->orderBy('periodo_numero')->orderBy('orden');

        if ($request->filled('asignatura_id')) $query->where('asignatura_id', $request->asignatura_id);
        if ($request->filled('grado_id'))      $query->where('grado_id', $request->grado_id);
        if ($request->filled('periodo'))       $query->where('periodo_numero', $request->periodo);

        $indicadores = $query->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.indicadores.lista_pdf',
            compact('indicadores', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('indicadores_' . now()->format('Ymd') . '.pdf');
    }

    // ── PDF de evaluaciones de indicadores ───────────────────────────────
    public function evaluacionesPdf(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $periodoId    = $request->periodo_id;

        $schoolYear  = SchoolYear::actual();
        $asignacion  = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])->findOrFail($asignacionId);

        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            $docente = \App\Models\Docente::where('user_id', $user->id)->first();
            if (!$docente || $asignacion->docente_id !== $docente->id) abort(403);
        }

        $periodo = \App\Models\Periodo::findOrFail($periodoId);

        $indicadores = IndicadorAprendizaje::where('asignatura_id', $asignacion->asignatura_id)
            ->where('grado_id', $asignacion->grupo->grado_id)
            ->where('periodo_numero', $periodo->numero)
            ->where('activo', true)->orderBy('orden')->get();

        $matriculas = $asignacion->grupo->matriculas()
            ->where('activo', true)->with('estudiante')->orderBy('numero_orden')->get();

        $evaluaciones = \App\Models\EvaluacionIndicador::where('periodo_id', $periodoId)
            ->whereIn('indicador_id', $indicadores->pluck('id'))
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()->groupBy('matricula_id')->map(fn($g) => $g->keyBy('indicador_id'));

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.indicadores.evaluaciones_pdf',
            compact('asignacion', 'periodo', 'indicadores', 'matriculas', 'evaluaciones', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug(($asignacion->asignatura?->nombre ?? '') . '-' . $periodo->nombre);
        return $pdf->download("evaluaciones_{$slug}.pdf");
    }

    // ── Excel de evaluaciones de indicadores ─────────────────────────────
    public function evaluacionesExcel(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $periodoId    = $request->periodo_id;

        $schoolYear  = SchoolYear::actual();
        $asignacion  = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])->findOrFail($asignacionId);
        $periodo     = \App\Models\Periodo::findOrFail($periodoId);

        $indicadores = IndicadorAprendizaje::where('asignatura_id', $asignacion->asignatura_id)
            ->where('grado_id', $asignacion->grupo->grado_id)
            ->where('periodo_numero', $periodo->numero)
            ->where('activo', true)->orderBy('orden')->get();

        $matriculas = $asignacion->grupo->matriculas()
            ->where('activo', true)->with('estudiante')->orderBy('numero_orden')->get();

        $evaluaciones = \App\Models\EvaluacionIndicador::where('periodo_id', $periodoId)
            ->whereIn('indicador_id', $indicadores->pluck('id'))
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()->groupBy('matricula_id')->map(fn($g) => $g->keyBy('indicador_id'));

        $nivelColores = [
            'Excelente'  => ['fill' => 'd1fae5', 'font' => '065f46'],
            'Bueno'      => ['fill' => 'dcfce7', 'font' => '166534'],
            'En proceso' => ['fill' => 'fef9c3', 'font' => '854d0e'],
            'Insuficiente' => ['fill' => 'fee2e2', 'font' => '991b1b'],
        ];

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Indicadores');

        $totalCols = 2 + $indicadores->count();
        $lastCol   = chr(64 + min($totalCols, 26));

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $title = 'EVALUACIÓN DE INDICADORES — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . $periodo->nombre . ' — ' . $asignacion->grupo?->nombre_completo;
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Estudiante');
        $col = 3;
        foreach ($indicadores as $ind) {
            $sheet->setCellValue(chr(64 + $col) . '2', \Str::limit($ind->descripcion ?? 'IL' . $ind->id, 20));
            $col++;
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(40);

        foreach ($matriculas as $i => $m) {
            $r = $i + 3;
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", trim(($m->estudiante?->apellidos ?? '') . ', ' . ($m->estudiante?->nombres ?? '')));

            $col = 3;
            foreach ($indicadores as $ind) {
                $eval  = $evaluaciones->get($m->id)?->get($ind->id);
                $nivel = $eval?->nivel ?? '';
                $cell  = chr(64 + $col) . $r;
                $sheet->setCellValue($cell, $nivel);
                if ($nivel && isset($nivelColores[$nivel])) {
                    $c = $nivelColores[$nivel];
                    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($c['fill']);
                    $sheet->getStyle($cell)->getFont()->getColor()->setRGB($c['font']);
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $col++;
            }

            if ($i % 2 === 1 && $sheet->getStyle("A{$r}")->getFill()->getFillType() === Fill::FILL_NONE) {
                $sheet->getStyle("A{$r}:B{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
        }

        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->freezePane('C3');

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'ind_') . '.xlsx';
        $writer->save($tmp);

        $xslug  = \Str::slug(($asignacion->asignatura?->nombre ?? '') . '-' . $periodo->nombre);
        $nombre = "Indicadores_{$xslug}.xlsx";
        return response()->download($tmp, $nombre)->deleteFileAfterSend();
    }
}
