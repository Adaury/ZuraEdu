<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\InstrumentoCriterio;
use App\Models\InstrumentoEvaluacion;
use App\Models\InstrumentoEvaluacionEstudiante;
use App\Models\PlanClase;
use App\Models\PlanClaseMomento;
use App\Models\BoletinConfig;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlanClaseDocenteController extends Controller
{
    private function getDocente()
    {
        $user = Auth::user();
        if (!$user->docente) abort(403, 'No tienes un perfil de docente asociado.');
        return $user->docente;
    }

    // ══ PLANES DE CLASE ══════════════════════════════════════════════════

    public function planesIndex(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $planes = PlanClase::with(['momentos'])
            ->where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere(function ($q2) use ($docente, $asignacion) {
                      $q2->where('docente_id', $docente->id)
                         ->whereNull('asignacion_id')
                         ->where('area', $asignacion->area);
                  });
            })
            ->latest()
            ->get();

        return view('portal.docente.planes_clase.index', compact('docente', 'asignacion', 'planes', 'schoolYear'));
    }

    public function planesCreate(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $estrategias = PlanClase::$estrategiasCatalogo;
        return view('portal.docente.planes_clase.create', compact('docente', 'asignacion', 'estrategias'));
    }

    public function planesStore(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'titulo'       => 'required|string|max:200',
            'tipo_plan'    => 'required|in:diaria,semanal,quincenal,mensual',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date',
            'archivo'      => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $schoolYear  = SchoolYear::actual();
        $archivoPath = $archivoNombre = $archivoTipo = null;

        if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
            $file          = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoTipo   = $file->getMimeType();
            $archivoPath   = $file->store('planes_clase', 'public');
        }

        $plan = PlanClase::create([
            'asignacion_id'        => $asignacion->id,
            'school_year_id'       => $schoolYear->id,
            'docente_id'           => $docente->id,
            'titulo'               => $request->titulo,
            'area'                 => $asignacion->area,
            'tipo_plan'            => $request->tipo_plan,
            'semana'               => $request->semana,
            'fecha_inicio'         => $request->fecha_inicio,
            'fecha_fin'            => $request->fecha_fin,
            'grado_seccion'        => $asignacion->grupo->nombre_completo ?? null,
            'intencion_pedagogica' => $request->intencion_pedagogica,
            'estrategias'          => $request->estrategias ?? [],
            'observacion'          => $request->observacion,
            'archivo_path'         => $archivoPath,
            'archivo_nombre'       => $archivoNombre,
            'archivo_tipo'         => $archivoTipo,
            'publicado'            => true,
            'creado_por'           => Auth::id(),
        ]);

        $this->guardarMomentos($plan, $request->input('momentos', []));

        return redirect()->route('portal.docente.planes-clase.show', [$asignacion, $plan])
            ->with('success', 'Plan de clase creado correctamente.');
    }

    public function planesShow(Asignacion $asignacion, PlanClase $planClase)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        $planClase->load('momentos');
        return view('portal.docente.planes_clase.show', compact('docente', 'asignacion', 'planClase'));
    }

    public function planesPdf(Asignacion $asignacion, PlanClase $planClase)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $planClase->load(['momentos' => fn($q) => $q->orderBy('orden')]);
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = SchoolYear::actual();
        $config = $sy ? BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.planes_clase.plan_pdf',
            compact('planClase', 'asignacion', 'docente', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($planClase->titulo ?? 'plan-clase');
        return $pdf->download("plan_{$slug}.pdf");
    }

    public function planesToggle(Asignacion $asignacion, PlanClase $planClase)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        $planClase->update(['publicado' => !$planClase->publicado]);
        return back()->with('success', $planClase->publicado ? 'Plan publicado.' : 'Plan guardado como borrador.');
    }

    public function planesDestroy(Asignacion $asignacion, PlanClase $planClase)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($planClase->archivo_path) Storage::disk('public')->delete($planClase->archivo_path);
        $planClase->delete();
        return redirect()->route('portal.docente.planes-clase.index', $asignacion)
            ->with('success', 'Plan eliminado.');
    }

    public function planesDownload(Asignacion $asignacion, PlanClase $planClase)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if (!$planClase->archivo_path || !Storage::disk('public')->exists($planClase->archivo_path)) {
            return back()->with('error', 'Archivo no encontrado.');
        }
        return Storage::disk('public')->download($planClase->archivo_path, $planClase->archivo_nombre);
    }

    public function planesListaPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $planes = PlanClase::with(['momentos'])
            ->where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere(function ($q2) use ($docente, $asignacion) {
                      $q2->where('docente_id', $docente->id)
                         ->whereNull('asignacion_id')
                         ->where('area', $asignacion->area);
                  });
            })->latest()->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.planes_clase.lista_pdf', compact(
            'docente', 'asignacion', 'planes', 'schoolYear', 'inst'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'planes');
        return $pdf->download("planes_clase_{$slug}.pdf");
    }

    // ── Planes clase lista Excel ─────────────────────────────────────────
    public function planesListaExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $planes = PlanClase::where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere(function ($q2) use ($docente, $asignacion) {
                      $q2->where('docente_id', $docente->id)
                         ->whereNull('asignacion_id')
                         ->where('area', $asignacion->area);
                  });
            })->latest()->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planes de Clase');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Planes de Clase — ' . ($asignacion->asignatura?->nombre ?? '') . ' · ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Tipo', 'Semana', 'Fecha Inicio', 'Fecha Fin', 'Publicado'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($planes as $idx => $plan) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $plan->titulo);
            $sheet->setCellValue('C' . $row, ucfirst($plan->tipo_plan ?? '—'));
            $sheet->setCellValue('D' . $row, $plan->semana ?? '—');
            $sheet->setCellValue('E' . $row, $plan->fecha_inicio?->format('d/m/Y') ?? '—');
            $sheet->setCellValue('F' . $row, $plan->fecha_fin?->format('d/m/Y') ?? '—');
            $sheet->setCellValue('G' . $row, $plan->publicado ? 'Sí' : 'No');
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            if (! $plan->publicado) {
                $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fef9c3');
            }
        }

        foreach (['A'=>5,'B'=>35,'C'=>14,'D'=>10,'E'=>14,'F'=>14,'G'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'planes');
        $filename = "planes_clase_{$slug}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ══ INSTRUMENTOS DE EVALUACIÓN ════════════════════════════════════════

    public function instrumentosListaPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear   = SchoolYear::actual();
        $instrumentos = InstrumentoEvaluacion::with(['criterios'])
            ->where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere('docente_id', $docente->id);
            })
            ->latest()
            ->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.instrumentos.lista_pdf', compact(
            'docente', 'asignacion', 'instrumentos', 'schoolYear', 'inst'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'instrumentos');
        return $pdf->download("instrumentos_{$slug}.pdf");
    }

    // ── Instrumentos lista Excel ─────────────────────────────────────────
    public function instrumentosListaExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear   = SchoolYear::actual();
        $instrumentos = InstrumentoEvaluacion::with(['criterios'])
            ->where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere('docente_id', $docente->id);
            })
            ->latest()
            ->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Instrumentos');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Instrumentos — ' . ($asignacion->asignatura?->nombre ?? '') . ' · ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Tipo', 'Criterios', 'Puntaje Total', 'Fecha'];
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
            $puntajeTotal = $instr->criterios->sum('puntaje_maximo') ?? 0;
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $instr->titulo);
            $sheet->setCellValue('C' . $row, $tipo);
            $sheet->setCellValue('D' . $row, $instr->criterios->count());
            $sheet->setCellValue('E' . $row, $puntajeTotal ?: '—');
            $sheet->setCellValue('F' . $row, $instr->created_at?->format('d/m/Y'));
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (['A'=>5,'B'=>35,'C'=>22,'D'=>10,'E'=>14,'F'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'instrumentos');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "instrumentos_{$slug}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function instrumentosIndex(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear   = SchoolYear::actual();
        $instrumentos = InstrumentoEvaluacion::with(['criterios'])
            ->where('school_year_id', $schoolYear?->id)
            ->where(function ($q) use ($asignacion, $docente) {
                $q->where('asignacion_id', $asignacion->id)
                  ->orWhere('docente_id', $docente->id);
            })
            ->latest()
            ->get();

        return view('portal.docente.instrumentos.index', compact('docente', 'asignacion', 'instrumentos', 'schoolYear'));
    }

    public function instrumentosCreate(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $tipos   = InstrumentoEvaluacion::$tiposLabels;
        $niveles = InstrumentoEvaluacion::$nivelesDefault;
        $matriculas = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->orderBy('numero_orden')->get();

        return view('portal.docente.instrumentos.create', compact('docente', 'asignacion', 'tipos', 'niveles', 'matriculas'));
    }

    public function instrumentosStore(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'titulo'               => 'required|string|max:200',
            'tipo'                 => 'required|in:lista_cotejo,rubrica,escala_estimacion',
            'criterios'            => 'required|array|min:1',
            'criterios.*.nombre'   => 'required|string|max:200',
        ]);

        $schoolYear = SchoolYear::actual();

        DB::transaction(function () use ($request, $asignacion, $docente, $schoolYear) {
            $instrumento = InstrumentoEvaluacion::create([
                'asignacion_id'    => $asignacion->id,
                'school_year_id'   => $schoolYear->id,
                'docente_id'       => $docente->id,
                'titulo'           => $request->titulo,
                'tipo'             => $request->tipo,
                'competencia'      => $request->competencia,
                'descripcion'      => $request->descripcion,
                'indicadores_logro'=> $request->indicadores_logro,
                'observaciones'    => $request->observaciones,
                'publicado'        => false,
                'creado_por'       => Auth::id(),
                'niveles_desempeno'=> $request->tipo === 'rubrica' ? InstrumentoEvaluacion::$nivelesDefault : null,
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

            // Save initial evaluaciones if puntajes provided
            if ($request->filled('evaluaciones')) {
                foreach ($request->evaluaciones as $matId => $datos) {
                    InstrumentoEvaluacionEstudiante::create([
                        'instrumento_id' => $instrumento->id,
                        'matricula_id'   => $matId,
                        'puntajes'       => $datos['puntajes'] ?? [],
                        'ponderacion'    => $datos['ponderacion'] ?? null,
                        'nivel_desempeno'=> $datos['nivel_desempeno'] ?? null,
                    ]);
                }
            }

            return $instrumento;
        });

        return redirect()->route('portal.docente.instrumentos.index', $asignacion)
            ->with('success', 'Instrumento de evaluación creado correctamente.');
    }

    public function instrumentosShow(Asignacion $asignacion, InstrumentoEvaluacion $instrumento)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $instrumento->load(['criterios']);
        $matriculas = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->orderBy('numero_orden')->get();
        $evaluaciones = $instrumento->evaluaciones()->get()->keyBy('matricula_id');

        return view('portal.docente.instrumentos.show', compact('docente', 'asignacion', 'instrumento', 'matriculas', 'evaluaciones'));
    }

    public function instrumentosGuardar(Request $request, Asignacion $asignacion, InstrumentoEvaluacion $instrumento)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        DB::transaction(function () use ($request, $instrumento) {
            foreach ($request->input('evaluaciones', []) as $matId => $datos) {
                InstrumentoEvaluacionEstudiante::updateOrCreate(
                    ['instrumento_id' => $instrumento->id, 'matricula_id' => $matId],
                    [
                        'puntajes'        => $datos['puntajes'] ?? [],
                        'ponderacion'     => isset($datos['ponderacion']) && $datos['ponderacion'] !== ''
                            ? (float) $datos['ponderacion'] : null,
                        'nivel_desempeno' => $datos['nivel_desempeno'] ?? null,
                        'observacion'     => $datos['observacion'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('portal.docente.instrumentos.show', [$asignacion, $instrumento])
            ->with('success', 'Evaluaciones guardadas correctamente.');
    }

    // ── Instrumento de Evaluación PDF ────────────────────────────────────
    public function instrumentosPdf(Asignacion $asignacion, InstrumentoEvaluacion $instrumento)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $instrumento->load(['criterios']);
        $matriculas   = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->orderBy('numero_orden')->get();
        $evaluaciones = $instrumento->evaluaciones()->get()->keyBy('matricula_id');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.instrumentos.instrumento_pdf',
            compact('instrumento', 'asignacion', 'docente', 'matriculas', 'evaluaciones', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($instrumento->titulo ?? 'instrumento');
        return $pdf->download("instrumento_{$slug}.pdf");
    }

    // ── Private helpers ──────────────────────────────────────────────────
    private function guardarMomentos(PlanClase $plan, array $momentos): void
    {
        $orden = 0;
        foreach (['inicio', 'desarrollo', 'cierre'] as $tipo) {
            $data = $momentos[$tipo] ?? null;
            if (!$data) continue;
            PlanClaseMomento::create([
                'plan_clase_id'            => $plan->id,
                'tipo'                     => $tipo,
                'orden'                    => $orden++,
                'duracion_minutos'         => $data['duracion_minutos'] ?? PlanClaseMomento::$tipoDuraciones[$tipo],
                'area_curricular'          => $data['area_curricular'] ?? null,
                'competencias_especificas' => $data['competencias_especificas'] ?? null,
                'contenidos'               => $data['contenidos'] ?? null,
                'actividades'              => $data['actividades'] ?? null,
                'indicador_logro'          => $data['indicador_logro'] ?? null,
                'recursos'                 => $data['recursos'] ?? null,
            ]);
        }
    }
}
