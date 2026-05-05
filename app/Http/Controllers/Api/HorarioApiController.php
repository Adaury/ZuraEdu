<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class HorarioApiController extends Controller
{
    /** GET /api/v1/horario */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;
        $sy   = SchoolYear::actual();
        $h    = $sy ? Horario::where('school_year_id', $sy->id)->where('estado','publicado')->latest()->first() : null;

        if (! $h) return response()->json(['publicado' => false, 'horario' => []]);

        if ($role === 'Estudiante') {
            $est = Estudiante::where('user_id', $user->id)->first();
            $mat = $est?->matriculas()->where('estado','activa')->where('school_year_id', $sy->id)->latest()->first();
            return $this->porGrupo($h, $mat?->grupo_id);
        }

        if ($role === 'Docente') {
            $doc = Docente::where('user_id', $user->id)->first();
            return $this->porDocente($h, $doc);
        }

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /** GET /api/v1/horario/hijo/{estudiante} */
    public function hijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }
        $sy  = SchoolYear::actual();
        $h   = $sy ? Horario::where('school_year_id', $sy->id)->where('estado','publicado')->latest()->first() : null;
        if (! $h) return response()->json(['publicado' => false]);
        $mat = $estudiante->matriculas()->where('estado','activa')->where('school_year_id', $sy->id)->latest()->first();
        return $this->porGrupo($h, $mat?->grupo_id);
    }

    private function porGrupo($horario, $grupoId)
    {
        if (! $grupoId) return response()->json(['publicado' => true, 'horario' => []]);
        $detalles = HorarioDetalle::with(['asignacion.asignatura','asignacion.docente','franja','aula'])
            ->where('horario_id', $horario->id)
            ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $grupoId))
            ->get()->map(fn($d) => [
                'dia'        => $d->dia,
                'franja'     => ['inicio' => $d->franja?->hora_inicio, 'fin' => $d->franja?->hora_fin, 'nombre' => $d->franja?->nombre],
                'asignatura' => $d->asignacion?->asignatura?->nombre,
                'color'      => $d->asignacion?->asignatura?->color ?? '#64748b',
                'docente'    => $d->asignacion?->docente ? "{$d->asignacion->docente->apellidos}, {$d->asignacion->docente->nombres}" : null,
                'aula'       => $d->aula?->nombre,
            ]);
        return response()->json(['publicado' => true, 'horario' => $detalles]);
    }

    private function porDocente($horario, $docente)
    {
        if (! $docente) return response()->json(['publicado' => true, 'horario' => []]);
        $detalles = HorarioDetalle::with(['asignacion.asignatura','asignacion.grupo.grado','asignacion.grupo.seccion','franja','aula'])
            ->where('horario_id', $horario->id)
            ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id))
            ->get()->map(fn($d) => [
                'dia'        => $d->dia,
                'franja'     => ['inicio' => $d->franja?->hora_inicio, 'fin' => $d->franja?->hora_fin],
                'asignatura' => $d->asignacion?->asignatura?->nombre,
                'color'      => $d->asignacion?->asignatura?->color ?? '#64748b',
                'grupo'      => $d->asignacion?->grupo?->nombre_completo,
                'aula'       => $d->aula?->nombre,
            ]);
        return response()->json(['publicado' => true, 'horario' => $detalles]);
    }
}
