<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Observacion;
use App\Models\Representante;
use Illuminate\Http\Request;

class ObservacionesApiController extends Controller
{
    /** GET /api/v1/observaciones — Estudiante ve sus propias observaciones */
    public function misObservaciones(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) {
            return response()->json(['message' => 'Solo para estudiantes.'], 403);
        }

        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) {
            return response()->json(['message' => 'Perfil no encontrado.'], 404);
        }

        return $this->datos($estudiante);
    }

    /** GET /api/v1/observaciones/hijo/{estudiante} — Representante ve observaciones de su hijo */
    public function hijoObservaciones(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return $this->datos($estudiante);
    }

    private function datos(Estudiante $estudiante)
    {
        $obs = Observacion::with(['docente', 'asignacion.asignatura'])
            ->delEstudiante($estudiante->id)
            ->publicas()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $lista = $obs->map(fn($o) => $this->format($o));

        return response()->json([
            'estudiante'    => "{$estudiante->nombres} {$estudiante->apellidos}",
            'observaciones' => $lista,
            'resumen'       => [
                'total'      => $lista->count(),
                'academica'  => $lista->where('tipo', 'academica')->count(),
                'conductual' => $lista->where('tipo', 'conductual')->count(),
                'positiva'   => $lista->where('tipo', 'positiva')->count(),
                'general'    => $lista->where('tipo', 'general')->count(),
            ],
        ]);
    }

    private function format(Observacion $o): array
    {
        $meta    = Observacion::TIPOS[$o->tipo] ?? Observacion::TIPOS['general'];
        $docente = $o->docente;
        $nombre  = $docente ? "{$docente->apellidos}, {$docente->nombres}" : 'Docente';

        return [
            'id'         => $o->id,
            'tipo'       => $o->tipo,
            'tipo_label' => $meta['label'],
            'tipo_color' => $meta['color'],
            'texto'      => $o->texto,
            'docente'    => $nombre,
            'asignatura' => $o->asignacion?->asignatura?->nombre,
            'fecha'      => $o->created_at?->format('d/m/Y'),
            'fecha_hace' => $o->created_at?->locale('es')->diffForHumans(),
        ];
    }
}
