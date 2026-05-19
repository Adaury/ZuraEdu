<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Traits\AsignaMateriasBasicas;
use Illuminate\Http\Request;

class AcademicoController extends Controller
{
    use AsignaMateriasBasicas;

    public function index(Request $request)
    {
        $schoolYears = SchoolYear::orderByDesc('id')->get();
        $schoolYear  = $request->filled('sy')
            ? SchoolYear::find($request->integer('sy'))
            : SchoolYear::actual();
        $schoolYear ??= $schoolYears->first();

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->withCount(['matriculas' => fn($q) => $q->where('estado', 'activa')])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        $asigCount = Asignacion::where('school_year_id', $schoolYear?->id)
            ->whereIn('grupo_id', $grupos->pluck('id'))
            ->where('activo', true)
            ->selectRaw('grupo_id, count(*) as total')
            ->groupBy('grupo_id')
            ->pluck('total', 'grupo_id');

        $docenteCount = Asignacion::where('school_year_id', $schoolYear?->id)
            ->whereIn('grupo_id', $grupos->pluck('id'))
            ->whereNotNull('docente_id')
            ->where('activo', true)
            ->selectRaw('grupo_id, count(distinct docente_id) as total')
            ->groupBy('grupo_id')
            ->pluck('total', 'grupo_id');

        $cursos    = $grupos->groupBy(fn($g) => $g->grado->nombre ?? 'Sin Grado');
        $grados    = Grado::orderBy('nivel')->get();
        $secciones = Seccion::orderBy('orden')->get();

        return view('admin.academico.index', compact(
            'schoolYear', 'schoolYears', 'cursos', 'asigCount', 'docenteCount', 'grados', 'secciones'
        ));
    }

    public function show(Grupo $grupo)
    {
        $grupo->load(['grado', 'seccion', 'schoolYear', 'tutor']);

        $asignaciones = Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $grupo->id)
            ->orderByDesc('activo')
            ->orderByRaw('(SELECT nombre FROM asignaturas WHERE id = asignaciones.asignatura_id)')
            ->get();

        $asigIdsTomadas = $asignaciones->pluck('asignatura_id');
        $asignaturasDisponibles = Asignatura::activas()
            ->whereNotIn('id', $asigIdsTomadas)
            ->orderBy('nombre')
            ->get();

        $docentes  = Docente::activos()->with('user')->orderBy('apellidos')->get();
        $tiposEval = [
            'componentes'       => 'Componentes',
            'ra'                => 'Resultados de Aprendizaje',
            'indicadores_logro' => 'Indicadores de Logro',
            'competencias'      => 'Competencias',
        ];

        return view('admin.academico.show', compact(
            'grupo', 'asignaciones', 'asignaturasDisponibles', 'docentes', 'tiposEval'
        ));
    }

    public function storeCurso(Request $request)
    {
        $data = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'grado_id'       => 'required|exists:grados,id',
            'seccion_id'     => 'required|exists:secciones,id',
            'aula'           => 'nullable|string|max:60',
            'capacidad'      => 'nullable|integer|min:1',
        ]);

        if (Grupo::where('school_year_id', $data['school_year_id'])
            ->where('grado_id', $data['grado_id'])
            ->where('seccion_id', $data['seccion_id'])
            ->exists()
        ) {
            return back()->with('error', 'Ya existe ese curso en este año escolar.');
        }

        $data['activo'] = true;
        $grupo = Grupo::create($data);
        $this->asignarMateriasBasicas($grupo->id, $grupo->school_year_id);

        return redirect()->route('admin.academico.show', $grupo)
            ->with('success', 'Curso creado. Se asignaron las materias básicas automáticamente.');
    }

    public function updateCurso(Request $request, Grupo $grupo)
    {
        $request->validate([
            'aula'      => 'nullable|string|max:60',
            'capacidad' => 'nullable|integer|min:1',
            'activo'    => 'nullable|boolean',
        ]);

        $grupo->update([
            'aula'      => $request->input('aula'),
            'capacidad' => $request->integer('capacidad') ?: null,
            'activo'    => $request->boolean('activo'),
        ]);

        return back()->with('success', 'Curso actualizado.');
    }

    public function destroyCurso(Grupo $grupo)
    {
        $grupo->loadCount('matriculas');
        if ($grupo->matriculas_count > 0) {
            return back()->with('error', 'No se puede eliminar el curso porque tiene matrículas activas.');
        }

        $grupo->asignaciones()->delete();
        $grupo->delete();

        return redirect()->route('admin.academico.index')
            ->with('success', 'Curso eliminado.');
    }

    public function storeMateria(Request $request, Grupo $grupo)
    {
        $data = $request->validate([
            'asignatura_id'   => 'required|exists:asignaturas,id',
            'docente_id'      => 'nullable|exists:docentes,id',
            'area'            => 'required|in:academica,tecnica',
            'tipo_evaluacion' => 'required|in:componentes,ra,indicadores_logro,competencias',
            'horas_semana'    => 'nullable|integer|min:1|max:40',
        ]);

        if (Asignacion::where('grupo_id', $grupo->id)
            ->where('asignatura_id', $data['asignatura_id'])
            ->exists()
        ) {
            return back()->with('error', 'Esa materia ya está asignada a este curso.');
        }

        Asignacion::create([
            'school_year_id'  => $grupo->school_year_id,
            'grupo_id'        => $grupo->id,
            'asignatura_id'   => $data['asignatura_id'],
            'docente_id'      => $data['docente_id'] ?: null,
            'area'            => $data['area'],
            'tipo_evaluacion' => $data['tipo_evaluacion'],
            'horas_semana'    => $data['horas_semana'] ?? null,
            'activo'          => true,
        ]);

        return back()->with('success', 'Materia agregada.');
    }

    public function updateMateria(Request $request, Asignacion $asignacion)
    {
        $request->validate([
            'docente_id'      => 'nullable|exists:docentes,id',
            'area'            => 'required|in:academica,tecnica',
            'tipo_evaluacion' => 'required|in:componentes,ra,indicadores_logro,competencias',
            'horas_semana'    => 'nullable|integer|min:1|max:40',
        ]);

        $asignacion->update([
            'docente_id'      => $request->input('docente_id') ?: null,
            'area'            => $request->input('area'),
            'tipo_evaluacion' => $request->input('tipo_evaluacion'),
            'horas_semana'    => $request->integer('horas_semana') ?: null,
        ]);

        return back()->with('success', 'Materia actualizada.');
    }

    public function toggleMateria(Asignacion $asignacion)
    {
        $asignacion->update(['activo' => !$asignacion->activo]);
        $label = $asignacion->fresh()->activo ? 'activada' : 'desactivada';

        return back()->with('success', "Materia {$label}.");
    }

    public function destroyMateria(Asignacion $asignacion)
    {
        $asignacion->delete();

        return back()->with('success', 'Materia eliminada.');
    }
}
