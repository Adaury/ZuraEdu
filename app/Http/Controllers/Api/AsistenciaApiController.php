<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class AsistenciaApiController extends Controller
{
    /** GET /api/v1/asistencia */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) return response()->json(['message' => 'Solo para estudiantes.'], 403);
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);
        return $this->datos($estudiante);
    }

    /** GET /api/v1/asistencia/hijo/{estudiante} */
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
            ->latest()->first();

        if (! $matricula) return response()->json(['total' => 0, 'porcentaje' => null, 'por_materia' => [], 'ultimas' => []]);

        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)->orderBy('fecha','desc')->get();

        $total     = $asistencias->count();
        $presentes = $asistencias->whereIn('estado',['presente','tardanza'])->count();
        $ausentes  = $asistencias->where('estado','ausente')->count();
        $tardanzas = $asistencias->where('estado','tardanza')->count();

        $porMateria = $asistencias->groupBy('asignacion_id')->map(fn($rows) => [
            'asignatura' => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
            'total'      => $rows->count(),
            'presentes'  => $rows->whereIn('estado',['presente','tardanza'])->count(),
            'ausentes'   => $rows->where('estado','ausente')->count(),
            'porcentaje' => $rows->count() > 0
                ? round($rows->whereIn('estado',['presente','tardanza'])->count() / $rows->count() * 100, 1)
                : null,
        ])->values();

        return response()->json([
            'total'      => $total,
            'presentes'  => $presentes,
            'ausentes'   => $ausentes,
            'tardanzas'  => $tardanzas,
            'porcentaje' => $total > 0 ? round($presentes / $total * 100, 1) : null,
            'por_materia'=> $porMateria,
            'ultimas'    => $asistencias->take(30)->map(fn($a) => [
                'fecha'      => $a->fecha,
                'asignatura' => $a->asignacion?->asignatura?->nombre,
                'estado'     => $a->estado,
            ]),
        ]);
    }
}
