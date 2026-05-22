<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Reunion;
use Illuminate\Http\Request;

class ReunionesApiController extends Controller
{
    /** GET /api/v1/docente/mis-reuniones */
    public function misReuniones(Request $request)
    {
        if (! $request->user()->hasRole('Docente')) {
            return response()->json(['message' => 'Solo para docentes.'], 403);
        }

        $user    = $request->user();
        $docente = Docente::where('user_id', $user->id)->first();
        if (! $docente) {
            return response()->json(['message' => 'Perfil de docente no encontrado.'], 404);
        }

        $reuniones = Reunion::with('acuerdos')
            ->where(function ($q) use ($user) {
                $q->where('convocante_id', $user->id)
                  ->orWhere('tipo', 'reunion_docentes');
            })
            ->orderByDesc('fecha')
            ->get()
            ->map(fn($r) => [
                'id'                => $r->id,
                'titulo'            => $r->titulo,
                'tipo'              => $r->tipo,
                'tipo_label'        => $r->tipoLabel(),
                'estado'            => $r->estado,
                'estado_label'      => $r->estadoLabel(),
                'fecha'             => $r->fecha->format('Y-m-d H:i'),
                'fecha_label'       => $r->fecha->format('d/m/Y'),
                'hora_label'        => $r->fecha->format('H:i'),
                'lugar'             => $r->lugar,
                'agenda'            => $r->agenda,
                'es_convocante'     => $r->convocante_id === $user->id,
                'acuerdos_total'    => $r->acuerdos->count(),
                'acuerdos_cumplidos'=> $r->acuerdos->where('cumplido', true)->count(),
            ]);

        return response()->json([
            'reuniones'   => $reuniones,
            'total'       => $reuniones->count(),
            'programadas' => $reuniones->where('estado', 'programada')->count(),
            'realizadas'  => $reuniones->where('estado', 'realizada')->count(),
        ]);
    }
}
