<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\ClaseVirtual;
use App\Models\EntregaClassroom;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ClaseVirtualController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $query = ClaseVirtual::with([
                'asignacion.asignatura',
                'asignacion.grupo',
                'asignacion.docente.user',
            ])
            ->withCount('materiales')
            ->when($schoolYear, fn($q) => $q->whereHas(
                'asignacion', fn($s) => $s->where('school_year_id', $schoolYear->id)
            ));

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($s) =>
                $s->where('nombre', 'like', "%{$q}%")
                  ->orWhereHas('asignacion.asignatura', fn($a) => $a->where('nombre', 'like', "%{$q}%"))
                  ->orWhereHas('asignacion.docente.user', fn($a) => $a->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $clases = $query->latest()->paginate(20)->withQueryString();

        // Stats globales del año — 2 queries en lugar de 3
        $syId = $schoolYear?->id;
        $entregaAgg = EntregaClassroom::whereHas('material.claseVirtual.asignacion',
                fn($q) => $q->where('school_year_id', $syId))
            ->whereIn('estado', ['entregado', 'calificado'])
            ->selectRaw("COUNT(*) as total, SUM(estado = 'entregado') as por_calificar")
            ->first();

        $statsGlobal = [
            'total_activas'  => ClaseVirtual::whereHas('asignacion',
                fn($q) => $q->where('school_year_id', $syId))->where('activo', true)->count(),
            'total_entregas' => $entregaAgg->total       ?? 0,
            'por_calificar'  => $entregaAgg->por_calificar ?? 0,
        ];

        return view('admin.classroom.index', compact('clases', 'schoolYear', 'statsGlobal'));
    }

    public function create()
    {
        $schoolYear  = SchoolYear::actual();
        $asignaciones = Asignacion::with(['asignatura', 'grupo', 'docente'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('activo', true)
            ->orderBy('asignatura_id')
            ->get();

        return view('admin.classroom.create', compact('asignaciones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asignacion_id'       => 'required|exists:asignaciones,id',
            'nombre'              => 'required|string|max:150',
            'descripcion'         => 'nullable|string|max:500',
            'portada_color'       => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'activo'              => 'boolean',
            'permite_comentarios' => 'boolean',
        ]);

        $data['activo']              = $request->boolean('activo', true);
        $data['permite_comentarios'] = $request->boolean('permite_comentarios', true);

        ClaseVirtual::create($data);

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual creada correctamente.');
    }

    public function show(ClaseVirtual $claseVirtual)
    {
        $claseVirtual->load([
            'asignacion.asignatura',
            'asignacion.grupo',
            'asignacion.docente',
        ]);

        $tab = request('tab', 'materiales');

        $materiales = $claseVirtual->materiales()
            ->with(['archivos', 'entregas.matricula.estudiante', 'periodo', 'quiz'])
            ->orderByDesc('created_at')
            ->get();

        // Estudiantes del grupo
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')
            ->get();

        $totalEstudiantes = $matriculas->count();

        // Estadísticas globales de la clase
        $tareasEvals = $materiales->whereIn('tipo', ['tarea', 'evaluacion']);
        $totalTareas = $tareasEvals->count();

        $tareaIds = $tareasEvals->pluck('id');
        $entregaAgg = EntregaClassroom::whereIn('material_id', $tareaIds)
            ->selectRaw("
                COUNT(CASE WHEN estado IN ('entregado','calificado','atrasado') THEN 1 END) as total_entregas,
                COUNT(CASE WHEN estado = 'calificado' THEN 1 END)                           as total_calificados,
                AVG(CASE WHEN estado = 'calificado' AND calificacion IS NOT NULL THEN calificacion END) as promedio_notas
            ")->first();

        $stats = [
            'total_materiales'  => $materiales->count(),
            'total_tareas'      => $totalTareas,
            'total_entregas'    => $entregaAgg->total_entregas    ?? 0,
            'total_calificados' => $entregaAgg->total_calificados ?? 0,
            'promedio_notas'    => $entregaAgg->promedio_notas !== null ? round($entregaAgg->promedio_notas, 1) : null,
        ];

        // Progreso por estudiante — 1 query para todos en lugar de N queries
        $todasEntregas = EntregaClassroom::whereIn('matricula_id', $matriculas->pluck('id'))
            ->whereIn('material_id', $tareaIds)
            ->get()
            ->groupBy('matricula_id');

        $totalTareasCount = $tareasEvals->count();
        $progresoEstudiantes = $matriculas->map(function($mat) use ($todasEntregas, $totalTareasCount) {
            $entregasAlumno = $todasEntregas->get($mat->id, collect());
            $calificadas    = $entregasAlumno->where('estado', 'calificado');
            $promedio       = $calificadas->whereNotNull('calificacion')->avg('calificacion');

            return [
                'matricula'   => $mat,
                'entregadas'  => $entregasAlumno->count(),
                'calificadas' => $calificadas->count(),
                'pendientes'  => $totalTareasCount - $entregasAlumno->count(),
                'promedio'    => $promedio ? round($promedio, 1) : null,
            ];
        })->sortByDesc('entregadas');

        return view('admin.classroom.show', compact(
            'claseVirtual', 'materiales', 'tab',
            'matriculas', 'totalEstudiantes', 'stats', 'progresoEstudiantes'
        ));
    }

    public function edit(ClaseVirtual $claseVirtual)
    {
        $schoolYear   = SchoolYear::actual();
        $asignaciones = Asignacion::with(['asignatura', 'grupo', 'docente'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('activo', true)
            ->orderBy('asignatura_id')
            ->get();

        return view('admin.classroom.edit', compact('claseVirtual', 'asignaciones'));
    }

    public function update(Request $request, ClaseVirtual $claseVirtual)
    {
        $data = $request->validate([
            'asignacion_id'       => 'required|exists:asignaciones,id',
            'nombre'              => 'required|string|max:150',
            'descripcion'         => 'nullable|string|max:500',
            'portada_color'       => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'activo'              => 'boolean',
            'permite_comentarios' => 'boolean',
        ]);

        $data['activo']              = $request->boolean('activo');
        $data['permite_comentarios'] = $request->boolean('permite_comentarios');

        $claseVirtual->update($data);

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual actualizada.');
    }

    public function destroy(ClaseVirtual $claseVirtual)
    {
        $claseVirtual->delete();

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual eliminada.');
    }

    public function toggleActivo(ClaseVirtual $claseVirtual)
    {
        $claseVirtual->update(['activo' => ! $claseVirtual->activo]);

        return back()->with('success', $claseVirtual->activo
            ? 'Aula virtual activada.'
            : 'Aula virtual desactivada.');
    }
}
