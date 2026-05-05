<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ArchivoEntrega;
use App\Models\ClaseVirtual;
use App\Models\ComentarioClassroom;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\MaterialClase;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\ZcRecurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClassroomEstudianteController extends Controller
{
    /** Obtiene la matrícula activa del estudiante autenticado (o null si no tiene) */
    private function getMatriculaOrNull(): ?Matricula
    {
        $estudiante = Estudiante::where('user_id', auth()->id())->first();
        if (! $estudiante) return null;

        $schoolYear = SchoolYear::actual();
        return Matricula::where('estudiante_id', $estudiante->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();
    }

    /** Obtiene la matrícula activa del estudiante autenticado */
    private function getMatricula(): Matricula
    {
        $matricula = $this->getMatriculaOrNull();
        abort_unless($matricula, 403, 'No tiene matrícula activa.');
        return $matricula;
    }

    /** Verifica que el estudiante (matricula) pertenece al grupo de la clase */
    private function autorizarClase(ClaseVirtual $clase, Matricula $matricula): void
    {
        abort_unless(
            $clase->asignacion->grupo_id === $matricula->grupo_id,
            403,
            'No está matriculado en esta asignatura.'
        );
    }

    // ── index ──────────────────────────────────────────────────────────────
    public function index()
    {
        $matricula = $this->getMatriculaOrNull();

        if (! $matricula) {
            return view('portal.classroom.estudiante.index', [
                'clases'    => collect(),
                'matricula' => null,
                'sinMatricula' => true,
            ]);
        }

        $clases = ClaseVirtual::with(['asignacion.asignatura', 'asignacion.docente', 'materiales'])
            ->whereHas('asignacion', function ($q) use ($matricula) {
                $q->where('grupo_id', $matricula->grupo_id)
                  ->where('school_year_id', $matricula->school_year_id)
                  ->where('activo', true);
            })
            ->where('activo', true)
            ->latest()
            ->get();

        return view('portal.classroom.estudiante.index', compact('clases', 'matricula'));
    }

    // ── show ───────────────────────────────────────────────────────────────
    public function show(ClaseVirtual $claseVirtual)
    {
        $matricula = $this->getMatricula();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.docente', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $matricula);

        $materiales = $claseVirtual->materialesPublicados()
            ->with(['archivos', 'comentarios.user', 'rubric.criterios', 'periodo'])
            ->get();

        $entregasMap = EntregaClassroom::where('matricula_id', $matricula->id)
            ->whereIn('material_id', $materiales->pluck('id'))
            ->with(['archivos', 'rubricCalificaciones.criterio'])
            ->get()
            ->keyBy('material_id');

        $recursos = ZcRecurso::where('clase_virtual_id', $claseVirtual->id)
            ->where('publico', true)->orderBy('orden')->get();

        return view('portal.classroom.estudiante.show', compact(
            'claseVirtual', 'materiales', 'matricula', 'entregasMap', 'recursos'
        ));
    }

    // ── tareasPendientes (dashboard) ───────────────────────────────────────
    public function tareasPendientes()
    {
        $matricula = $this->getMatricula();

        $clases = ClaseVirtual::whereHas('asignacion', fn($q) =>
            $q->where('grupo_id', $matricula->grupo_id)
              ->where('school_year_id', $matricula->school_year_id)
              ->where('activo', true)
        )->where('activo', true)->get();

        $pendientes = collect();
        foreach ($clases as $clase) {
            $mats = $clase->materialesPublicados()
                ->whereIn('tipo', ['tarea', 'evaluacion'])
                ->whereDoesntHave('entregas', fn($q) => $q->where('matricula_id', $matricula->id)
                    ->whereIn('estado', ['entregado', 'calificado']))
                ->with(['claseVirtual.asignacion.asignatura'])
                ->get();
            $pendientes = $pendientes->merge($mats);
        }

        return response()->json([
            'count'   => $pendientes->count(),
            'proximas'=> $pendientes->filter(fn($m) => $m->fecha_limite && !$m->estaVencido())
                ->sortBy('fecha_limite')->take(5)->values(),
            'vencidas'=> $pendientes->filter(fn($m) => $m->estaVencido())->count(),
        ]);
    }

    // ── entregarTarea ──────────────────────────────────────────────────────
    public function entregarTarea(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $matricula = $this->getMatricula();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $matricula);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($material->esTareaOEvaluacion(), 422, 'Solo se pueden entregar tareas o evaluaciones.');

        $data = $request->validate([
            'contenido'    => 'nullable|string|max:5000',
            'url_entrega'  => 'nullable|url|max:500',
            'archivos.*'   => 'nullable|file|max:20480',
        ]);

        // Verificar si ya entregó y si puede reenviar
        $entregaExistente = EntregaClassroom::where('material_id', $material->id)
            ->where('matricula_id', $matricula->id)->first();

        if ($entregaExistente && !$material->permite_reentrega
            && in_array($entregaExistente->estado, ['entregado', 'calificado'])) {
            return back()->withErrors(['error' => 'No se permiten reenvíos para esta tarea.']);
        }

        $esAtrasada = $material->fecha_limite && $material->fecha_limite->isPast();

        $entrega = EntregaClassroom::updateOrCreate(
            ['material_id' => $material->id, 'matricula_id' => $matricula->id],
            [
                'contenido'    => $data['contenido'] ?? null,
                'url_entrega'  => $data['url_entrega'] ?? null,
                'estado'       => $esAtrasada ? 'atrasado' : 'entregado',
                'fecha_entrega'=> now(),
                'intentos'     => ($entregaExistente?->intentos ?? 0) + 1,
            ]
        );

        // Subir archivos de entrega
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                $ruta = $file->store("entregas/{$claseVirtual->id}/{$matricula->id}", 'public');
                ArchivoEntrega::create([
                    'entrega_id'     => $entrega->id,
                    'nombre_original'=> $file->getClientOriginalName(),
                    'ruta'           => $ruta,
                    'tipo_mime'      => $file->getMimeType(),
                    'tamanio'        => $file->getSize(),
                ]);
            }
        }

        return back()->with('success', $esAtrasada
            ? 'Tarea entregada (con retraso).'
            : 'Tarea entregada correctamente.');
    }

    // ── comentar ───────────────────────────────────────────────────────────
    public function comentar(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $matricula = $this->getMatricula();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $matricula);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($claseVirtual->permite_comentarios, 403, 'Los comentarios están desactivados en esta aula.');

        $request->validate(['contenido' => 'required|string|max:1000']);

        ComentarioClassroom::create([
            'material_id' => $material->id,
            'user_id'     => auth()->id(),
            'contenido'   => $request->contenido,
        ]);

        return back()->with('success', 'Comentario agregado.');
    }
}
