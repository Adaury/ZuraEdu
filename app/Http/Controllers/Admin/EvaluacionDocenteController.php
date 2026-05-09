<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\EvaluacionDocente;
use App\Models\Notificacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionDocenteController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $docentes = Docente::activos()->orderBy('apellidos')->get();

        $query = EvaluacionDocente::with(['docente', 'evaluador'])
            ->orderByDesc('created_at');

        if ($request->filled('docente_id')) {
            $query->where('docente_id', $request->docente_id);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo_evaluado', 'like', '%' . $request->periodo . '%');
        }

        $evaluaciones = $query->paginate(15)->withQueryString();

        return view('admin.evaluaciones_docentes.index', compact('evaluaciones', 'docentes'));
    }

    // ── Dashboard ──────────────────────────────────────────────────────────
    public function dashboard()
    {
        $total = EvaluacionDocente::count();

        $promedioInstitucional = $total > 0
            ? round(EvaluacionDocente::avg('promedio'), 2)
            : null;

        // % por nivel
        $niveles = [
            'Excelente'  => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 4.5)->count() / $total * 100, 1) : 0,
            'Bueno'      => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 3.5)->where('promedio', '<', 4.5)->count() / $total * 100, 1) : 0,
            'Regular'    => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 2.5)->where('promedio', '<', 3.5)->count() / $total * 100, 1) : 0,
            'Deficiente' => $total > 0 ? round(EvaluacionDocente::where('promedio', '<', 2.5)->count() / $total * 100, 1) : 0,
        ];

        // Ranking de docentes (promedio de todas sus evaluaciones)
        $ranking = Docente::withCount('evaluaciones as total_evaluaciones')
            ->with('evaluaciones')
            ->having('total_evaluaciones', '>', 0)
            ->get()
            ->map(function ($docente) {
                $docente->promedio_general = round($docente->evaluaciones->avg('promedio'), 2);
                return $docente;
            })
            ->sortByDesc('promedio_general')
            ->values();

        return view('admin.evaluaciones_docentes.dashboard', compact(
            'total', 'promedioInstitucional', 'niveles', 'ranking'
        ));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $docentes = Docente::activos()->orderBy('apellidos')->get();
        $docentePresel = $request->filled('docente_id')
            ? Docente::find($request->docente_id)
            : null;

        return view('admin.evaluaciones_docentes.create', compact('docentes', 'docentePresel'));
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'docente_id'          => 'required|exists:docentes,id',
            'periodo_evaluado'    => 'required|string|max:100',
            'puntualidad'         => 'required|integer|min:1|max:5',
            'dominio_contenido'   => 'required|integer|min:1|max:5',
            'metodologia'         => 'required|integer|min:1|max:5',
            'relacion_estudiantes'=> 'required|integer|min:1|max:5',
            'planificacion'       => 'required|integer|min:1|max:5',
            'observaciones'       => 'nullable|string|max:2000',
        ]);

        $data['evaluador_id'] = Auth::id();

        $evaluacion = EvaluacionDocente::create($data);

        try {
            $evaluacion->load('docente');
            $docente = $evaluacion->docente;
            if ($docente?->user_id) {
                $nivel = $evaluacion->nivelDesempeno()['label'] ?? 'registrado';
                Notificacion::enviar(
                    $docente->user_id,
                    'academica',
                    '📋 Evaluación de desempeño registrada',
                    "Se ha registrado tu evaluación de desempeño del período «{$evaluacion->periodo_evaluado}». Nivel: {$nivel}."
                );
            }
        } catch (\Throwable) {}

        return redirect()
            ->route('admin.evaluaciones-docentes.show', $evaluacion)
            ->with('success', 'Evaluación registrada correctamente.');
    }

    // ── Show ───────────────────────────────────────────────────────────────
    public function show(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->load(['docente', 'evaluador']);

        return view('admin.evaluaciones_docentes.show', [
            'evaluacion' => $evaluacionDocente,
        ]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────
    public function destroy(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->delete();

        return redirect()
            ->route('admin.evaluaciones-docentes.index')
            ->with('success', 'Evaluación eliminada correctamente.');
    }

    // ── Excel ──────────────────────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $query = EvaluacionDocente::with(['docente', 'evaluador'])->orderByDesc('created_at');

        if ($request->filled('docente_id')) $query->where('docente_id', $request->docente_id);
        if ($request->filled('periodo'))    $query->where('periodo_evaluado', 'like', '%' . $request->periodo . '%');

        $evaluaciones = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Evaluaciones Docentes');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1e3a8a']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Evaluaciones de Desempeño Docente — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Docente', 'Período', 'Promedio', 'Nivel', 'Evaluador', 'Fecha'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($evaluaciones->values() as $i => $ev) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $ev->docente?->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", $ev->periodo_evaluado);
            $ws->setCellValue("D{$row}", number_format($ev->promedio, 2));
            $ws->setCellValue("E{$row}", $ev->nivel_desempeno ?? '—');
            $ws->setCellValue("F{$row}", $ev->evaluador?->name ?? '—');
            $ws->setCellValue("G{$row}", $ev->created_at?->format('d/m/Y') ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'evaluaciones_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'evaluaciones_docentes_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ──────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = EvaluacionDocente::with(['docente', 'evaluador'])->orderByDesc('created_at');

        if ($request->filled('docente_id')) $query->where('docente_id', $request->docente_id);
        if ($request->filled('periodo'))    $query->where('periodo_evaluado', 'like', '%' . $request->periodo . '%');

        $evaluaciones = $query->get();
        $inst         = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView(
            'admin.evaluaciones_docentes.lista_pdf',
            compact('evaluaciones', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('evaluaciones_docentes_' . now()->format('Ymd') . '.pdf');
    }

    // ── PDF ────────────────────────────────────────────────────────────────
    public function pdf(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->load(['docente', 'evaluador']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView(
            'admin.evaluaciones_docentes.pdf',
            ['evaluacion' => $evaluacionDocente, 'inst' => $inst]
        )->setPaper('letter', 'portrait');

        $nombre = 'evaluacion_docente_' .
            str_replace([' ', ','], ['_', ''], strtolower($evaluacionDocente->docente->apellidos ?? 'docente')) .
            '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
