<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use App\Models\Tarea;
use Illuminate\Http\Request;

class AgendaDocenteController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function getDocente(): Docente
    {
        $docente = Docente::where('user_id', auth()->id())->first();

        if (! $docente) {
            abort(403, 'No tienes un perfil de docente asociado a esta cuenta.');
        }

        return $docente;
    }

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

        return view('portal.docente.tareas.entregas', compact(
            'asignacion', 'tarea', 'matriculas', 'entregas'
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

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'entrega' => $entrega]);
        }

        return back()->with('success', 'Entrega actualizada.');
    }

    // ── Notificaciones ────────────────────────────────────────────────────

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
