<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\InstrumentoEvaluacionEstudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ResultadosEvaluacionApiController extends Controller
{
    /** GET /api/v1/mis-resultados — Estudiante ve sus propios resultados */
    public function misResultados(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) {
            return response()->json(['message' => 'Solo para estudiantes.'], 403);
        }
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);
        return $this->datos($estudiante);
    }

    /** GET /api/v1/mis-resultados/hijo/{estudiante} — Representante ve los de su hijo */
    public function hijoResultados(Request $request, Estudiante $estudiante)
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
                'resultados' => [],
            ]);
        }

        $resultados = InstrumentoEvaluacionEstudiante::with([
            'instrumento.asignacion.asignatura',
            'instrumento.periodo',
            'instrumento.criterios',
        ])
        ->where('matricula_id', $matricula->id)
        ->get()
        ->map(function ($res) {
            $instr      = $res->instrumento;
            $asignatura = $instr?->asignacion?->asignatura;
            $puntajes   = $res->puntajes ?? [];

            $criterios = ($instr?->criterios ?? collect())->map(fn($c) => [
                'id'       => $c->id,
                'nombre'   => $c->nombre,
                'peso_max' => (float) $c->peso_max,
                'puntaje'  => isset($puntajes[$c->id]) ? (float) $puntajes[$c->id] : null,
            ])->values();

            return [
                'instrumento_id'   => $instr?->id,
                'titulo'           => $instr?->titulo ?? '—',
                'tipo'             => $instr?->tipo,
                'tipo_label'       => $instr?->tipo_label,
                'asignatura'       => $asignatura?->nombre ?? '—',
                'asignatura_color' => $asignatura?->color  ?? '#64748b',
                'periodo_nombre'   => $instr?->periodo?->nombre ?? '—',
                'ponderacion'      => $res->ponderacion !== null ? round((float) $res->ponderacion, 1) : null,
                'nivel_desempeno'  => $res->nivel_desempeno,
                'observacion'      => $res->observacion,
                'criterios'        => $criterios,
            ];
        })
        ->sortBy([['asignatura', 'asc'], ['periodo_nombre', 'asc']])
        ->values();

        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'resultados' => $resultados,
        ]);
    }
}
