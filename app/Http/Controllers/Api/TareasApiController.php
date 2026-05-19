<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Estudiante;
use App\Models\EntregaTarea;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Tarea;
use Illuminate\Http\Request;

class TareasApiController extends Controller
{
    /** GET /api/v1/tareas — para Estudiante */
    public function index(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        return response()->json($this->buildTareas($estudiante));
    }

    /** GET /api/v1/tareas/hijo/{estudiante} — para Representante */
    public function hijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return response()->json($this->buildTareas($estudiante));
    }

    private function buildTareas(Estudiante $estudiante): array
    {
        $sy       = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) {
            return ['estudiante' => $estudiante->nombre_completo, 'tareas' => []];
        }

        $asignacionIds = Asignacion::where('grupo_id', $matricula->grupo_id)
            ->where('activo', true)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->pluck('id');

        $tareas = Tarea::with('asignacion.asignatura')
            ->whereIn('asignacion_id', $asignacionIds)
            ->where('activo', true)
            ->orderBy('fecha_limite')
            ->get();

        $entregas = EntregaTarea::where('estudiante_id', $estudiante->id)
            ->whereIn('tarea_id', $tareas->pluck('id'))
            ->get()
            ->keyBy('tarea_id');

        $now = now();

        $tareasMapped = $tareas->map(function (Tarea $t) use ($entregas, $now) {
            $entrega = $entregas->get($t->id);
            $vencida = $t->fecha_limite->isPast();

            $estado = match(true) {
                $entrega && in_array($entrega->estado, ['entregada', 'revisada']) => $entrega->estado,
                $vencida => 'vencida',
                default  => 'pendiente',
            };

            $diasRestantes = null;
            if ($estado === 'pendiente') {
                $diasRestantes = (int) $now->diffInDays($t->fecha_limite, false);
            }

            return [
                'id'             => $t->id,
                'titulo'         => $t->titulo,
                'descripcion'    => $t->descripcion,
                'tipo'           => $t->tipo,
                'tipo_label'     => $t->tipo_label,
                'tipo_color'     => $t->tipo_color,
                'fecha_limite'   => $t->fecha_limite->toDateString(),
                'puntos_valor'   => $t->puntos_valor,
                'asignatura'     => $t->asignacion?->asignatura?->nombre,
                'estado'         => $estado,
                'dias_restantes' => $diasRestantes,
                'nota_entrega'   => $entrega?->nota,
                'comentario'     => $entrega?->comentario,
            ];
        });

        $pendientes = $tareasMapped->where('estado', 'pendiente')->count();
        $vencidas   = $tareasMapped->where('estado', 'vencida')->count();
        $entregadas = $tareasMapped->whereIn('estado', ['entregada', 'revisada'])->count();

        return [
            'estudiante' => $estudiante->nombre_completo,
            'resumen'    => compact('pendientes', 'vencidas', 'entregadas'),
            'tareas'     => $tareasMapped->values(),
        ];
    }
}
