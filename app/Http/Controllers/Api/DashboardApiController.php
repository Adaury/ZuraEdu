<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    /** GET /api/v1/dashboard */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;

        return match (true) {
            in_array($role, ['Administrador','Director','Coordinador Academico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']) => $this->admin($user),
            $role === 'Docente'       => $this->docente($user),
            $role === 'Estudiante'    => $this->estudiante($user),
            $role === 'Representante' => $this->padre($user),
            default => response()->json(['message' => 'Rol no soportado.'], 403),
        };
    }

    private function admin($user)
    {
        $sy = SchoolYear::actual();
        return response()->json([
            'role' => 'admin',
            'school_year' => $sy?->nombre,
            'stats' => [
                'estudiantes' => Estudiante::activos()->count(),
                'docentes'    => Docente::activos()->count(),
                'grupos'      => Grupo::where('activo', true)->when($sy, fn($q) => $q->where('school_year_id', $sy->id))->count(),
            ],
        ]);
    }

    private function docente($user)
    {
        $sy      = SchoolYear::actual();
        $docente = Docente::where('user_id', $user->id)->first();

        $asignaciones = ($docente && $sy)
            ? Asignacion::where('docente_id', $docente->id)->where('school_year_id', $sy->id)->where('activo', true)
                ->with(['grupo.grado','grupo.seccion','asignatura'])->get()
                ->map(fn($a) => ['id' => $a->id, 'asignatura' => $a->asignatura?->nombre, 'grupo' => $a->grupo?->nombre_completo, 'area' => $a->area])
            : [];

        return response()->json([
            'role'         => 'docente',
            'nombre'       => $docente ? "{$docente->apellidos}, {$docente->nombres}" : $user->name,
            'school_year'  => $sy?->nombre,
            'asignaciones' => $asignaciones,
        ]);
    }

    private function estudiante($user)
    {
        $sy         = SchoolYear::actual();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        $matricula  = $estudiante?->matriculas()
            ->where('estado','activa')->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with(['grupo.grado','grupo.seccion'])->latest()->first();

        $promedio = null;
        $asistencia = null;

        if ($matricula) {
            $notas = Calificacion::where('matricula_id', $matricula->id)->where('publicado', true)->pluck('nota_final');
            $notasA = CalificacionAcademica::where('matricula_id', $matricula->id)->where('publicado', true)->pluck('nota_final');
            $todas = $notas->merge($notasA)->filter();
            $promedio = $todas->count() ? round($todas->avg(), 1) : null;

            $total = Asistencia::where('matricula_id', $matricula->id)->count();
            $pres  = Asistencia::where('matricula_id', $matricula->id)->whereIn('estado',['presente','tardanza'])->count();
            $asistencia = $total > 0 ? round($pres / $total * 100, 1) : null;
        }

        return response()->json([
            'role'        => 'estudiante',
            'nombre'      => $estudiante ? "{$estudiante->apellidos}, {$estudiante->nombres}" : $user->name,
            'grupo'       => $matricula?->grupo?->nombre_completo,
            'school_year' => $sy?->nombre,
            'promedio'    => $promedio,
            'asistencia'  => $asistencia,
        ]);
    }

    private function padre($user)
    {
        $sy   = SchoolYear::actual();
        $rep  = Representante::where('user_id', $user->id)->first();
        $hijos = $rep
            ? $rep->estudiantes()->with(['matriculas' => fn($q) => $q->where('estado','activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))->with(['grupo.grado','grupo.seccion'])])->get()
                ->map(fn($e) => ['id' => $e->id, 'nombre' => "{$e->nombres} {$e->apellidos}", 'grupo' => $e->matriculas->first()?->grupo?->nombre_completo])
            : [];

        return response()->json([
            'role'        => 'padre',
            'nombre'      => $rep?->nombres ?? $user->name,
            'school_year' => $sy?->nombre,
            'hijos'       => $hijos,
        ]);
    }
}
