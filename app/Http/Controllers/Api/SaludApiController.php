<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\FichaSalud;
use App\Models\IncidenteMedico;
use App\Models\Representante;
use Illuminate\Http\Request;

class SaludApiController extends Controller
{
    /** GET /api/v1/salud/hijo/{estudiante} — Representante ve la ficha de salud de su hijo */
    public function saludHijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $ficha = FichaSalud::where('estudiante_id', $estudiante->id)->first();

        $incidentes = IncidenteMedico::where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get()
            ->map(fn($i) => [
                'id'                      => $i->id,
                'fecha'                   => $i->fecha->format('d/m/Y'),
                'hora'                    => $i->hora ? substr($i->hora, 0, 5) : null,
                'tipo'                    => $i->tipo,
                'tipo_label'              => ucfirst($i->tipo),
                'descripcion'             => $i->descripcion,
                'accion_tomada'           => $i->accion_tomada,
                'remitido_a'              => $i->remitido_a,
                'notificado_representante'=> $i->notificado_representante,
            ]);

        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'ficha'      => $ficha ? [
                'tipo_sangre'          => $ficha->tipo_sangre,
                'alergias'             => $ficha->alergias,
                'condiciones_medicas'  => $ficha->condiciones_medicas,
                'medicamentos'         => $ficha->medicamentos,
                'contacto_emergencia'  => $ficha->contacto_emergencia,
                'telefono_emergencia'  => $ficha->telefono_emergencia,
                'seguro_medico'        => $ficha->seguro_medico,
                'num_seguro'           => $ficha->num_seguro,
            ] : null,
            'incidentes' => $incidentes,
        ]);
    }
}
