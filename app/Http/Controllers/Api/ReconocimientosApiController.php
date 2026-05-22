<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Reconocimiento;
use App\Models\Representante;
use Illuminate\Http\Request;

class ReconocimientosApiController extends Controller
{
    /** GET /api/v1/reconocimientos — Estudiante ve los suyos */
    public function misReconocimientos(Request $request)
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

    /** GET /api/v1/reconocimientos/hijo/{estudiante} — Representante ve los de su hijo */
    public function hijoReconocimientos(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }
        return $this->datos($estudiante);
    }

    private function datos(Estudiante $estudiante)
    {
        $reconocimientos = Reconocimiento::with('tipo', 'emitidoPor')
            ->where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'titulo'         => $r->titulo,
                'descripcion'    => $r->descripcion,
                'fecha'          => $r->fecha->format('Y-m-d'),
                'fecha_label'    => $r->fecha->format('d/m/Y'),
                'entregado'      => $r->entregado,
                'fecha_entrega'  => $r->fecha_entrega?->format('d/m/Y'),
                'tipo'           => $r->tipo ? [
                    'id'     => $r->tipo->id,
                    'nombre' => $r->tipo->nombre,
                    'icono'  => $r->tipo->icono ?? '🏆',
                ] : null,
                'emitido_por'    => $r->emitidoPor?->name,
            ]);

        return response()->json([
            'estudiante'      => "{$estudiante->nombres} {$estudiante->apellidos}",
            'reconocimientos' => $reconocimientos,
            'total'           => $reconocimientos->count(),
            'entregados'      => $reconocimientos->where('entregado', true)->count(),
        ]);
    }
}
