<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ArchivoEntrega;
use App\Models\ArchivoMaterial;
use App\Models\Asignacion;
use App\Models\ClaseVirtual;
use App\Models\EntregaClassroom;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\SchoolYear;
use App\Models\ZcRecurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaseVirtualController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $query = ClaseVirtual::with([
                'asignacion.asignatura',
                'asignacion.grupo.grado',
                'asignacion.grupo.seccion',
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
        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente.user'])
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

        // `exists:` no filtra por centro: la asignación debe ser de ESTE tenant (scope BelongsToTenant).
        abort_unless(Asignacion::where('id', $data['asignacion_id'])->exists(), 404);

        $data['activo']              = $request->boolean('activo', true);
        $data['permite_comentarios'] = $request->boolean('permite_comentarios', true);

        $clase = ClaseVirtual::create($data);

        ActivityLog::registrar(
            'classroom.creada',
            ClaseVirtual::class,
            $clase->id,
            "Aula virtual #{$clase->id} «{$clase->nombre}» creada para la asignación #{$clase->asignacion_id}"
        );

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual creada correctamente.');
    }

    public function show(ClaseVirtual $claseVirtual)
    {
        $claseVirtual->load([
            'asignacion.asignatura',
            'asignacion.grupo.grado',
            'asignacion.grupo.seccion',
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
        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])
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

        abort_unless(Asignacion::where('id', $data['asignacion_id'])->exists(), 404);

        // Mover el aula a OTRA asignación con entregas ya hechas reasignaría esas notas: la
        // sincronización escribe en calificaciones usando clase.asignacion_id, así que las notas de
        // los estudiantes del grupo anterior caerían en otra materia/grupo.
        $cambiaAsignacion = (int) $data['asignacion_id'] !== (int) $claseVirtual->asignacion_id;
        if ($cambiaAsignacion
            && EntregaClassroom::whereIn('material_id', $claseVirtual->materiales()->select('id'))->exists()) {
            return back()->withInput()->withErrors([
                'asignacion_id' => 'Esta aula ya tiene entregas: no se puede cambiar su asignación (se perderían las notas). Crea una aula nueva o duplícala.',
            ]);
        }

        $antes = "asignación #{$claseVirtual->asignacion_id}, " . ($claseVirtual->activo ? 'activa' : 'inactiva');

        $data['activo']              = $request->boolean('activo');
        $data['permite_comentarios'] = $request->boolean('permite_comentarios');

        $claseVirtual->update($data);

        ActivityLog::registrar(
            'classroom.actualizada',
            ClaseVirtual::class,
            $claseVirtual->id,
            "Aula virtual #{$claseVirtual->id} «{$claseVirtual->nombre}»: {$antes} → asignación #{$claseVirtual->asignacion_id}, "
                . ($claseVirtual->activo ? 'activa' : 'inactiva')
        );

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual actualizada.');
    }

    public function destroy(ClaseVirtual $claseVirtual)
    {
        // Borrado físico con cascada (materiales, entregas, quizzes, chat): sin SoftDeletes. Se deja
        // constancia de QUÉ se eliminó y se recogen los archivos para no dejarlos huérfanos en disco.
        $materialIds = $claseVirtual->materiales()->pluck('id');
        $entregaIds  = EntregaClassroom::whereIn('material_id', $materialIds)->pluck('id');

        $rutas = ArchivoMaterial::whereIn('material_id', $materialIds)->pluck('ruta')
            ->merge(ArchivoEntrega::whereIn('entrega_id', $entregaIds)->pluck('ruta'))
            ->merge(ZcRecurso::where('clase_virtual_id', $claseVirtual->id)->whereNotNull('ruta_archivo')->pluck('ruta_archivo'))
            ->filter()->unique()->values();

        ActivityLog::registrar(
            'classroom.eliminada',
            ClaseVirtual::class,
            $claseVirtual->id,
            "Aula virtual #{$claseVirtual->id} «{$claseVirtual->nombre}» eliminada | asignación #{$claseVirtual->asignacion_id}"
                . " | {$materialIds->count()} material(es), {$entregaIds->count()} entrega(s), {$rutas->count()} archivo(s)"
        );

        $claseVirtual->delete();

        foreach ($rutas as $ruta) {
            Storage::disk('public')->delete($ruta);
        }

        return redirect()->route('admin.classroom.index')
            ->with('success', 'Aula virtual eliminada.');
    }

    public function toggleActivo(ClaseVirtual $claseVirtual)
    {
        $claseVirtual->update(['activo' => ! $claseVirtual->activo]);

        ActivityLog::registrar(
            'classroom.estado_cambiado',
            ClaseVirtual::class,
            $claseVirtual->id,
            "Aula virtual #{$claseVirtual->id} «{$claseVirtual->nombre}»: " . ($claseVirtual->activo ? 'activada' : 'desactivada')
        );

        return back()->with('success', $claseVirtual->activo
            ? 'Aula virtual activada.'
            : 'Aula virtual desactivada.');
    }
}
