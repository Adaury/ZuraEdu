<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HorarioDetalleRequest;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Aula;
use App\Models\Comunicado;
use App\Models\ConfigInstitucional;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\FranjaHoraria;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\SchoolYear;
use App\Models\Suplencia;
use App\Services\HorarioGeneratorService;
use App\Services\HorarioIntegrityChecker;
use App\Services\HorarioValidatorService;
use App\Services\SuplenciaService;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    // ── INDEX: lista de horarios ──────────────────────────────────────────
    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $horarios   = Horario::with('creador')
            ->where('school_year_id', $schoolYear?->id)
            ->orderByDesc('created_at')
            ->get();

        $grupos = Grupo::with('grado', 'seccion')
            ->where('school_year_id', $schoolYear?->id)
            ->orderBy('grado_id')
            ->get();

        return view('admin.horarios.index', compact('horarios', 'schoolYear', 'grupos'));
    }

    // ── MI HORARIO: vista personal del docente autenticado ───────────────
    public function miHorario()
    {
        $user    = auth()->user();
        $docente = \App\Models\Docente::where('user_id', $user->id)->first();

        if (! $docente) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'No tienes un perfil de docente asociado a tu cuenta.');
        }

        $schoolYear    = SchoolYear::actual();
        $horarioActivo = $schoolYear
            ? Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')
                ->latest()
                ->first()
            : null;

        $detalles  = collect();
        $grid      = [];
        $franjas   = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $colores   = [];

        if ($horarioActivo) {
            $detalles = HorarioDetalle::with([
                    'asignacion.asignatura',
                    'asignacion.grupo.grado',
                    'asignacion.grupo.seccion',
                    'franja',
                    'aula',
                ])
                ->where('horario_id', $horarioActivo->id)
                ->whereHas('asignacion', fn ($q) => $q->where('docente_id', $docente->id))
                ->get();

            foreach ($detalles as $d) {
                $grid[$d->franja_id][$d->dia] = $d;
            }

            $colores = $this->generarColores($detalles);
        }

        // Estadísticas de carga semanal
        $stats = [
            'clases_semana' => $detalles->count(),
            'grupos'        => $detalles->pluck('asignacion.grupo_id')->unique()->count(),
            'asignaturas'   => $detalles->pluck('asignacion.asignatura_id')->unique()->count(),
        ];

        return view('admin.horarios.mi-horario', compact(
            'docente', 'horarioActivo', 'detalles', 'grid',
            'franjas', 'colores', 'schoolYear', 'stats'
        ));
    }

    // ── HORARIO POR DOCENTE (admin selecciona cualquier docente) ─────────
    public function horarioDocente(Request $request)
    {
        $schoolYear    = SchoolYear::actual();
        $docentes      = Docente::orderBy('apellidos')->get();
        $docenteId     = $request->input('docente_id');
        $docente       = $docenteId ? Docente::find($docenteId) : null;

        $horarioActivo = $schoolYear
            ? Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')
                ->latest()
                ->first()
            : null;

        $detalles = collect();
        $grid     = [];
        $franjas  = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $colores  = [];
        $stats    = ['clases_semana' => 0, 'grupos' => 0, 'asignaturas' => 0];

        if ($horarioActivo && $docente) {
            $detalles = HorarioDetalle::with([
                    'asignacion.asignatura',
                    'asignacion.grupo.grado',
                    'asignacion.grupo.seccion',
                    'franja',
                    'aula',
                ])
                ->where('horario_id', $horarioActivo->id)
                ->whereHas('asignacion', fn ($q) => $q->where('docente_id', $docente->id))
                ->get();

            foreach ($detalles as $d) {
                $grid[$d->franja_id][$d->dia] = $d;
            }

            $colores = $this->generarColores($detalles);
            $stats   = [
                'clases_semana' => $detalles->count(),
                'grupos'        => $detalles->pluck('asignacion.grupo_id')->unique()->count(),
                'asignaturas'   => $detalles->pluck('asignacion.asignatura_id')->unique()->count(),
            ];
        }

        return view('admin.horarios.horario-docente', compact(
            'docentes', 'docente', 'horarioActivo', 'detalles',
            'grid', 'franjas', 'colores', 'schoolYear', 'stats'
        ));
    }

    // ── Horario por docente PDF ───────────────────────────────────────────
    public function horarioDocentePdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $docenteId  = $request->input('docente_id');
        $docente    = $docenteId ? Docente::find($docenteId) : null;

        if (! $docente) abort(404, 'Docente no especificado.');

        $horarioActivo = $schoolYear
            ? Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')->latest()->first()
            : null;

        $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $grid    = [];

        if ($horarioActivo) {
            $detalles = HorarioDetalle::with([
                    'asignacion.asignatura',
                    'asignacion.grupo.grado',
                    'asignacion.grupo.seccion',
                    'franja', 'aula',
                ])
                ->where('horario_id', $horarioActivo->id)
                ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id))
                ->get();

            foreach ($detalles as $d) {
                $grid[$d->franja_id][$d->dia] = $d;
            }
        }

        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $dias   = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.horarios.horario_docente_pdf',
            compact('docente', 'schoolYear', 'grid', 'franjas', 'dias', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($docente->nombre_completo ?? 'docente');
        return $pdf->download("horario_{$slug}.pdf");
    }

    // ── VISTA MAESTRA: grid aSc-style (todos los grupos × días × franjas) ──
    public function vistaMaestra(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $horarios   = Horario::where('school_year_id', $schoolYear?->id)
            ->orderByDesc('created_at')
            ->get();

        $horarioId = $request->input('horario_id');
        $horario   = $horarioId
            ? Horario::find($horarioId)
            : ($horarios->firstWhere('estado', 'publicado') ?? $horarios->first());

        $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $dias    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $grupos  = collect();
        $grid    = [];
        $colores = [];

        if ($horario) {
            $detalles = HorarioDetalle::where('horario_id', $horario->id)
                ->with([
                    'asignacion.grupo.grado',
                    'asignacion.grupo.seccion',
                    'asignacion.asignatura',
                    'asignacion.docente',
                    'franja',
                ])
                ->get();

            $grupos = $detalles
                ->pluck('asignacion.grupo')
                ->filter()
                ->unique('id')
                ->sortBy(fn ($g) => sprintf('%03d-%s', $g->grado?->nivel ?? 0, $g->nombre_completo ?? ''));

            foreach ($detalles as $d) {
                $grupoId = $d->asignacion?->grupo_id;
                if ($grupoId) {
                    $grid[$grupoId][$d->dia][$d->franja_id] = $d;
                }
            }
            $colores = $this->generarColores($detalles);
        }

        return view('admin.horarios.vista-maestra', compact(
            'horarios', 'horario', 'schoolYear',
            'franjas', 'grupos', 'grid', 'dias', 'colores'
        ));
    }

    // ── Horario maestro del centro PDF ───────────────────────────────────
    public function vistaMaestraPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $horarioId  = $request->input('horario_id');
        $horarios   = Horario::where('school_year_id', $schoolYear?->id)->get();
        $horario    = $horarioId
            ? Horario::find($horarioId)
            : ($horarios->firstWhere('estado', 'publicado') ?? $horarios->first());

        if (! $horario) abort(404, 'No hay horario disponible.');

        $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $dias    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $grupos  = collect();
        $grid    = [];

        $detalles = HorarioDetalle::where('horario_id', $horario->id)
            ->with(['asignacion.grupo.grado', 'asignacion.grupo.seccion',
                    'asignacion.asignatura', 'asignacion.docente', 'franja'])
            ->get();

        $grupos = $detalles->pluck('asignacion.grupo')->filter()->unique('id')
            ->sortBy(fn($g) => sprintf('%03d-%s', $g->grado?->nivel ?? 0, $g->nombre_completo ?? ''));

        foreach ($detalles as $d) {
            $grupoId = $d->asignacion?->grupo_id;
            if ($grupoId) $grid[$grupoId][$d->dia][$d->franja_id] = $d;
        }

        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.horarios.vista_maestra_pdf',
            compact('horario', 'schoolYear', 'franjas', 'grupos', 'grid', 'dias', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        return $pdf->download('horario_maestro_' . now()->format('Ymd') . '.pdf');
    }

    // ── Horario maestro del centro Excel ─────────────────────────────────
    public function vistaMaestraExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $horarioId  = $request->input('horario_id');
        $horarios   = Horario::where('school_year_id', $schoolYear?->id)->get();
        $horario    = $horarioId
            ? Horario::find($horarioId)
            : ($horarios->firstWhere('estado', 'publicado') ?? $horarios->first());

        if (! $horario) abort(404, 'No hay horario disponible.');

        $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $dias    = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $grid    = [];

        $detalles = HorarioDetalle::where('horario_id', $horario->id)
            ->with(['asignacion.grupo.grado', 'asignacion.grupo.seccion',
                    'asignacion.asignatura', 'asignacion.docente', 'franja'])
            ->get();

        $grupos = $detalles->pluck('asignacion.grupo')->filter()->unique('id')
            ->sortBy(fn($g) => sprintf('%03d-%s', $g->grado?->nivel ?? 0, $g->nombre_completo ?? ''));

        foreach ($detalles as $d) {
            $grupoId = $d->asignacion?->grupo_id;
            if ($grupoId) $grid[$grupoId][strtolower(str_replace('é','e',$d->dia))][$d->franja_id] = $d;
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Horario Maestro');

        // Título
        $totalCols = $franjas->count() * 5 + 1;
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
        $ws->mergeCells("A1:{$lastColLetter}1");
        $ws->setCellValue('A1', 'Horario Maestro — ' . ($schoolYear?->nombre ?? date('Y')));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Headers: Grupo + [día1_franja1 ... día5_franjaX]
        $ws->setCellValue('A2', 'Grupo');
        $ws->getStyle('A2')->getFont()->setBold(true);
        $col = 2;
        foreach ($dias as $dia) {
            foreach ($franjas as $franja) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $ws->setCellValue("{$colLetter}2", substr($dia, 0, 3) . ' ' . $franja->nombre);
                $ws->getStyle("{$colLetter}2")->getFont()->setBold(true)->setSize(7);
                $ws->getStyle("{$colLetter}2")->getFill()
                   ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('1e3a6e');
                $ws->getStyle("{$colLetter}2")->getFont()->getColor()->setRGB('ffffff');
                $col++;
            }
        }

        $diasKeys = ['lunes','martes','miercoles','jueves','viernes'];
        foreach ($grupos as $row => $grupo) {
            $excelRow = $row + 3;
            $ws->setCellValue("A{$excelRow}", $grupo->nombre_completo ?? '');
            $ws->getStyle("A{$excelRow}")->getFont()->setBold(true);
            $col = 2;
            foreach ($diasKeys as $diaKey) {
                foreach ($franjas as $franja) {
                    $det = $grid[$grupo->id][$diaKey][$franja->id] ?? null;
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $ws->setCellValue("{$colLetter}{$excelRow}", $det ? ($det->asignacion?->asignatura?->nombre ?? '—') : '');
                    $ws->getStyle("{$colLetter}{$excelRow}")->getFont()->setSize(7);
                    $col++;
                }
            }
        }

        foreach (range(1, $col - 1) as $ci) {
            $ws->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci)
            )->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="horario_maestro_' . now()->format('Ymd') . '.xlsx"',
        ]);
    }

    // ── SHOW: vista visual del horario (tabla días × horas) ───────────────
    public function show(Horario $horario, Request $request)
    {
        $grupoId = $request->input('grupo_id');
        $docenteId = $request->input('docente_id');

        $detalles = HorarioDetalle::with(['asignacion.grupo', 'asignacion.docente', 'asignacion.asignatura', 'aula', 'franja'])
            ->where('horario_id', $horario->id)
            ->when($grupoId,   fn($q) => $q->whereHas('asignacion', fn($q2) => $q2->where('grupo_id', $grupoId)))
            ->when($docenteId, fn($q) => $q->whereHas('asignacion', fn($q2) => $q2->where('docente_id', $docenteId)))
            ->get();

        $franjas     = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $grupos      = \App\Models\Grupo::with('grado', 'seccion')
            ->where('school_year_id', $horario->school_year_id)
            ->orderBy('grado_id')
            ->get();
        $docentes    = Docente::orderBy('apellidos')->get();
        $asignaturas = Asignatura::activas()->orderBy('nombre')->get();
        $aulas       = Aula::where('disponible', true)->orderBy('nombre')->get();

        // Build grid: [franja_id][dia] = detalle
        $grid = [];
        foreach ($detalles as $d) {
            $grid[$d->franja_id][$d->dia] = $d;
        }

        // Color palette for subjects (consistent colors)
        $colores = $this->generarColores($detalles);

        return view('admin.horarios.show', compact(
            'horario', 'detalles', 'franjas', 'grid', 'grupos', 'docentes',
            'grupoId', 'docenteId', 'colores', 'asignaturas', 'aulas'
        ));
    }

    // ── GENERAR: valida, lanza el algoritmo y verifica integridad ─────────
    public function generar(Request $request)
    {
        $request->validate([
            'nombre'     => 'nullable|string|max:100',
            'grupo_ids'  => 'nullable|array',
            'grupo_ids.*'=> 'integer|exists:grupos,id',
        ]);

        $grupoIds = array_filter((array) $request->input('grupo_ids', []));
        $isAjax   = $request->expectsJson();

        // ── FASE 1: Validación previa ─────────────────────────────────────────
        $validacion = (new HorarioValidatorService)->validar(null, $grupoIds);

        if (! $validacion['valido']) {
            if ($isAjax) {
                return response()->json([
                    'error'       => implode(' | ', $validacion['errores']),
                    'errores'     => $validacion['errores'],
                    'advertencias'=> $validacion['advertencias'],
                    'stats'       => $validacion['stats'],
                    'sugerencias' => $validacion['sugerencias'],
                ], 422);
            }
            return back()->with('error', implode(' ', $validacion['errores']));
        }

        // ── FASE 2: Ejecutar el algoritmo ─────────────────────────────────────
        $result = (new HorarioGeneratorService)->generar(
            null,
            $request->input('nombre') ?? ('Horario ' . now()->format('d/m/Y H:i')),
            $grupoIds
        );

        if (isset($result['error'])) {
            $payload = [
                'error'       => $result['error'],
                'sugerencias' => $result['sugerencias'] ?? [
                    'Revisa la disponibilidad de los docentes.',
                    'Comprueba que las franjas horarias sean suficientes.',
                    'Intenta reducir la carga horaria de algunas materias.',
                ],
                'debug'       => $result['debug'] ?? [],
            ];
            if ($isAjax) {
                return response()->json($payload, 422);
            }
            return back()->with('error', $payload['error']);
        }

        // ── FASE 6: Verificar integridad del horario generado ─────────────────
        $integridad = (new HorarioIntegrityChecker)->verificar(
            Horario::find($result['horario_id'])
        );

        $msg = $result['pendientes'] === 0
            ? "Horario generado: {$result['asignados']} clases · Score: {$result['score']}%"
            : "Horario generado con {$result['pendientes']} conflicto(s). Score: {$result['score']}%.";

        if ($isAjax) {
            return response()->json([
                'ok'           => true,
                'horario_id'   => $result['horario_id'],
                'redirect'     => route('admin.horarios.show', $result['horario_id']),
                'message'      => $msg,
                'score'        => $result['score'],
                'asignados'    => $result['asignados'],
                'pendientes'   => $result['pendientes'],
                'conflictos'   => $result['conflictos'],
                'integridad'   => $integridad,
                'advertencias' => $validacion['advertencias'],
                'stats'        => $validacion['stats'],
                'debug'        => $result['debug'] ?? [],
            ]);
        }

        if (! empty($result['conflictos'])) {
            session()->flash('conflictos', $result['conflictos']);
        }

        return redirect()->route('admin.horarios.show', $result['horario_id'])
            ->with($result['pendientes'] === 0 ? 'success' : 'warning', $msg);
    }

    // ── REGENERAR: reejecutar algoritmo sobre horario existente ──────────
    public function regenerar(Request $request, Horario $horario)
    {
        $request->validate([
            'grupo_ids'   => 'nullable|array',
            'grupo_ids.*' => 'integer|exists:grupos,id',
        ]);

        $grupoIds = array_filter((array) $request->input('grupo_ids', []));

        // Validación previa
        $validacion = (new HorarioValidatorService)->validar($horario->school_year_id, $grupoIds);

        if (! $validacion['valido']) {
            return response()->json([
                'error'      => implode(' | ', $validacion['errores']),
                'errores'    => $validacion['errores'],
                'sugerencias'=> $validacion['sugerencias'],
            ], 422);
        }

        $result = (new HorarioGeneratorService)->generar(
            $horario->school_year_id,
            $horario->nombre,
            $grupoIds,
            $horario->id
        );

        if (isset($result['error'])) {
            return response()->json([
                'error'      => $result['error'],
                'sugerencias'=> $result['sugerencias'] ?? [],
            ], 422);
        }

        $integridad = (new HorarioIntegrityChecker)->verificar(
            $horario->fresh()
        );

        $msg = $result['pendientes'] === 0
            ? "Regenerado: {$result['asignados']} clases · Score: {$result['score']}%"
            : "Regenerado con {$result['pendientes']} conflicto(s). Score: {$result['score']}%.";

        return response()->json([
            'ok'         => true,
            'redirect'   => route('admin.horarios.show', $horario->id),
            'message'    => $msg,
            'score'      => $result['score'],
            'asignados'  => $result['asignados'],
            'pendientes' => $result['pendientes'],
            'conflictos' => $result['conflictos'],
            'integridad' => $integridad,
            'debug'      => $result['debug'] ?? [],
        ]);
    }

    // ── PUBLICAR / DESPUBLICAR ────────────────────────────────────────────
    public function publicar(Horario $horario)
    {
        $nuevoEstado = $horario->estado === 'publicado' ? 'borrador' : 'publicado';
        $horario->update(['estado' => $nuevoEstado]);

        // Auto-crear comunicado cuando se publica
        if ($nuevoEstado === 'publicado') {
            Comunicado::create([
                'titulo'             => "Horario publicado: {$horario->nombre}",
                'cuerpo'             => "El horario escolar <strong>{$horario->nombre}</strong> ha sido publicado y ya está disponible para consulta de toda la comunidad educativa.",
                'autor_id'           => auth()->id(),
                'tipo_destinatarios' => 'todos',
                'published_at'       => now(),
                'activo'             => true,
            ]);
        }

        return back()->with('success', "Horario " . ($nuevoEstado === 'publicado' ? 'publicado' : 'vuelto a borrador') . ".");
    }

    // ── MOVER DETALLE (drag & drop AJAX) ─────────────────────────────────
    public function moverDetalle(Request $request, HorarioDetalle $detalle)
    {
        $request->validate([
            'dia'      => 'required|in:lunes,martes,miercoles,jueves,viernes',
            'franja_id'=> 'required|exists:franjas_horarias,id',
        ]);

        $docenteId = $detalle->asignacion->docente_id;
        $grupoId   = $detalle->asignacion->grupo_id;
        $dia       = $request->dia;
        $franjaId  = $request->franja_id;
        $horarioId = $detalle->horario_id;

        // Check conflict
        $conflictoDocente = HorarioDetalle::where('horario_id', $horarioId)
            ->where('dia', $dia)->where('franja_id', $franjaId)
            ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docenteId))
            ->where('id', '!=', $detalle->id)
            ->exists();

        $conflictoGrupo = HorarioDetalle::where('horario_id', $horarioId)
            ->where('dia', $dia)->where('franja_id', $franjaId)
            ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $grupoId))
            ->where('id', '!=', $detalle->id)
            ->exists();

        if ($conflictoDocente) return response()->json(['error' => 'El docente ya tiene clase en ese horario.'], 422);
        if ($conflictoGrupo)   return response()->json(['error' => 'El grupo ya tiene clase en ese horario.'], 422);

        $detalle->update(['dia' => $dia, 'franja_id' => $franjaId]);

        return response()->json(['ok' => true, 'message' => 'Clase movida correctamente.']);
    }

    // ── AULAS CRUD ────────────────────────────────────────────────────────
    public function aulas()
    {
        $aulas = Aula::orderBy('nombre')->paginate(20);
        return view('admin.horarios.aulas', compact('aulas'));
    }

    public function aulaStore(Request $request)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:100',
            'codigo'     => 'nullable|string|max:20',
            'capacidad'  => 'required|integer|min:1|max:200',
            'tipo'       => 'required|in:aula,laboratorio,taller,gimnasio,biblioteca',
            'piso'       => 'nullable|string|max:20',
            'disponible' => 'boolean',
        ]);
        $data['disponible'] = $request->boolean('disponible', true);
        Aula::create($data);
        return back()->with('success', 'Aula registrada.');
    }

    public function aulaUpdate(Request $request, Aula $aula)
    {
        $data = $request->validate([
            'nombre'     => 'required|string|max:100',
            'capacidad'  => 'required|integer|min:1',
            'tipo'       => 'required|in:aula,laboratorio,taller,gimnasio,biblioteca',
            'disponible' => 'boolean',
        ]);
        $data['disponible'] = $request->boolean('disponible', true);
        $aula->update($data);
        return back()->with('success', 'Aula actualizada.');
    }

    public function aulaDestroy(Aula $aula)
    {
        $aula->delete();
        return back()->with('success', 'Aula eliminada.');
    }

    // ── FRANJAS HORARIAS ──────────────────────────────────────────────────
    public function franjas()
    {
        $franjas = FranjaHoraria::orderBy('numero')->get();
        return view('admin.horarios.franjas', compact('franjas'));
    }

    public function franjaStore(Request $request)
    {
        $data = $request->validate([
            'numero'      => 'required|integer|min:1',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'nombre'      => 'nullable|string|max:50',
            'es_recreo'   => 'boolean',
        ]);
        $data['es_recreo'] = $request->boolean('es_recreo', false);
        FranjaHoraria::create($data);
        return back()->with('success', 'Franja horaria agregada.');
    }

    public function franjaUpdate(Request $request, FranjaHoraria $franja)
    {
        $data = $request->validate([
            'numero'      => 'required|integer|min:1',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'nombre'      => 'nullable|string|max:50',
            'es_recreo'   => 'boolean',
            'activa'      => 'boolean',
        ]);
        $data['es_recreo'] = $request->boolean('es_recreo', false);
        $data['activa']    = $request->boolean('activa', true);
        $franja->update($data);
        return back()->with('success', 'Franja horaria actualizada.');
    }

    public function franjaDestroy(FranjaHoraria $franja)
    {
        $franja->delete();
        return back()->with('success', 'Franja eliminada.');
    }

    // ── DISPONIBILIDAD DOCENTES ───────────────────────────────────────────
    public function disponibilidad(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $docentes   = Docente::with('user')->orderBy('apellidos')->get();
        $franjas    = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
        $docenteId  = $request->input('docente_id', $docentes->first()?->id);

        $disponibilidad = DisponibilidadDocente::where('docente_id', $docenteId)
            ->where('school_year_id', $schoolYear?->id)
            ->get()
            ->keyBy(fn($d) => $d->dia . '_' . $d->franja_id);

        return view('admin.horarios.disponibilidad', compact(
            'docentes', 'franjas', 'docenteId', 'disponibilidad', 'schoolYear'
        ));
    }

    public function disponibilidadGuardar(Request $request)
    {
        $request->validate(['docente_id' => 'required|exists:docentes,id']);
        $schoolYear = SchoolYear::actual();
        $docenteId  = $request->docente_id;

        // Delete existing and re-create
        DisponibilidadDocente::where('docente_id', $docenteId)
            ->where('school_year_id', $schoolYear->id)
            ->delete();

        $dias   = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $franjas = FranjaHoraria::where('activa', true)->pluck('id');

        foreach ($dias as $dia) {
            foreach ($franjas as $franjaId) {
                $key = "{$dia}_{$franjaId}";
                DisponibilidadDocente::create([
                    'docente_id'    => $docenteId,
                    'dia'           => $dia,
                    'franja_id'     => $franjaId,
                    'disponible'    => $request->has("disponible.{$key}"),
                    'school_year_id'=> $schoolYear->id,
                ]);
            }
        }

        return back()->with('success', 'Disponibilidad guardada.');
    }

    // ── SUPLENCIAS ────────────────────────────────────────────────────────
    public function suplencias()
    {
        $suplencias = Suplencia::with(['docenteOriginal', 'docenteSuplente', 'detalle.asignacion.grupo', 'detalle.asignacion.asignatura', 'detalle.franja'])
            ->orderByDesc('fecha')
            ->paginate(20);
        $docentes = Docente::orderBy('apellidos')->get();
        return view('admin.horarios.suplencias', compact('suplencias', 'docentes'));
    }

    public function suplenciaUpdate(Request $request, Suplencia $suplencia)
    {
        $data = $request->validate([
            'estado'              => 'required|in:pendiente,cubierta,sin_cubrir,cancelada',
            'docente_suplente_id' => 'nullable|exists:docentes,id',
            'motivo'              => 'nullable|string|max:200',
        ]);

        $suplencia->update([
            'estado'              => $data['estado'],
            'docente_suplente_id' => $data['docente_suplente_id'] ?: null,
            'motivo'              => $data['motivo'] ?? $suplencia->motivo,
        ]);

        return back()->with('success', 'Suplencia actualizada correctamente.');
    }

    public function suplenciaStore(Request $request)
    {
        $data = $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'fecha'      => 'required|date',
            'motivo'     => 'required|string|max:200',
        ]);

        $service = new SuplenciaService();
        $result  = $service->registrarAusencia(
            (int) $data['docente_id'],
            \Carbon\Carbon::parse($data['fecha']),
            $data['motivo'],
            auth()->id()
        );

        if (isset($result['error'])) return back()->with('error', $result['error']);

        $msg = count($result['suplencias']) . " suplencia(s) creada(s).";
        if ($result['sin_cubrir'] > 0) {
            $msg .= " {$result['sin_cubrir']} clase(s) sin cubrir — revisa la lista.";
        }

        return back()->with('success', $msg);
    }

    // ── CREACIÓN MANUAL DE CELDAS ─────────────────────────────────────────

    /**
     * Guarda una nueva entrada de horario creada manualmente.
     * Busca o crea la asignación docente-asignatura-grupo antes de insertar el detalle.
     */
    public function detalleStore(HorarioDetalleRequest $request, Horario $horario)
    {
        $schoolYear = SchoolYear::actual();

        // Buscar o crear la asignación base (docente ↔ asignatura ↔ grupo)
        $asignacion = Asignacion::firstOrCreate(
            [
                'school_year_id' => $schoolYear->id,
                'grupo_id'       => $request->grupo_id,
                'asignatura_id'  => $request->asignatura_id,
                'docente_id'     => $request->docente_id,
            ],
            [
                'activo'           => true,
                'horas_semana'     => 1,
                'tipo_evaluacion'  => 'academica',
            ]
        );

        HorarioDetalle::create([
            'horario_id'    => $horario->id,
            'asignacion_id' => $asignacion->id,
            'aula_id'       => $request->aula_id,
            'franja_id'     => $request->franja_id,
            'dia'           => $request->dia,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Clase agregada correctamente.']);
        }

        return back()->with('success', 'Clase agregada al horario.');
    }

    /**
     * Actualiza una celda existente del horario (edición manual).
     */
    public function detalleUpdate(HorarioDetalleRequest $request, Horario $horario, HorarioDetalle $detalle)
    {
        $schoolYear = SchoolYear::actual();

        // Si cambió asignatura/docente/grupo, buscar o crear nueva asignación
        $asignacion = Asignacion::firstOrCreate(
            [
                'school_year_id' => $schoolYear->id,
                'grupo_id'       => $request->grupo_id,
                'asignatura_id'  => $request->asignatura_id,
                'docente_id'     => $request->docente_id,
            ],
            [
                'activo'          => true,
                'horas_semana'    => 1,
                'tipo_evaluacion' => 'academica',
            ]
        );

        $detalle->update([
            'asignacion_id' => $asignacion->id,
            'aula_id'       => $request->aula_id,
            'franja_id'     => $request->franja_id,
            'dia'           => $request->dia,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Clase actualizada correctamente.']);
        }

        return back()->with('success', 'Clase actualizada.');
    }

    /**
     * Elimina una celda del horario.
     */
    public function detalleDestroy(Horario $horario, HorarioDetalle $detalle)
    {
        abort_unless($detalle->horario_id === $horario->id, 404);
        $detalle->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Clase eliminada del horario.');
    }

    /**
     * Devuelve los datos necesarios para el formulario de creación/edición manual.
     * Usado vía AJAX para pre-poblar el modal.
     */
    public function detalleFormData(Horario $horario)
    {
        $schoolYear = SchoolYear::actual();

        return response()->json([
            'grupos'      => Grupo::with('grado', 'seccion')
                ->where('school_year_id', $horario->school_year_id)
                ->orderBy('grado_id')
                ->get(['id', 'grado_id', 'seccion_id'])
                ->map(fn($g) => [
                    'id'     => $g->id,
                    'nombre' => $g->nombre_completo ?? ($g->grado?->nombre . ' ' . $g->seccion?->nombre),
                ]),
            'asignaturas' => Asignatura::activas()->orderBy('nombre')->get(['id', 'nombre', 'color']),
            'docentes'    => Docente::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos'])
                ->map(fn($d) => ['id' => $d->id, 'nombre' => $d->apellidos . ', ' . $d->nombres]),
            'aulas'       => Aula::where('disponible', true)->orderBy('nombre')->get(['id', 'nombre', 'capacidad']),
            'franjas'     => FranjaHoraria::where('activa', true)->orderBy('numero')->get(['id', 'numero', 'hora_inicio', 'hora_fin', 'nombre', 'es_recreo']),
        ]);
    }

    // ── CONFIGURACIÓN GLOBAL DEL MÓDULO ──────────────────────────────────
    public function configuracion()
    {
        $config = [
            'tipo_institucion'      => ConfigInstitucional::get('tipo_institucion', 'publico'),
            'horario_dias'          => ConfigInstitucional::get('horario_dias', ['lunes','martes','miercoles','jueves','viernes']),
            'max_horas_dia_docente' => ConfigInstitucional::get('max_horas_dia_docente', 6),
            'max_horas_dia_grupo'   => ConfigInstitucional::get('max_horas_dia_grupo', 8),
            'duracion_bloque'       => ConfigInstitucional::get('duracion_bloque', 45),
            'max_misma_materia_dia' => ConfigInstitucional::get('max_misma_materia_dia', 1),
            'modulo_pagos_activo'   => ConfigInstitucional::get('modulo_pagos_activo', false),
        ];

        return view('admin.horarios.configuracion', compact('config'));
    }

    public function configuracionGuardar(Request $request)
    {
        $request->validate([
            'tipo_institucion'      => 'required|in:publico,privado',
            'horario_dias'          => 'required|array|min:1',
            'horario_dias.*'        => 'in:lunes,martes,miercoles,jueves,viernes,sabado',
            'max_horas_dia_docente' => 'required|integer|min:1|max:10',
            'max_horas_dia_grupo'   => 'required|integer|min:1|max:12',
            'duracion_bloque'       => 'required|integer|min:20|max:120',
            'max_misma_materia_dia' => 'required|integer|min:1|max:4',
        ]);

        ConfigInstitucional::set('tipo_institucion',      $request->tipo_institucion);
        ConfigInstitucional::set('horario_dias',          $request->horario_dias);
        ConfigInstitucional::set('max_horas_dia_docente', (string) $request->max_horas_dia_docente);
        ConfigInstitucional::set('max_horas_dia_grupo',   (string) $request->max_horas_dia_grupo);
        ConfigInstitucional::set('duracion_bloque',       (string) $request->duracion_bloque);
        ConfigInstitucional::set('max_misma_materia_dia', (string) $request->max_misma_materia_dia);
        ConfigInstitucional::set('modulo_pagos_activo',   $request->boolean('modulo_pagos_activo') ? '1' : '0');

        return redirect()->route('admin.horarios.configuracion')
            ->with('success', 'Configuración guardada correctamente. Los cambios se aplicarán en la próxima generación.');
    }

    // ── LIMPIAR HORARIO: elimina todas las celdas sin borrar el registro ──
    public function limpiar(Horario $horario)
    {
        $count = $horario->detalles()->count();
        $horario->detalles()->delete();
        $horario->update(['score' => 0, 'conflictos' => []]);

        return response()->json([
            'success' => true,
            'mensaje' => "Se eliminaron {$count} clase(s) del horario. Puedes regenerarlo o agregar celdas manualmente.",
        ]);
    }

    // ── Reporte de suplencias PDF ─────────────────────────────────────────
    public function suplenciasPdf(Request $request)
    {
        $q = Suplencia::with([
            'docenteOriginal', 'docenteSuplente',
            'detalle.asignacion.grupo.grado',
            'detalle.asignacion.grupo.seccion',
            'detalle.asignacion.asignatura',
            'detalle.franja',
        ])->orderByDesc('fecha');

        if ($request->filled('docente_id'))  $q->where('docente_id', $request->docente_id);
        if ($request->filled('estado'))      $q->where('estado', $request->estado);
        if ($request->filled('fecha_desde')) $q->where('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $q->where('fecha', '<=', $request->fecha_hasta);

        $suplencias = $q->get();
        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.horarios.suplencias_pdf',
            compact('suplencias', 'inst', 'config', 'sy')
        )->setPaper('letter', 'landscape');

        return $pdf->download('suplencias_' . now()->format('Ymd') . '.pdf');
    }

    // ── Reporte de suplencias Excel ───────────────────────────────────────
    public function suplenciasExcel(Request $request)
    {
        $q = Suplencia::with([
            'docenteOriginal', 'docenteSuplente',
            'detalle.asignacion.grupo.grado',
            'detalle.asignacion.asignatura',
        ])->orderByDesc('fecha');

        if ($request->filled('docente_id'))  $q->where('docente_id', $request->docente_id);
        if ($request->filled('estado'))      $q->where('estado', $request->estado);

        $suplencias = $q->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Suplencias');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['#', 'Fecha', 'Docente Original', 'Docente Suplente', 'Asignatura', 'Grupo', 'Estado', 'Motivo'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '1';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($hdrStyle);

        foreach ($suplencias as $i => $s) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $s->fecha ? \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') : '—');
            $sheet->setCellValue("C{$row}", $s->docenteOriginal?->nombre_completo ?? '—');
            $sheet->setCellValue("D{$row}", $s->docenteSuplente?->nombre_completo ?? 'Sin asignar');
            $sheet->setCellValue("E{$row}", $s->detalle?->asignacion?->asignatura?->nombre ?? '—');
            $sheet->setCellValue("F{$row}", $s->detalle?->asignacion?->grupo
                ? ($s->detalle->asignacion->grupo->grado->nombre ?? '') . ' ' . ($s->detalle->asignacion->grupo->seccion->nombre ?? '')
                : '—');
            $sheet->setCellValue("G{$row}", ucfirst($s->estado ?? ''));
            $sheet->setCellValue("H{$row}", $s->motivo ?? '');
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp      = tempnam(sys_get_temp_dir(), 'sup_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'suplencias_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────
    private function generarColores(\Illuminate\Support\Collection $detalles): array
    {
        $palette = [
            '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
            '#ec4899','#06b6d4','#84cc16','#f97316','#6366f1',
            '#14b8a6','#a855f7','#eab308','#64748b','#dc2626',
        ];
        $colores = [];
        $idx = 0;
        foreach ($detalles as $d) {
            $asigId = $d->asignacion?->asignatura_id ?? 0;
            if (! isset($colores[$asigId])) {
                $colores[$asigId] = $palette[$idx % count($palette)];
                $idx++;
            }
        }
        return $colores;
    }
}
