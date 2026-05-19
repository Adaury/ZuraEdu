<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\EstudianteRuta;
use App\Models\Representante;
use Illuminate\Http\Request;

class TransporteApiController extends Controller
{
    /** GET /api/v1/transporte/mi-ruta — para Estudiante */
    public function miRuta(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        return response()->json($this->buildRuta($estudiante));
    }

    /** GET /api/v1/transporte/ruta-hijo/{estudiante} — para Representante */
    public function rutaHijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return response()->json($this->buildRuta($estudiante));
    }

    private function buildRuta(Estudiante $estudiante): array
    {
        $asignacion = EstudianteRuta::where('estudiante_id', $estudiante->id)
            ->with(['ruta.paradas', 'parada'])
            ->latest()
            ->first();

        if (! $asignacion || ! $asignacion->ruta) {
            return [
                'estudiante' => $estudiante->nombre_completo,
                'asignado'   => false,
                'ruta'       => null,
            ];
        }

        $ruta = $asignacion->ruta;

        return [
            'estudiante' => $estudiante->nombre_completo,
            'asignado'   => true,
            'ruta'       => [
                'nombre'            => $ruta->nombre,
                'conductor'         => $ruta->conductor,
                'telefono_conductor'=> $ruta->telefono_conductor,
                'placa'             => $ruta->placa,
                'horario_ida'       => $ruta->horario_ida,
                'horario_vuelta'    => $ruta->horario_vuelta,
                'descripcion'       => $ruta->descripcion,
            ],
            'mi_parada'  => $asignacion->parada ? [
                'nombre'         => $asignacion->parada->nombre,
                'hora_estimada'  => $asignacion->parada->hora_estimada,
                'referencia'     => $asignacion->parada->referencia ?? null,
            ] : null,
            'todas_paradas' => $ruta->paradas->map(fn($p) => [
                'nombre'        => $p->nombre,
                'hora_estimada' => $p->hora_estimada,
                'orden'         => $p->orden ?? null,
            ])->sortBy('orden')->values(),
        ];
    }
}
