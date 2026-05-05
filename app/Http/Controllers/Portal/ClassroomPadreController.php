<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClaseVirtual;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ClassroomPadreController extends Controller
{
    private function getMatriculaHijo(Estudiante $estudiante): Matricula
    {
        // Verificar que el padre tiene acceso a este estudiante
        $repId = auth()->user()->representante?->id;
        abort_unless(
            $repId && $estudiante->representantes()->where('representante_id', $repId)->exists(),
            403, 'No tiene acceso a este estudiante.'
        );

        $schoolYear = SchoolYear::actual();
        $matricula  = Matricula::where('estudiante_id', $estudiante->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        abort_unless($matricula, 404, 'El estudiante no tiene matrícula activa.');
        return $matricula;
    }

    /** Lista de clases virtuales del hijo */
    public function index(Estudiante $estudiante)
    {
        $matricula = $this->getMatriculaHijo($estudiante);

        $clases = ClaseVirtual::with(['asignacion.asignatura', 'asignacion.docente'])
            ->whereHas('asignacion', fn($q) => $q
                ->where('grupo_id', $matricula->grupo_id)
                ->where('activo', true)
            )
            ->where('activo', true)
            ->latest()
            ->get();

        // Para cada clase, contar tareas pendientes del hijo
        $clases->each(function ($clase) use ($matricula) {
            $tareas = $clase->materiales()->whereIn('tipo', ['tarea','evaluacion'])->pluck('id');
            $entregadas = EntregaClassroom::where('matricula_id', $matricula->id)
                ->whereIn('material_id', $tareas)->count();
            $clase->_tareas_total   = $tareas->count();
            $clase->_tareas_entregadas = $entregadas;
            $clase->_pendientes     = max(0, $tareas->count() - $entregadas);
        });

        return view('portal.classroom.padre.index', compact('clases', 'estudiante', 'matricula'));
    }

    /** Detalle de una clase virtual del hijo (solo lectura) */
    public function show(Estudiante $estudiante, ClaseVirtual $claseVirtual)
    {
        $matricula = $this->getMatriculaHijo($estudiante);
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.docente']);

        abort_unless(
            $claseVirtual->asignacion->grupo_id === $matricula->grupo_id,
            403, 'Esta clase no corresponde al grupo del estudiante.'
        );

        $materiales = $claseVirtual->materialesPublicados()
            ->with(['archivos', 'comentarios.user'])
            ->get();

        $entregasMap = EntregaClassroom::where('matricula_id', $matricula->id)
            ->whereIn('material_id', $materiales->pluck('id'))
            ->get()
            ->keyBy('material_id');

        return view('portal.classroom.padre.show', compact(
            'claseVirtual', 'materiales', 'matricula', 'entregasMap', 'estudiante'
        ));
    }
}
