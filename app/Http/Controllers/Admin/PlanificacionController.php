<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Planificacion;
use App\Models\PlanificacionActividad;
use App\Models\PlanificacionRaItem;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlanificacionController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            return Docente::where('user_id', $user->id)->first()
                ?? Docente::where('email', $user->email)->first();
        }
        return null;
    }

    private function schoolYear(): SchoolYear
    {
        return SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        try {
            $schoolYear = $this->schoolYear();
        } catch (\Throwable) {
            $schoolYear = null;
        }

        $baseQuery = fn() => $schoolYear
            ? Planificacion::where('school_year_id', $schoolYear->id)
            : Planificacion::query();

        $total         = ($baseQuery)()->count();
        $publicadas    = ($baseQuery)()->where('publicado', true)->count();
        $borradores    = $total - $publicadas;
        $porRa         = ($baseQuery)()->where('tipo', 'ra')->count();
        $porActividad  = ($baseQuery)()->where('tipo', 'actividad')->count();

        // Cumplimiento: asignaciones técnicas con/sin planificación
        $asignaciones = $schoolYear
            ? \App\Models\Asignacion::with(['docente', 'asignatura', 'grupo'])
                ->where('school_year_id', $schoolYear->id)
                ->where('area', 'tecnica')
                ->where('activo', true)
                ->get()
            : collect();

        $conPlan    = $schoolYear
            ? ($baseQuery)()->pluck('asignacion_id')->unique()
            : collect();

        $sinPlan = $asignaciones->filter(fn($a) => ! $conPlan->contains($a->id));
        $pctCumplimiento = $asignaciones->count() > 0
            ? round($conPlan->count() / $asignaciones->count() * 100)
            : 0;

        // Últimas planificaciones
        $ultimas = ($baseQuery)()
            ->with(['asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.planificacion.dashboard', compact(
            'schoolYear', 'total', 'publicadas', 'borradores',
            'porRa', 'porActividad',
            'asignaciones', 'conPlan', 'sinPlan', 'pctCumplimiento',
            'ultimas'
        ));
    }

    // ── Listado ───────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $schoolYear  = $this->schoolYear();
        $docente     = $this->docenteActual();

        $query = Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where(function ($q) {
                $q->whereHas('asignacion', fn($sq) => $sq->where('area', 'tecnica'));
            });

        if ($docente) {
            $query->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('asignacion_id')) {
            $query->where('asignacion_id', $request->asignacion_id);
        }

        $planificaciones = $query->latest()->paginate(20)->withQueryString();

        // Asignaciones técnicas para el filtro
        $asigQuery = Asignacion::with(['asignatura', 'grupo'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica');
        if ($docente) $asigQuery->where('docente_id', $docente->id);
        $asignaciones = $asigQuery->get();

        return view('admin.planificacion.index', compact(
            'planificaciones', 'asignaciones', 'schoolYear'
        ));
    }

    // ── Crear planificación por RA ─────────────────────────────────────────

    public function createRa(Request $request)
    {
        $schoolYear  = $this->schoolYear();
        $docente     = $this->docenteActual();

        $asigQuery = Asignacion::with(['asignatura', 'grupo', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica');
        if ($docente) $asigQuery->where('docente_id', $docente->id);
        $asignaciones = $asigQuery->get();

        $asignacionSeleccionada = $request->filled('asignacion_id')
            ? Asignacion::with(['asignatura', 'grupo', 'docente'])->find($request->asignacion_id)
            : null;

        $planificacion = null;

        return view('admin.planificacion.crear_ra', compact(
            'planificacion', 'asignaciones', 'asignacionSeleccionada', 'schoolYear'
        ));
    }

    public function storeRa(Request $request)
    {
        $data = $request->validate([
            'asignacion_id'       => 'required|exists:asignaciones,id',
            'familia_profesional' => 'nullable|string|max:255',
            'denominacion'        => 'nullable|string|max:255',
            'modulo_nombre'       => 'nullable|string|max:255',
            'mf_codigo'           => 'nullable|string|max:50',
            'uc_codigo'           => 'nullable|string',
            'sesion'              => 'nullable|string|max:100',
            'nivel'               => 'nullable|string|max:10',
            'horas'               => 'nullable|numeric|min:0',
            'fecha_inicio'        => 'nullable|date',
            'fecha_fin'           => 'nullable|date',
            'publicado'           => 'boolean',
            // Items RA
            'ra'                  => 'nullable|array',
            'ra.*.ra_codigo'              => 'nullable|string|max:30',
            'ra.*.ra_descripcion'         => 'nullable|string',
            'ra.*.nivel_taxonomico'       => 'nullable|string|max:100',
            'ra.*.elementos_capacidad'    => 'nullable|string',
            'ra.*.fechas_desde'           => 'nullable|array',
            'ra.*.fechas_hasta'           => 'nullable|array',
            'ra.*.actividades'            => 'nullable|string',
            'ra.*.instrumentos_evaluacion'=> 'nullable|string',
            'ra.*.contenidos'             => 'nullable|string',
        ]);

        $schoolYear = $this->schoolYear();

        $plan = Planificacion::create([
            'asignacion_id'       => $data['asignacion_id'],
            'school_year_id'      => $schoolYear->id,
            'tipo'                => 'ra',
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => $request->boolean('publicado'),
            'creado_por'          => auth()->id(),
        ]);

        // Guardar items RA
        foreach ($data['ra'] ?? [] as $orden => $item) {
            if (empty($item['ra_descripcion']) && empty($item['ra_codigo'])) continue;

            // Construir array de fechas
            $fechas = [];
            foreach ($item['fechas_desde'] ?? [] as $i => $d) {
                if ($d || ($item['fechas_hasta'][$i] ?? null)) {
                    $fechas[] = ['desde' => $d, 'hasta' => $item['fechas_hasta'][$i] ?? null];
                }
            }

            // Elementos de capacidad: texto separado por líneas
            $elementos = [];
            foreach (array_filter(explode("\n", $item['elementos_capacidad'] ?? '')) as $ec) {
                $ec = trim($ec);
                if ($ec) $elementos[] = ['descripcion' => $ec];
            }

            PlanificacionRaItem::create([
                'planificacion_id'       => $plan->id,
                'orden'                  => $orden + 1,
                'ra_codigo'              => $item['ra_codigo'] ?? null,
                'ra_descripcion'         => $item['ra_descripcion'] ?? null,
                'nivel_taxonomico'       => $item['nivel_taxonomico'] ?? null,
                'elementos_capacidad'    => $elementos ?: null,
                'fechas'                 => $fechas ?: null,
                'actividades'            => $item['actividades'] ?? null,
                'instrumentos_evaluacion'=> $item['instrumentos_evaluacion'] ?? null,
                'contenidos'             => $item['contenidos'] ?? null,
            ]);
        }

        return redirect()->route('admin.planificacion.show', $plan)
            ->with('success', 'Planificación por RA creada correctamente.');
    }

    // ── Crear planificación por Actividad ─────────────────────────────────

    public function createActividad(Request $request)
    {
        $schoolYear = $this->schoolYear();
        $docente    = $this->docenteActual();

        $asigQuery = Asignacion::with(['asignatura', 'grupo', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica');
        if ($docente) $asigQuery->where('docente_id', $docente->id);
        $asignaciones = $asigQuery->get();

        $asignacionSeleccionada = $request->filled('asignacion_id')
            ? Asignacion::with(['asignatura', 'grupo', 'docente'])->find($request->asignacion_id)
            : null;

        $planificacion = null;

        return view('admin.planificacion.crear_actividad', compact(
            'planificacion', 'asignaciones', 'asignacionSeleccionada', 'schoolYear'
        ));
    }

    public function storeActividad(Request $request)
    {
        $data = $request->validate([
            'asignacion_id'          => 'required|exists:asignaciones,id',
            'familia_profesional'    => 'nullable|string|max:255',
            'denominacion'           => 'nullable|string|max:255',
            'modulo_nombre'          => 'nullable|string|max:255',
            'mf_codigo'              => 'nullable|string|max:50',
            'uc_codigo'              => 'nullable|string',
            'sesion'                 => 'nullable|string|max:100',
            'nivel'                  => 'nullable|string|max:10',
            'horas'                  => 'nullable|numeric|min:0',
            'fecha_inicio'           => 'nullable|date',
            'fecha_fin'              => 'nullable|date',
            'publicado'              => 'boolean',
            // Actividad
            'ra_codigo'              => 'nullable|string|max:30',
            'ra_descripcion'         => 'nullable|string',
            'actividad_numero'       => 'nullable|integer|min:1',
            'objetivo'               => 'nullable|string',
            'act_inicio'             => 'nullable|string',
            'act_desarrollo'         => 'nullable|string',
            'act_cierre'             => 'nullable|string',
            'estrategias'            => 'nullable|string',
            'recursos'               => 'nullable|string',
            'instrumentos_evaluacion'=> 'nullable|string',
        ]);

        $schoolYear = $this->schoolYear();

        $plan = Planificacion::create([
            'asignacion_id'       => $data['asignacion_id'],
            'school_year_id'      => $schoolYear->id,
            'tipo'                => 'actividad',
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => $request->boolean('publicado'),
            'creado_por'          => auth()->id(),
        ]);

        PlanificacionActividad::create([
            'planificacion_id'        => $plan->id,
            'ra_codigo'               => $data['ra_codigo'] ?? null,
            'ra_descripcion'          => $data['ra_descripcion'] ?? null,
            'actividad_numero'        => $data['actividad_numero'] ?? null,
            'objetivo'                => $data['objetivo'] ?? null,
            'act_inicio'              => $data['act_inicio'] ?? null,
            'act_desarrollo'          => $data['act_desarrollo'] ?? null,
            'act_cierre'              => $data['act_cierre'] ?? null,
            'estrategias'             => $data['estrategias'] ?? null,
            'recursos'                => $data['recursos'] ?? null,
            'instrumentos_evaluacion' => $data['instrumentos_evaluacion'] ?? null,
        ]);

        return redirect()->route('admin.planificacion.show', $plan)
            ->with('success', 'Planificación por Actividad creada correctamente.');
    }

    // ── Ver planificación ─────────────────────────────────────────────────

    public function show(Planificacion $planificacion)
    {
        $planificacion->load([
            'asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente',
            'raItems', 'actividades', 'schoolYear',
        ]);
        return view('admin.planificacion.show', compact('planificacion'));
    }

    // ── Editar planificación ──────────────────────────────────────────────

    public function edit(Planificacion $planificacion)
    {
        $schoolYear = $this->schoolYear();
        $docente    = $this->docenteActual();

        $asigQuery = Asignacion::with(['asignatura', 'grupo', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica');
        if ($docente) $asigQuery->where('docente_id', $docente->id);
        $asignaciones = $asigQuery->get();

        $planificacion->load(['raItems', 'actividades']);

        $view = $planificacion->tipo === 'ra'
            ? 'admin.planificacion.crear_ra'
            : 'admin.planificacion.crear_actividad';

        return view($view, compact('planificacion', 'asignaciones', 'schoolYear'));
    }

    public function update(Request $request, Planificacion $planificacion)
    {
        if ($planificacion->tipo === 'ra') {
            return $this->updateRa($request, $planificacion);
        }
        return $this->updateActividad($request, $planificacion);
    }

    private function updateRa(Request $request, Planificacion $planificacion)
    {
        $data = $request->validate([
            'asignacion_id'       => 'required|exists:asignaciones,id',
            'familia_profesional' => 'nullable|string|max:255',
            'denominacion'        => 'nullable|string|max:255',
            'modulo_nombre'       => 'nullable|string|max:255',
            'mf_codigo'           => 'nullable|string|max:50',
            'uc_codigo'           => 'nullable|string',
            'sesion'              => 'nullable|string|max:100',
            'nivel'               => 'nullable|string|max:10',
            'horas'               => 'nullable|numeric|min:0',
            'fecha_inicio'        => 'nullable|date',
            'fecha_fin'           => 'nullable|date',
            'publicado'           => 'boolean',
            'ra'                  => 'nullable|array',
            'ra.*.ra_codigo'               => 'nullable|string|max:30',
            'ra.*.ra_descripcion'          => 'nullable|string',
            'ra.*.nivel_taxonomico'        => 'nullable|string|max:100',
            'ra.*.elementos_capacidad'     => 'nullable|string',
            'ra.*.fechas_desde'            => 'nullable|array',
            'ra.*.fechas_hasta'            => 'nullable|array',
            'ra.*.actividades'             => 'nullable|string',
            'ra.*.instrumentos_evaluacion' => 'nullable|string',
            'ra.*.contenidos'              => 'nullable|string',
        ]);

        $planificacion->update([
            'asignacion_id'       => $data['asignacion_id'],
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => $request->boolean('publicado'),
        ]);

        $planificacion->raItems()->delete();

        foreach ($data['ra'] ?? [] as $orden => $item) {
            if (empty($item['ra_descripcion']) && empty($item['ra_codigo'])) continue;
            $fechas = [];
            foreach ($item['fechas_desde'] ?? [] as $i => $d) {
                if ($d || ($item['fechas_hasta'][$i] ?? null)) {
                    $fechas[] = ['desde' => $d, 'hasta' => $item['fechas_hasta'][$i] ?? null];
                }
            }
            $elementos = [];
            foreach (array_filter(explode("\n", $item['elementos_capacidad'] ?? '')) as $ec) {
                $ec = trim($ec);
                if ($ec) $elementos[] = ['descripcion' => $ec];
            }
            PlanificacionRaItem::create([
                'planificacion_id'        => $planificacion->id,
                'orden'                   => $orden + 1,
                'ra_codigo'               => $item['ra_codigo'] ?? null,
                'ra_descripcion'          => $item['ra_descripcion'] ?? null,
                'nivel_taxonomico'        => $item['nivel_taxonomico'] ?? null,
                'elementos_capacidad'     => $elementos ?: null,
                'fechas'                  => $fechas ?: null,
                'actividades'             => $item['actividades'] ?? null,
                'instrumentos_evaluacion' => $item['instrumentos_evaluacion'] ?? null,
                'contenidos'              => $item['contenidos'] ?? null,
            ]);
        }

        return redirect()->route('admin.planificacion.show', $planificacion)
            ->with('success', 'Planificación actualizada correctamente.');
    }

    private function updateActividad(Request $request, Planificacion $planificacion)
    {
        $data = $request->validate([
            'asignacion_id'          => 'required|exists:asignaciones,id',
            'familia_profesional'    => 'nullable|string|max:255',
            'denominacion'           => 'nullable|string|max:255',
            'modulo_nombre'          => 'nullable|string|max:255',
            'mf_codigo'              => 'nullable|string|max:50',
            'uc_codigo'              => 'nullable|string',
            'sesion'                 => 'nullable|string|max:100',
            'nivel'                  => 'nullable|string|max:10',
            'horas'                  => 'nullable|numeric|min:0',
            'fecha_inicio'           => 'nullable|date',
            'fecha_fin'              => 'nullable|date',
            'publicado'              => 'boolean',
            'ra_codigo'              => 'nullable|string|max:30',
            'ra_descripcion'         => 'nullable|string',
            'actividad_numero'       => 'nullable|integer|min:1',
            'objetivo'               => 'nullable|string',
            'act_inicio'             => 'nullable|string',
            'act_desarrollo'         => 'nullable|string',
            'act_cierre'             => 'nullable|string',
            'estrategias'            => 'nullable|string',
            'recursos'               => 'nullable|string',
            'instrumentos_evaluacion'=> 'nullable|string',
        ]);

        $planificacion->update([
            'asignacion_id'       => $data['asignacion_id'],
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => $request->boolean('publicado'),
        ]);

        $act = $planificacion->actividades()->first() ?? new PlanificacionActividad(['planificacion_id' => $planificacion->id]);
        $act->fill([
            'ra_codigo'               => $data['ra_codigo'] ?? null,
            'ra_descripcion'          => $data['ra_descripcion'] ?? null,
            'actividad_numero'        => $data['actividad_numero'] ?? null,
            'objetivo'                => $data['objetivo'] ?? null,
            'act_inicio'              => $data['act_inicio'] ?? null,
            'act_desarrollo'          => $data['act_desarrollo'] ?? null,
            'act_cierre'              => $data['act_cierre'] ?? null,
            'estrategias'             => $data['estrategias'] ?? null,
            'recursos'                => $data['recursos'] ?? null,
            'instrumentos_evaluacion' => $data['instrumentos_evaluacion'] ?? null,
        ])->save();

        return redirect()->route('admin.planificacion.show', $planificacion)
            ->with('success', 'Planificación actualizada correctamente.');
    }

    // ── Eliminar ──────────────────────────────────────────────────────────

    public function destroy(Planificacion $planificacion)
    {
        $planificacion->delete();
        return back()->with('success', 'Planificación eliminada.');
    }

    // ── Toggle publicado ──────────────────────────────────────────────────

    public function togglePublicado(Planificacion $planificacion)
    {
        $eraInactivo = !$planificacion->publicado;
        $planificacion->update(['publicado' => !$planificacion->publicado]);

        if ($eraInactivo && $planificacion->publicado) {
            $planificacion->load('asignacion.asignatura');
            $asignacion = $planificacion->asignacion;
            if ($asignacion) {
                try {
                    $sy = $this->schoolYear();
                    $userIds = Matricula::with('estudiante')
                        ->where('grupo_id', $asignacion->grupo_id)
                        ->where('school_year_id', $sy->id)
                        ->where('estado', 'activa')
                        ->get()
                        ->filter(fn($m) => $m->estudiante?->user_id)
                        ->pluck('estudiante.user_id')
                        ->unique()->values()->toArray();

                    if (!empty($userIds)) {
                        $modulo = $planificacion->modulo_nombre ?? $asignacion->asignatura?->nombre ?? 'planificación';
                        Notificacion::enviarA(
                            $userIds, 'planificacion',
                            'Nueva planificación publicada',
                            "Planificación publicada: {$modulo} — {$asignacion->asignatura?->nombre}."
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Error notif. planificacion: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', $planificacion->publicado ? 'Planificación publicada.' : 'Planificación despublicada.');
    }

    // ── Lista Excel de planificaciones ───────────────────────────────────
    public function listaExcel(Request $request)
    {
        $schoolYear = $this->schoolYear();
        $docente    = $this->docenteActual();

        $query = Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente'])
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('asignacion', fn($q) => $q->where('area', 'tecnica'));

        if ($docente) {
            $query->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id));
        }
        if ($request->filled('tipo'))          $query->where('tipo', $request->tipo);
        if ($request->filled('asignacion_id')) $query->where('asignacion_id', $request->asignacion_id);

        $planificaciones = $query->latest()->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet()->setTitle('Planificaciones');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'PLANIFICACIONES — ÁREA TÉCNICA — ' . $schoolYear->nombre);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Módulo', 'Código MF', 'Asignatura', 'Docente', 'Grupo', 'Tipo', 'Estado'] as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:H2')->applyFromArray($hdrStyle);

        foreach ($planificaciones as $i => $plan) {
            $row  = $i + 3;
            $tipo = $plan->tipo === 'ra' ? 'Por RA' : 'Por Actividad';
            $est  = $plan->publicado ? 'Publicado' : 'Borrador';

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $plan->modulo_nombre ?? '—');
            $sheet->setCellValue("C{$row}", $plan->mf_codigo ?? '—');
            $sheet->setCellValue("D{$row}", $plan->asignacion?->asignatura?->nombre ?? '—');
            $sheet->setCellValue("E{$row}", $plan->asignacion?->docente?->nombre_completo ?? '—');
            $sheet->setCellValue("F{$row}", $plan->asignacion?->grupo?->nombre_completo ?? '—');
            $sheet->setCellValue("G{$row}", $tipo);
            $sheet->setCellValue("H{$row}", $est);

            if (! $plan->publicado && $i % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f9fafb');
            } elseif ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'plan_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'planificaciones_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Cumplimiento de planificaciones por docente Excel ─────────────────
    public function cumplimientoExcel(Request $request)
    {
        $schoolYear = $this->schoolYear();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica')
            ->where('activo', true)
            ->orderBy('docente_id')
            ->get();

        $planIds = Planificacion::where('school_year_id', $schoolYear->id)
            ->pluck('asignacion_id')
            ->unique();

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Cumplimiento');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'CUMPLIMIENTO DE PLANIFICACIONES — ' . $schoolYear->nombre);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Docente', 'Asignatura', 'Grupo', 'Grado', 'Estado'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65 + $i) . '2', $h);
        $sheet->getStyle('A2:F2')->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(22);

        foreach ($asignaciones as $i => $asig) {
            $r         = $i + 3;
            $tiene     = $planIds->contains($asig->id);
            $estado    = $tiene ? 'Con planificación' : 'Sin planificación';

            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", $asig->docente?->nombre_completo ?? '(Sin docente)');
            $sheet->setCellValue("C{$r}", $asig->asignatura?->nombre ?? '—');
            $sheet->setCellValue("D{$r}", $asig->grupo?->seccion?->nombre ?? '—');
            $sheet->setCellValue("E{$r}", $asig->grupo?->grado?->nombre ?? '—');
            $sheet->setCellValue("F{$r}", $estado);

            if ($tiene) {
                $sheet->getStyle("F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d1fae5');
                $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('065f46');
            } else {
                $sheet->getStyle("F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('fee2e2');
                $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('991b1b');
            }
            $sheet->getStyle("F{$r}")->getFont()->setBold(true);
            $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$r}:E{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
        }

        foreach (range('A', 'F') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'pln_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'Cumplimiento_Planificaciones_' . $schoolYear->nombre . '.xlsx')->deleteFileAfterSend();
    }

    // ── Cumplimiento PDF ─────────────────────────────────────────────────
    public function cumplimientoPdf(Request $request)
    {
        $schoolYear = $this->schoolYear();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'tecnica')
            ->where('activo', true)
            ->orderBy('docente_id')
            ->get();

        $planIds = Planificacion::where('school_year_id', $schoolYear->id)
            ->pluck('asignacion_id')
            ->unique();

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.planificacion.cumplimiento_pdf',
            compact('asignaciones', 'planIds', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('Cumplimiento_Planificaciones_' . $schoolYear->nombre . '.pdf');
    }

    // ── Lista PDF de planificaciones ─────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $schoolYear = $this->schoolYear();
        $docente    = $this->docenteActual();

        $query = Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente'])
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('asignacion', fn($q) => $q->where('area', 'tecnica'));

        if ($docente) {
            $query->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id));
        }
        if ($request->filled('tipo'))          $query->where('tipo', $request->tipo);
        if ($request->filled('asignacion_id')) $query->where('asignacion_id', $request->asignacion_id);

        $planificaciones = $query->latest()->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.planificacion.lista_pdf',
            compact('planificaciones', 'inst', 'schoolYear')
        )->setPaper('letter', 'landscape');

        return $pdf->download('planificaciones_' . now()->format('Ymd') . '.pdf');
    }

    // ── PDF de planificación ──────────────────────────────────────────────
    public function pdf(Planificacion $planificacion)
    {
        $planificacion->load([
            'asignacion.asignatura', 'asignacion.grupo.grado', 'asignacion.grupo.seccion',
            'asignacion.docente', 'raItems', 'actividades', 'schoolYear',
        ]);

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = $this->schoolYear();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.planificacion.planificacion_pdf',
            compact('planificacion', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($planificacion->modulo_nombre ?? $planificacion->titulo ?? 'planificacion');
        return $pdf->download("planificacion_{$slug}.pdf");
    }
}
