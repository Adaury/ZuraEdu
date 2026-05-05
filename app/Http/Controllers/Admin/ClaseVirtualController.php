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

        // Stats globales del año
        $statsGlobal = [
            'total_activas'     => ClaseVirtual::whereHas('asignacion',
                fn($q) => $q->where('school_year_id', $schoolYear?->id))->where('activo', true)->count(),
            'total_entregas'    => EntregaClassroom::whereHas('material.claseVirtual.asignacion',
                fn($q) => $q->where('school_year_id', $schoolYear?->id))
                ->whereIn('estado', ['entregado','calificado'])->count(),
            'por_calificar'     => EntregaClassroom::whereHas('material.claseVirtual.asignacion',
                fn($q) => $q->where('school_year_id', $schoolYear?->id))
                ->where('estado', 'entregado')->count(),
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

        $stats = [
            'total_materiales' => $materiales->count(),
            'total_tareas'     => $totalTareas,
            'total_entregas'   => EntregaClassroom::whereIn('material_id', $tareasEvals->pluck('id'))
                                    ->whereIn('estado', ['entregado','calificado','atrasado'])->count(),
            'total_calificados'=> EntregaClassroom::whereIn('material_id', $tareasEvals->pluck('id'))
                                    ->where('estado','calificado')->count(),
            'promedio_notas'   => EntregaClassroom::whereIn('material_id', $tareasEvals->pluck('id'))
                                    ->where('estado','calificado')->whereNotNull('calificacion')->avg('calificacion'),
        ];

        // Progreso por estudiante
        $progresoEstudiantes = $matriculas->map(function($mat) use ($tareasEvals) {
            $entregasAlumno = EntregaClassroom::where('matricula_id', $mat->id)
                ->whereIn('material_id', $tareasEvals->pluck('id'))
                ->get();

            $calificadas = $entregasAlumno->where('estado', 'calificado');
            $promedio    = $calificadas->whereNotNull('calificacion')->avg('calificacion');

            return [
                'matricula'   => $mat,
                'entregadas'  => $entregasAlumno->count(),
                'calificadas' => $calificadas->count(),
                'pendientes'  => $tareasEvals->count() - $entregasAlumno->count(),
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
