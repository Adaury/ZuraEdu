<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\PlanClase;
use App\Models\PlanClaseMomento;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlanClaseController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = PlanClase::with(['docente', 'asignacion.asignatura', 'asignacion.grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        // Role filtering
        if ($user->hasRole('Docente')) {
            $docente = $user->docente;
            if ($docente) $query->where('docente_id', $docente->id);
        }

        if ($request->filled('area'))    $query->where('area', $request->area);
        if ($request->filled('docente')) $query->where('docente_id', $request->docente);
        if ($request->filled('search'))  $query->where('titulo', 'like', '%'.$request->search.'%');

        $planes    = $query->paginate(20)->withQueryString();
        $docentes  = Docente::where('estado', 'activo')->orderBy('apellidos')->get();

        return view('admin.planes_clase.index', compact('planes', 'docentes', 'schoolYear'));
    }

    public function create(Request $request)
    {
        $schoolYear  = SchoolYear::actual();
        $user        = Auth::user();

        // Get academic asignaciones
        $asignaciones = Asignacion::with(['asignatura', 'grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->where('activo', true)
            ->when($user->hasRole('Docente') && $user->docente, function ($q) use ($user) {
                $q->where('docente_id', $user->docente->id);
            })
            ->get();

        $estrategias = PlanClase::$estrategiasCatalogo;

        return view('admin.planes_clase.create', compact('schoolYear', 'asignaciones', 'estrategias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'            => 'required|string|max:200',
            'area'              => 'required|in:academica,tecnica',
            'tipo_plan'         => 'required|in:diaria,semanal,quincenal,mensual',
            'fecha_inicio'      => 'nullable|date',
            'fecha_fin'         => 'nullable|date|after_or_equal:fecha_inicio',
            'archivo'           => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png|max:10240',
            'momentos'          => 'nullable|array',
        ]);

        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();
        $docente    = $user->docente ?? Docente::find($request->docente_id);

        // Handle file upload
        $archivoPath = $archivoNombre = $archivoTipo = null;
        if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
            $file         = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoTipo   = $file->getMimeType();
            $archivoPath   = $file->store('planes_clase', 'public');
        }

        $plan = PlanClase::create([
            'asignacion_id'       => $request->asignacion_id ?: null,
            'school_year_id'      => $schoolYear->id,
            'docente_id'          => $docente?->id,
            'titulo'              => $request->titulo,
            'area'                => $request->area,
            'tipo_plan'           => $request->tipo_plan,
            'semana'              => $request->semana,
            'fecha_inicio'        => $request->fecha_inicio,
            'fecha_fin'           => $request->fecha_fin,
            'grado_seccion'       => $request->grado_seccion,
            'intencion_pedagogica'=> $request->intencion_pedagogica,
            'estrategias'         => $request->estrategias ?? [],
            'observacion'         => $request->observacion,
            'archivo_path'        => $archivoPath,
            'archivo_nombre'      => $archivoNombre,
            'archivo_tipo'        => $archivoTipo,
            'publicado'           => $request->boolean('publicado'),
            'creado_por'          => $user->id,
        ]);

        // Save momentos
        $this->guardarMomentos($plan, $request->input('momentos', []));

        return redirect()->route('admin.planes-clase.show', $plan)
            ->with('success', 'Plan de clase "' . $plan->titulo . '" creado correctamente.');
    }

    public function show(PlanClase $planesClase)
    {
        $planesClase->load(['docente', 'asignacion.asignatura', 'asignacion.grupo', 'momentos', 'creadoPor']);
        return view('admin.planes_clase.show', ['plan' => $planesClase]);
    }

    public function edit(PlanClase $planesClase)
    {
        $schoolYear   = SchoolYear::actual();
        $asignaciones = Asignacion::with(['asignatura','grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->where('activo', true)
            ->get();
        $estrategias  = PlanClase::$estrategiasCatalogo;
        $planesClase->load('momentos');
        return view('admin.planes_clase.edit', [
            'plan'        => $planesClase,
            'asignaciones'=> $asignaciones,
            'estrategias' => $estrategias,
            'schoolYear'  => $schoolYear,
        ]);
    }

    public function update(Request $request, PlanClase $planesClase)
    {
        $request->validate([
            'titulo'       => 'required|string|max:200',
            'area'         => 'required|in:academica,tecnica',
            'tipo_plan'    => 'required|in:diaria,semanal,quincenal,mensual',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'archivo'      => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $archivoPath   = $planesClase->archivo_path;
        $archivoNombre = $planesClase->archivo_nombre;
        $archivoTipo   = $planesClase->archivo_tipo;

        if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
            // Delete old file
            if ($archivoPath) Storage::disk('public')->delete($archivoPath);
            $file          = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoTipo   = $file->getMimeType();
            $archivoPath   = $file->store('planes_clase', 'public');
        }

        if ($request->boolean('eliminar_archivo') && $archivoPath) {
            Storage::disk('public')->delete($archivoPath);
            $archivoPath = $archivoNombre = $archivoTipo = null;
        }

        $planesClase->update([
            'asignacion_id'        => $request->asignacion_id ?: null,
            'titulo'               => $request->titulo,
            'area'                 => $request->area,
            'tipo_plan'            => $request->tipo_plan,
            'semana'               => $request->semana,
            'fecha_inicio'         => $request->fecha_inicio,
            'fecha_fin'            => $request->fecha_fin,
            'grado_seccion'        => $request->grado_seccion,
            'intencion_pedagogica' => $request->intencion_pedagogica,
            'estrategias'          => $request->estrategias ?? [],
            'observacion'          => $request->observacion,
            'archivo_path'         => $archivoPath,
            'archivo_nombre'       => $archivoNombre,
            'archivo_tipo'         => $archivoTipo,
            'publicado'            => $request->boolean('publicado'),
        ]);

        // Rebuild momentos
        $planesClase->momentos()->delete();
        $this->guardarMomentos($planesClase, $request->input('momentos', []));

        return redirect()->route('admin.planes-clase.show', $planesClase)
            ->with('success', 'Plan de clase actualizado correctamente.');
    }

    public function destroy(PlanClase $planesClase)
    {
        if ($planesClase->archivo_path) {
            Storage::disk('public')->delete($planesClase->archivo_path);
        }
        $planesClase->delete();
        return redirect()->route('admin.planes-clase.index')
            ->with('success', 'Plan de clase eliminado.');
    }

    public function download(PlanClase $planesClase)
    {
        if (!$planesClase->archivo_path || !Storage::disk('public')->exists($planesClase->archivo_path)) {
            return back()->with('error', 'Archivo no encontrado.');
        }
        return Storage::disk('public')->download($planesClase->archivo_path, $planesClase->archivo_nombre);
    }

    // ── Excel lista ──────────────────────────────────────────────────────

    public function listaExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = PlanClase::with(['docente', 'asignacion.asignatura', 'asignacion.grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        if ($user->hasRole('Docente') && $user->docente) {
            $query->where('docente_id', $user->docente->id);
        }

        if ($request->filled('area'))    $query->where('area', $request->area);
        if ($request->filled('docente')) $query->where('docente_id', $request->docente);
        if ($request->filled('search'))  $query->where('titulo', 'like', '%'.$request->search.'%');

        $planes = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Planes de Clase');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1d4ed8']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Planes de Clase — ' . ($schoolYear?->nombre ?? '') . ' — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Título', 'Área', 'Tipo', 'Docente', 'Asignatura / Grupo', 'Fecha Inicio', 'Publicado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        $tipoLabels = ['diaria' => 'Diaria', 'semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual'];

        foreach ($planes->values() as $i => $plan) {
            $row = $i + 4;
            $asignacion = $plan->asignacion;
            $grupo = $asignacion?->grupo?->nombre ?? '—';
            $asignatura = $asignacion?->asignatura?->nombre ?? '—';
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $plan->titulo);
            $ws->setCellValue("C{$row}", ucfirst($plan->area));
            $ws->setCellValue("D{$row}", $tipoLabels[$plan->tipo_plan] ?? $plan->tipo_plan);
            $ws->setCellValue("E{$row}", $plan->docente?->nombre_completo ?? '—');
            $ws->setCellValue("F{$row}", "{$asignatura} / {$grupo}");
            $ws->setCellValue("G{$row}", $plan->fecha_inicio ? \Carbon\Carbon::parse($plan->fecha_inicio)->format('d/m/Y') : '—');
            $ws->setCellValue("H{$row}", $plan->publicado ? 'Sí' : 'No');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'planes_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'planes_clase_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();

        $query = PlanClase::with(['docente', 'asignacion.asignatura', 'asignacion.grupo'])
            ->where('school_year_id', $schoolYear?->id)
            ->latest();

        if ($user->hasRole('Docente') && $user->docente) {
            $query->where('docente_id', $user->docente->id);
        }

        if ($request->filled('area'))    $query->where('area', $request->area);
        if ($request->filled('docente')) $query->where('docente_id', $request->docente);
        if ($request->filled('search'))  $query->where('titulo', 'like', '%'.$request->search.'%');

        $planes = $query->get();
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.planes_clase.lista_pdf',
            compact('planes', 'schoolYear', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('planes_clase_' . now()->format('Ymd') . '.pdf');
    }

    // ── Private helpers ──────────────────────────────────────────────────
    private function guardarMomentos(PlanClase $plan, array $momentos): void
    {
        $orden = 0;
        foreach (['inicio', 'desarrollo', 'cierre'] as $tipo) {
            $data = $momentos[$tipo] ?? null;
            if (!$data) continue;
            PlanClaseMomento::create([
                'plan_clase_id'           => $plan->id,
                'tipo'                    => $tipo,
                'orden'                   => $orden++,
                'duracion_minutos'        => $data['duracion_minutos'] ?? PlanClaseMomento::$tipoDuraciones[$tipo],
                'area_curricular'         => $data['area_curricular'] ?? null,
                'competencias_especificas'=> $data['competencias_especificas'] ?? null,
                'contenidos'              => $data['contenidos'] ?? null,
                'actividades'             => $data['actividades'] ?? null,
                'indicador_logro'         => $data['indicador_logro'] ?? null,
                'recursos'                => $data['recursos'] ?? null,
            ]);
        }
    }
}
