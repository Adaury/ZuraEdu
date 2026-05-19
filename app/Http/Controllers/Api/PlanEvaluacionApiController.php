<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Estudiante;
use App\Models\InstrumentoEvaluacion;
use App\Models\Periodo;
use App\Models\PlanEvaluacionPeriodo;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PlanEvaluacionApiController extends Controller
{
    /** GET /api/v1/plan-evaluacion — Estudiante ve su plan */
    public function miPlan(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) {
            return response()->json(['message' => 'Solo para estudiantes.'], 403);
        }
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);
        return $this->datos($estudiante);
    }

    /** GET /api/v1/plan-evaluacion/hijo/{estudiante} — Representante ve el plan de su hijo */
    public function hijoPlan(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }
        return $this->datos($estudiante);
    }

    private function datos(Estudiante $estudiante)
    {
        $sy        = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) {
            return response()->json([
                'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
                'periodos'   => [],
                'categorias' => [],
                'planes'     => [],
            ]);
        }

        $periodos = Periodo::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->orderBy('numero')->get()
            ->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre]);

        $categorias = collect(PlanEvaluacionPeriodo::$categorias)
            ->map(fn($v, $k) => ['clave' => $k, 'label' => $v['label'], 'color' => $v['color']])
            ->values();

        $asignaciones = Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $matricula->grupo_id)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('activo', true)
            ->get();

        $asignacionIds = $asignaciones->pluck('id');

        $planesDB = PlanEvaluacionPeriodo::where('publicado', true)
            ->whereIn('asignacion_id', $asignacionIds)
            ->get()
            ->groupBy('periodo_id');

        $instrumentosDB = InstrumentoEvaluacion::where('publicado', true)
            ->whereIn('asignacion_id', $asignacionIds)
            ->get()
            ->groupBy(fn($i) => "{$i->asignacion_id}_{$i->periodo_id}");

        $porPeriodo = [];
        foreach ($periodos as $periodo) {
            $pid         = $periodo['id'];
            $periodPlanes = $planesDB[$pid] ?? collect();

            $porPeriodo[$pid] = $asignaciones->map(function ($asig) use ($periodPlanes, $instrumentosDB, $pid) {
                $plan = $periodPlanes->firstWhere('asignacion_id', $asig->id);
                if (! $plan) return null;

                $key   = "{$asig->id}_{$pid}";
                $instrs = ($instrumentosDB[$key] ?? collect())->map(fn($i) => [
                    'titulo'     => $i->titulo,
                    'tipo'       => $i->tipo,
                    'tipo_label' => $i->tipo_label,
                    'fecha'      => $i->fecha_aplicacion?->format('d/m/Y'),
                ])->values();

                return [
                    'asignatura'       => $asig->asignatura?->nombre ?? '—',
                    'asignatura_color' => $asig->asignatura?->color  ?? '#64748b',
                    'docente'          => $asig->docente
                        ? "{$asig->docente->apellidos}, {$asig->docente->nombres}"
                        : '—',
                    'tareas'        => $plan->tareas,
                    'practicas'     => $plan->practicas,
                    'participacion' => $plan->participacion,
                    'proyecto'      => $plan->proyecto,
                    'examen'        => $plan->examen,
                    'total'         => $plan->total,
                    'observaciones' => $plan->observaciones,
                    'instrumentos'  => $instrs,
                ];
            })->filter()->values();
        }

        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'periodos'   => $periodos,
            'categorias' => $categorias,
            'planes'     => $porPeriodo,
        ]);
    }
}
