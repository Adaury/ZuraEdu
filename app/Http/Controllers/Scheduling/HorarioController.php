<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\Scheduling\Asignacion;
use App\Models\Scheduling\Aula;
use App\Models\Scheduling\Curso;
use App\Models\Scheduling\DisponibilidadProfesor;
use App\Models\Scheduling\Franja;
use App\Models\Scheduling\Horario;
use App\Models\Scheduling\HorarioDetalle;
use App\Models\Scheduling\Materia;
use App\Models\Scheduling\Profesor;
use App\Services\Scheduling\HorarioGeneratorService;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    // ── INDEX ─────────────────────────────────────────────────────────────

    public function index()
    {
        $horarios = Horario::latest()->get();
        return view('scheduling.horarios.index', compact('horarios'));
    }

    // ── SHOW ──────────────────────────────────────────────────────────────

    public function show(Horario $horario, Request $request)
    {
        $cursoId = $request->input('curso_id');

        $detalles = HorarioDetalle::with([
                'asignacion.materia',
                'asignacion.profesor',
                'asignacion.curso',
                'aula',
                'franja',
            ])
            ->where('horario_id', $horario->id)
            ->when($cursoId, fn($q) => $q->whereHas('asignacion', fn($q2) => $q2->where('curso_id', $cursoId)))
            ->get();

        $franjas = Franja::where('activa', true)->orderBy('numero')->get();
        $cursos  = Curso::orderBy('grado')->get();

        // Grid: [franja_id][dia] = detalle
        $grid = [];
        foreach ($detalles as $d) {
            $grid[$d->franja_id][$d->dia] = $d;
        }

        return view('scheduling.horarios.show', compact(
            'horario', 'franjas', 'cursos', 'grid', 'cursoId'
        ));
    }

    // ── GENERAR ───────────────────────────────────────────────────────────

    public function generar(Request $request)
    {
        $request->validate(['nombre' => 'nullable|string|max:100']);

        $service = new HorarioGeneratorService();
        $result  = $service->generar(
            $request->input('nombre', 'Horario ' . now()->format('d/m/Y H:i'))
        );

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $msg = $result['pendientes'] === 0
            ? "Horario generado sin conflictos — {$result['asignados']} clases. Score: {$result['score']}%"
            : "Horario generado: {$result['asignados']} asignadas, {$result['pendientes']} sin resolver. Score: {$result['score']}%.";

        if (!empty($result['conflictos'])) {
            session()->flash('conflictos', $result['conflictos']);
        }

        return redirect()
            ->route('scheduling.horarios.show', $result['horario_id'])
            ->with($result['pendientes'] === 0 ? 'success' : 'warning', $msg);
    }

    // ── PUBLICAR ──────────────────────────────────────────────────────────

    public function publicar(Horario $horario)
    {
        $nuevo = $horario->estado === 'publicado' ? 'borrador' : 'publicado';
        $horario->update(['estado' => $nuevo]);
        return back()->with('success', "Horario " . ($nuevo === 'publicado' ? 'publicado' : 'vuelto a borrador') . ".");
    }

    // ── ELIMINAR ──────────────────────────────────────────────────────────

    public function destroy(Horario $horario)
    {
        $horario->delete();
        return redirect()->route('scheduling.horarios.index')->with('success', 'Horario eliminado.');
    }

    // ── CONFIGURACIÓN ─────────────────────────────────────────────────────

    public function configuracion()
    {
        return view('scheduling.configuracion', [
            'cursos'      => Curso::orderBy('grado')->get(),
            'materias'    => Materia::orderBy('nombre')->get(),
            'profesores'  => Profesor::orderBy('apellidos')->get(),
            'aulas'       => Aula::orderBy('nombre')->get(),
            'franjas'     => Franja::orderBy('numero')->get(),
            'asignaciones'=> Asignacion::with(['materia','profesor','curso'])->get(),
        ]);
    }

    // ── CRUD CURSOS ───────────────────────────────────────────────────────

    public function cursoStore(Request $request)
    {
        Curso::create($request->validate([
            'nombre'    => 'required|string|max:80',
            'grado'     => 'required|string|max:40',
            'seccion'   => 'nullable|string|max:10',
            'capacidad' => 'required|integer|min:1|max:60',
        ]));
        return back()->with('success', 'Curso registrado.');
    }

    public function cursoDestroy(Curso $curso)
    {
        $curso->delete();
        return back()->with('success', 'Curso eliminado.');
    }

    // ── CRUD MATERIAS ─────────────────────────────────────────────────────

    public function materiaStore(Request $request)
    {
        Materia::create($request->validate([
            'nombre'       => 'required|string|max:100',
            'horas_semana' => 'required|integer|min:1|max:10',
            'color'        => 'nullable|string|max:7',
        ]));
        return back()->with('success', 'Materia registrada.');
    }

    public function materiaDestroy(Materia $materia)
    {
        $materia->delete();
        return back()->with('success', 'Materia eliminada.');
    }

    // ── CRUD PROFESORES ───────────────────────────────────────────────────

    public function profesorStore(Request $request)
    {
        Profesor::create($request->validate([
            'nombre'       => 'required|string|max:80',
            'apellidos'    => 'required|string|max:80',
            'email'        => 'nullable|email|max:120|unique:sch_profesores,email',
            'especialidad' => 'nullable|string|max:100',
        ]));
        return back()->with('success', 'Profesor registrado.');
    }

    public function profesorDestroy(Profesor $profesor)
    {
        $profesor->delete();
        return back()->with('success', 'Profesor eliminado.');
    }

    // ── CRUD AULAS ────────────────────────────────────────────────────────

    public function aulaStore(Request $request)
    {
        Aula::create($request->validate([
            'nombre'     => 'required|string|max:80',
            'capacidad'  => 'required|integer|min:1',
            'tipo'       => 'required|in:aula,laboratorio,taller,gimnasio',
            'disponible' => 'boolean',
        ]) + ['disponible' => $request->boolean('disponible', true)]);
        return back()->with('success', 'Aula registrada.');
    }

    public function aulaDestroy(Aula $aula)
    {
        $aula->delete();
        return back()->with('success', 'Aula eliminada.');
    }

    // ── CRUD FRANJAS ──────────────────────────────────────────────────────

    public function franjaStore(Request $request)
    {
        Franja::create($request->validate([
            'numero'      => 'required|integer|min:1',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'nombre'      => 'nullable|string|max:50',
            'es_recreo'   => 'boolean',
        ]) + ['es_recreo' => $request->boolean('es_recreo', false)]);
        return back()->with('success', 'Franja agregada.');
    }

    public function franjaDestroy(Franja $franja)
    {
        $franja->delete();
        return back()->with('success', 'Franja eliminada.');
    }

    // ── CRUD ASIGNACIONES ─────────────────────────────────────────────────

    public function asignacionStore(Request $request)
    {
        Asignacion::firstOrCreate(
            [
                'materia_id'  => $request->validate(['materia_id'  => 'required|exists:sch_materias,id'])['materia_id'],
                'profesor_id' => $request->validate(['profesor_id' => 'required|exists:sch_profesores,id'])['profesor_id'],
                'curso_id'    => $request->validate(['curso_id'    => 'required|exists:sch_cursos,id'])['curso_id'],
            ],
            ['horas_semana' => $request->validate(['horas_semana' => 'required|integer|min:1|max:10'])['horas_semana']]
        );
        return back()->with('success', 'Asignación guardada.');
    }

    public function asignacionDestroy(Asignacion $asignacion)
    {
        $asignacion->delete();
        return back()->with('success', 'Asignación eliminada.');
    }

    // ── DISPONIBILIDAD ────────────────────────────────────────────────────

    public function disponibilidadGuardar(Request $request)
    {
        $request->validate(['profesor_id' => 'required|exists:sch_profesores,id']);
        $pId    = $request->profesor_id;
        $franjas = Franja::where('activa', true)->pluck('id');
        $dias   = ['lunes','martes','miercoles','jueves','viernes'];

        DisponibilidadProfesor::where('profesor_id', $pId)->delete();

        foreach ($dias as $dia) {
            foreach ($franjas as $fId) {
                DisponibilidadProfesor::create([
                    'profesor_id' => $pId,
                    'franja_id'   => $fId,
                    'dia'         => $dia,
                    'disponible'  => $request->has("disp.{$dia}.{$fId}"),
                ]);
            }
        }

        return back()->with('success', 'Disponibilidad guardada.');
    }
}
