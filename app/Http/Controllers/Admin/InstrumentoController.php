<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\InstrumentoCriterio;
use App\Models\InstrumentoEvaluacion;
use App\Models\InstrumentoEvaluacionEstudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InstrumentoController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = InstrumentoEvaluacion::with(['docente', 'asignacion.asignatura', 'asignacion.grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        if ($user->hasRole('Docente') && $user->docente) {
            $query->where('docente_id', $user->docente->id);
        }

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('search')) $query->where('titulo', 'like', '%'.$request->search.'%');

        $instrumentos = $query->paginate(20)->withQueryString();

        return view('admin.instrumentos.index', compact('instrumentos', 'schoolYear'));
    }

    public function create()
    {
        $schoolYear   = SchoolYear::actual();
        $asignaciones = Asignacion::with(['asignatura','grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->where('activo', true)
            ->when(Auth::user()->hasRole('Docente') && Auth::user()->docente, function ($q) {
                $q->where('docente_id', Auth::user()->docente->id);
            })
            ->get();

        $tipos    = InstrumentoEvaluacion::$tiposLabels;
        $niveles  = InstrumentoEvaluacion::$nivelesDefault;

        return view('admin.instrumentos.create', compact('schoolYear', 'asignaciones', 'tipos', 'niveles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'           => 'required|string|max:200',
            'tipo'             => 'required|in:lista_cotejo,rubrica,escala_estimacion',
            'criterios'        => 'required|array|min:1',
            'criterios.*.nombre' => 'required|string|max:200',
        ]);

        $schoolYear = SchoolYear::actual();
        $docente    = Auth::user()->docente ?? null;

        DB::transaction(function () use ($request, $schoolYear, $docente) {
            $instrumento = InstrumentoEvaluacion::create([
                'asignacion_id'   => $request->asignacion_id ?: null,
                'school_year_id'  => $schoolYear->id,
                'docente_id'      => $docente?->id,
                'titulo'          => $request->titulo,
                'tipo'            => $request->tipo,
                'competencia'     => $request->competencia,
                'descripcion'     => $request->descripcion,
                'indicadores_logro' => $request->indicadores_logro,
                'observaciones'   => $request->observaciones,
                'publicado'       => $request->boolean('publicado'),
                'creado_por'      => Auth::id(),
                'niveles_desempeno' => $request->tipo === 'rubrica'
                    ? InstrumentoEvaluacion::$nivelesDefault : null,
            ]);

            foreach ($request->criterios as $i => $crit) {
                if (empty($crit['nombre'])) continue;
                InstrumentoCriterio::create([
                    'instrumento_id' => $instrumento->id,
                    'nombre'         => $crit['nombre'],
                    'descripcion'    => $crit['descripcion'] ?? null,
                    'orden'          => $i,
                    'peso_max'       => $crit['peso_max'] ?? 1,
                ]);
            }

            return $instrumento;
        });

        return redirect()->route('admin.instrumentos.index')
            ->with('success', 'Instrumento de evaluación creado correctamente.');
    }

    public function show(InstrumentoEvaluacion $instrumento)
    {
        $instrumento->load(['docente', 'asignacion.asignatura', 'asignacion.grupo', 'criterios', 'creadoPor']);

        // Load student list and their evaluations
        $matriculas = collect();
        $evaluaciones = collect();
        if ($instrumento->asignacion) {
            $matriculas   = $instrumento->asignacion->grupo->matriculas()
                ->activas()->with('estudiante')->orderBy('numero_orden')->get();
            $evaluaciones = $instrumento->evaluaciones()->with('matricula.estudiante')->get()
                ->keyBy('matricula_id');
        }

        return view('admin.instrumentos.show', compact('instrumento', 'matriculas', 'evaluaciones'));
    }

    public function registrar(Request $request, InstrumentoEvaluacion $instrumento)
    {
        $request->validate([
            'evaluaciones'   => 'required|array',
        ]);

        DB::transaction(function () use ($request, $instrumento) {
            foreach ($request->evaluaciones as $matriculaId => $datos) {
                InstrumentoEvaluacionEstudiante::updateOrCreate(
                    ['instrumento_id' => $instrumento->id, 'matricula_id' => $matriculaId],
                    [
                        'puntajes'         => $datos['puntajes'] ?? [],
                        'ponderacion'      => isset($datos['ponderacion']) && $datos['ponderacion'] !== ''
                            ? (float) $datos['ponderacion'] : null,
                        'nivel_desempeno'  => $datos['nivel_desempeno'] ?? null,
                        'observacion'      => $datos['observacion'] ?? null,
                    ]
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Evaluaciones guardadas.']);
    }

    public function destroy(InstrumentoEvaluacion $instrumento)
    {
        $instrumento->delete();
        return redirect()->route('admin.instrumentos.index')
            ->with('success', 'Instrumento eliminado.');
    }

    // ── Lista Excel ──────────────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = InstrumentoEvaluacion::with(['docente', 'asignacion.asignatura', 'asignacion.grupo.grado', 'asignacion.grupo.seccion'])
            ->withCount('criterios')
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        if ($user->hasRole('Docente') && $user->docente) {
            $query->where('docente_id', $user->docente->id);
        }
        if ($request->filled('tipo')) $query->where('tipo', $request->tipo);

        $instrumentos = $query->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Instrumentos');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Instrumentos de Evaluación — ' . ($schoolYear?->nombre ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Docente', 'Asignatura', 'Grupo', 'Tipo', 'Criterios', 'Fecha'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($instrumentos as $idx => $instr) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $tipo = match($instr->tipo ?? '') {
                'lc'  => 'Lista de Cotejo',
                'rb'  => 'Rúbrica',
                'es'  => 'Escala de Estimación',
                default => strtoupper($instr->tipo ?? '—'),
            };
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $instr->docente?->nombre_completo);
            $sheet->setCellValue('C' . $row, $instr->asignacion?->asignatura?->nombre);
            $sheet->setCellValue('D' . $row, $instr->asignacion?->grupo?->nombre_completo);
            $sheet->setCellValue('E' . $row, $tipo);
            $sheet->setCellValue('F' . $row, $instr->criterios_count);
            $sheet->setCellValue('G' . $row, $instr->created_at?->format('d/m/Y'));
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (['A'=>5,'B'=>28,'C'=>25,'D'=>18,'E'=>22,'F'=>10,'G'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'instrumentos_' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Lista PDF ────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = InstrumentoEvaluacion::with(['docente', 'asignacion.asignatura', 'asignacion.grupo.grado', 'asignacion.grupo.seccion'])
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        if ($user->hasRole('Docente') && $user->docente) {
            $query->where('docente_id', $user->docente->id);
        }
        if ($request->filled('tipo')) $query->where('tipo', $request->tipo);

        $instrumentos = $query->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.instrumentos.lista_pdf',
            compact('instrumentos', 'inst', 'schoolYear')
        )->setPaper('letter', 'portrait');

        return $pdf->download('instrumentos_' . now()->format('Ymd') . '.pdf');
    }
}
