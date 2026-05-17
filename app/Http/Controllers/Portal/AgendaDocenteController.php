<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Tarea;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgendaDocenteController extends Controller
{
    use HasDocenteContext;

    private function resolverAsignacion(int $asignacionId): Asignacion
    {
        $docente    = $this->getDocente();
        $asignacion = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('id', $asignacionId)
            ->where('docente_id', $docente->id)
            ->firstOrFail();

        return $asignacion;
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Asignacion $asignacion)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);

        $tareas = Tarea::where('asignacion_id', $asignacion->id)
            ->orderByDesc('fecha_limite')
            ->get();

        // Conteo de entregas por tarea
        $entregasCounts = EntregaTarea::whereIn('tarea_id', $tareas->pluck('id'))
            ->selectRaw('tarea_id, estado, count(*) as total')
            ->groupBy('tarea_id', 'estado')
            ->get()
            ->groupBy('tarea_id');

        // Total de estudiantes en el grupo
        $schoolYear      = SchoolYear::actual();
        $totalEstudiantes = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        return view('portal.docente.tareas.index', compact(
            'asignacion', 'tareas', 'entregasCounts', 'totalEstudiantes'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create(Asignacion $asignacion)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        $tipos      = Tarea::TIPOS;

        return view('portal.docente.tareas.create', compact('asignacion', 'tipos'));
    }

    public function store(Request $request, Asignacion $asignacion)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);

        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string|max:5000',
            'fecha_limite' => 'required|date',
            'tipo'         => 'required|in:tarea,actividad,proyecto,evaluacion',
            'puntos_valor' => 'nullable|integer|min:1|max:100',
        ]);

        $data['asignacion_id'] = $asignacion->id;
        $tarea = Tarea::create($data);

        // Notificar a los estudiantes del grupo
        $this->notificarEstudiantes($asignacion, $tarea);

        return redirect()
            ->route('portal.docente.tareas.index', $asignacion)
            ->with('success', 'Tarea "' . $tarea->titulo . '" creada correctamente.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $tipos = Tarea::TIPOS;

        return view('portal.docente.tareas.create', compact('asignacion', 'tarea', 'tipos'));
    }

    public function update(Request $request, Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string|max:5000',
            'fecha_limite' => 'required|date',
            'tipo'         => 'required|in:tarea,actividad,proyecto,evaluacion',
            'puntos_valor' => 'nullable|integer|min:1|max:100',
            'activo'       => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $tarea->update($data);

        return redirect()
            ->route('portal.docente.tareas.index', $asignacion)
            ->with('success', 'Tarea actualizada correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $tarea->delete();

        return redirect()
            ->route('portal.docente.tareas.index', $asignacion)
            ->with('success', 'Tarea eliminada.');
    }

    // ── Entregas ──────────────────────────────────────────────────────────

    public function entregas(Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $schoolYear = SchoolYear::actual();

        // Estudiantes activos del grupo con su entrega (si existe)
        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')
            ->get();

        $entregas = EntregaTarea::where('tarea_id', $tarea->id)
            ->get()
            ->keyBy('estudiante_id');

        $esTecnica = ($asignacion->area ?? '') === 'tecnica';
        $periodos  = Periodo::where('school_year_id', $schoolYear?->id)->orderBy('numero')->get();

        return view('portal.docente.tareas.entregas', compact(
            'asignacion', 'tarea', 'matriculas', 'entregas', 'esTecnica', 'periodos'
        ));
    }

    // ── Calificar (PATCH) ─────────────────────────────────────────────────

    public function calificar(Request $request, Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'estado'        => 'required|in:pendiente,entregada,revisada',
            'calificacion'  => 'nullable|numeric|min:0|max:100',
            'notas_docente' => 'nullable|string|max:1000',
        ]);

        // Guardar feedback previo para detectar si cambió
        $entregaPrevia = EntregaTarea::where('tarea_id', $tarea->id)
            ->where('estudiante_id', $data['estudiante_id'])
            ->first();
        $feedbackPrevio = $entregaPrevia?->notas_docente;

        $entrega = EntregaTarea::updateOrCreate(
            [
                'tarea_id'      => $tarea->id,
                'estudiante_id' => $data['estudiante_id'],
            ],
            [
                'estado'        => $data['estado'],
                'calificacion'  => $data['calificacion'] ?? null,
                'notas_docente' => $data['notas_docente'] ?? null,
                'fecha_entrega' => $data['estado'] !== 'pendiente' ? now() : null,
            ]
        );

        // Notificar al estudiante si hay feedback nuevo o actualizado
        $nuevoFeedback = $data['notas_docente'] ?? null;
        if ($nuevoFeedback && $nuevoFeedback !== $feedbackPrevio) {
            $this->notificarFeedback((int) $data['estudiante_id'], $tarea, $asignacion);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'entrega' => $entrega]);
        }

        return back()->with('success', 'Entrega actualizada.');
    }

    // ── Notificaciones ────────────────────────────────────────────────────

    private function notificarFeedback(int $estudianteId, Tarea $tarea, Asignacion $asignacion): void
    {
        try {
            $est = Estudiante::find($estudianteId);
            if (!$est?->user_id) return;

            $tipoLabel   = Tarea::TIPOS[$tarea->tipo] ?? 'Tarea';
            $asignNombre = $asignacion->asignatura?->nombre ?? 'Materia';

            Notificacion::enviarA(
                [$est->user_id],
                'general',
                "Retroalimentación en: {$tarea->titulo}",
                "Tu docente de {$asignNombre} dejó retroalimentación en tu {$tipoLabel}.",
                ['tarea_id' => $tarea->id, 'asignacion_id' => $asignacion->id]
            );
        } catch (\Throwable) {}
    }

    // ── Seguimiento Global ────────────────────────────────────────────────

    public function seguimiento(Asignacion $asignacion)
    {
        $asignacion  = $this->resolverAsignacion($asignacion->id);
        $schoolYear  = SchoolYear::actual();

        $tareas = Tarea::where('asignacion_id', $asignacion->id)
            ->orderByDesc('fecha_limite')
            ->get();

        $totalEstudiantes = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        // Conteo completo por tarea y estado
        $entregasRaw = EntregaTarea::whereIn('tarea_id', $tareas->pluck('id'))
            ->selectRaw('tarea_id, estado, count(*) as total')
            ->groupBy('tarea_id', 'estado')
            ->get()
            ->groupBy('tarea_id');

        $statsMap = [];
        foreach ($tareas as $t) {
            $g = $entregasRaw->get($t->id, collect());
            $entregadas = $g->whereIn('estado', ['entregada', 'revisada'])->sum('total');
            $revisadas  = $g->where('estado', 'revisada')->sum('total');
            $statsMap[$t->id] = [
                'entregadas'  => $entregadas,
                'revisadas'   => $revisadas,
                'pendientes'  => max(0, $totalEstudiantes - $entregadas),
                'pct'         => $totalEstudiantes > 0 ? round($entregadas / $totalEstudiantes * 100) : 0,
            ];
        }

        return view('portal.docente.tareas.seguimiento', compact(
            'asignacion', 'tareas', 'statsMap', 'totalEstudiantes', 'schoolYear'
        ));
    }

    // ── Recordatorio masivo a pendientes ──────────────────────────────────

    public function recordatorio(Request $request, Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $schoolYear = SchoolYear::actual();

        // Estudiantes del grupo
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        // IDs de estudiantes que ya entregaron
        $entregados = EntregaTarea::where('tarea_id', $tarea->id)
            ->whereIn('estado', ['entregada', 'revisada'])
            ->pluck('estudiante_id')
            ->toArray();

        // Pendientes: no están en $entregados
        $pendientes = $matriculas->filter(
            fn($m) => $m->estudiante && !in_array($m->estudiante->id, $entregados)
        );

        $userIds = $pendientes
            ->map(fn($m) => $m->estudiante?->user_id)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($userIds)) {
            $tipoLabel   = Tarea::TIPOS[$tarea->tipo] ?? 'Tarea';
            $asignNombre = $asignacion->asignatura?->nombre ?? 'Materia';
            $limite      = $tarea->fecha_limite->format('d/m/Y');

            Notificacion::enviarA(
                $userIds,
                'general',
                "Recordatorio: {$tarea->titulo}",
                "{$asignNombre} — Debes entregar tu {$tipoLabel} antes del {$limite}.",
                ['tarea_id' => $tarea->id, 'asignacion_id' => $asignacion->id]
            );
        }

        return response()->json([
            'ok'       => true,
            'enviados' => count($userIds),
            'mensaje'  => count($userIds)
                ? 'Recordatorio enviado a ' . count($userIds) . ' estudiante(s).'
                : 'No hay estudiantes pendientes.',
        ]);
    }

    // ── Entregas PDF ──────────────────────────────────────────────────────
    public function entregasPdf(Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $schoolYear = SchoolYear::actual();
        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $entregas     = EntregaTarea::where('tarea_id', $tarea->id)->get()->keyBy('estudiante_id');
        $nTotal       = $matriculas->count();
        $nEntregadas  = $entregas->whereIn('estado', ['entregada', 'revisada'])->count();
        $nRevisadas   = $entregas->where('estado', 'revisada')->count();
        $nPendientes  = $nTotal - $nEntregadas;
        $nConFeedback = $entregas->filter(fn($e) => !empty($e->notas_docente))->count();

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        $pdf = Pdf::loadView('portal.docente.tareas.entregas_pdf', compact(
            'asignacion', 'tarea', 'matriculas', 'entregas',
            'nTotal', 'nEntregadas', 'nRevisadas', 'nPendientes', 'nConFeedback', 'tenant'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('entregas_' . Str::slug($tarea->titulo) . '.pdf');
    }

    // ── Entregas CSV ──────────────────────────────────────────────────────
    public function entregasCsv(Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $schoolYear = SchoolYear::actual();
        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $entregas = EntregaTarea::where('tarea_id', $tarea->id)->get()->keyBy('estudiante_id');
        $puntos   = max(1, (int) ($tarea->puntos_valor ?? 100));
        $filename = 'entregas_' . Str::slug($tarea->titulo) . '.csv';

        return response()->streamDownload(function () use ($matriculas, $entregas, $puntos) {
            $fh = fopen('php://output', 'w');
            fputs($fh, "\xEF\xBB\xBF");
            fputcsv($fh, ['#', 'Estudiante', 'Cédula', 'Estado', 'Nota', 'Sobre ' . $puntos, 'Fecha Entrega', 'Retroalimentación']);
            $i = 1;
            foreach ($matriculas as $m) {
                $est     = $m->estudiante;
                $entrega = $est ? ($entregas->get($est->id) ?? null) : null;
                $nota    = $entrega?->calificacion;
                fputcsv($fh, [
                    $i++,
                    $est?->nombre_completo ?? 'Sin nombre',
                    $est?->cedula ?? '',
                    $entrega?->estado ?? 'pendiente',
                    $nota !== null ? number_format($nota, 2) : '',
                    $puntos,
                    $entrega?->fecha_entrega?->format('d/m/Y') ?? '',
                    $entrega?->notas_docente ?? '',
                ]);
            }
            fclose($fh);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Pasar nota a calificaciones ───────────────────────────────────────
    public function pasarACalificaciones(Request $request, Asignacion $asignacion, Tarea $tarea)
    {
        $asignacion = $this->resolverAsignacion($asignacion->id);
        abort_if($tarea->asignacion_id !== $asignacion->id, 404);

        $esTecnica  = ($asignacion->area ?? '') === 'tecnica';
        $schoolYear = SchoolYear::actual();

        if ($esTecnica) {
            $data = $request->validate([
                'periodo_id' => 'required|integer|exists:periodos,id',
                'campo'      => 'required|in:tareas,practicas,participacion,proyecto,examen',
            ]);
        } else {
            $data = $request->validate([
                'componente'  => 'required|integer|in:1,2,3,4',
                'periodo_num' => 'required|integer|in:1,2,3,4',
            ]);
        }

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $entregas = EntregaTarea::where('tarea_id', $tarea->id)
            ->whereNotNull('calificacion')
            ->get()->keyBy('estudiante_id');

        $puntos       = max(1, (int) ($tarea->puntos_valor ?? 100));
        $actualizados = 0;

        foreach ($matriculas as $m) {
            $est     = $m->estudiante;
            $entrega = $est ? ($entregas->get($est->id) ?? null) : null;
            if (!$entrega) continue;

            $nota = max(0, min(100, round($entrega->calificacion / $puntos * 100, 2)));

            if ($esTecnica) {
                Calificacion::updateOrCreate(
                    ['matricula_id' => $m->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $data['periodo_id']],
                    [$data['campo'] => $nota]
                );
            } else {
                $campo = "comp{$data['componente']}_p{$data['periodo_num']}";
                $row   = CalificacionAcademica::firstOrNew([
                    'matricula_id'   => $m->id,
                    'asignacion_id'  => $asignacion->id,
                    'school_year_id' => $schoolYear?->id,
                ]);
                $row->$campo = $nota;
                $row->save();
                if (method_exists($row, 'recalcularPromedios')) {
                    $row->recalcularPromedios();
                }
            }
            $actualizados++;
        }

        return response()->json([
            'ok'          => true,
            'actualizados'=> $actualizados,
            'mensaje'     => "{$actualizados} calificación(es) guardada(s) correctamente.",
        ]);
    }

    private function notificarEstudiantes(Asignacion $asignacion, Tarea $tarea): void
    {
        $schoolYear = SchoolYear::actual();

        $estudiantes = Matricula::with('estudiante.user')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->map(fn($m) => $m->estudiante)
            ->filter()
            ->filter(fn($e) => $e->user_id);

        $userIds = $estudiantes->pluck('user_id')->filter()->unique()->values()->toArray();

        if (empty($userIds)) {
            return;
        }

        $tipoLabel  = Tarea::TIPOS[$tarea->tipo] ?? 'Tarea';
        $asignNombre = $asignacion->asignatura?->nombre ?? 'Materia';
        $limite      = $tarea->fecha_limite->format('d/m/Y');

        Notificacion::enviarA(
            $userIds,
            'general',
            "Nueva {$tipoLabel}: {$tarea->titulo}",
            "{$asignNombre} — Fecha límite: {$limite}",
            ['tarea_id' => $tarea->id, 'asignacion_id' => $asignacion->id]
        );
    }
}
