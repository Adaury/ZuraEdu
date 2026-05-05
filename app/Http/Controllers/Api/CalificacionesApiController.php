<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class CalificacionesApiController extends Controller
{
    /** GET /api/v1/calificaciones */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) return response()->json(['message' => 'Solo para estudiantes.'], 403);

        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        return $this->datos($estudiante);
    }

    /** GET /api/v1/calificaciones/hijo/{estudiante} */
    public function hijo(Request $request, Estudiante $estudiante)
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
            ->where('estado','activa')->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with(['grupo.grado','grupo.seccion'])->latest()->first();

        if (! $matricula) return response()->json(['grupo' => null, 'tecnicas' => [], 'academicas' => [], 'promedio' => null]);

        $tecnicas = Calificacion::with(['asignacion.asignatura','periodo'])
            ->where('matricula_id', $matricula->id)->where('publicado', true)->get()
            ->groupBy(fn($c) => $c->periodo?->nombre ?? 'Sin período')
            ->map(fn($g) => $g->map(fn($c) => [
                'asignatura' => $c->asignacion?->asignatura?->nombre,
                'nota_final' => $c->nota_final,
                'letra'      => $c->letra ?? null,
                'indicador'  => $c->indicador,
            ]));

        $academicas = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('publicado', true)->get()
            ->map(fn($c) => [
                'asignatura' => $c->asignacion?->asignatura?->nombre,
                'nota_final' => $c->nota_final,
                'indicador'  => $c->indicador,
                'situacion'  => $c->situacion,
            ]);

        $todas = collect($tecnicas->flatten(1)->pluck('nota_final'))
            ->merge($academicas->pluck('nota_final'))->filter();

        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'grupo'      => $matricula->grupo?->nombre_completo,
            'tecnicas'   => $tecnicas,
            'academicas' => $academicas,
            'promedio'   => $todas->count() ? round($todas->avg(), 1) : null,
        ]);
    }
}
