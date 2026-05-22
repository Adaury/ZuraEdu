<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Estudiante;
use App\Models\InscripcionEvento;
use Illuminate\Http\Request;

class EventosApiController extends Controller
{
    public function index(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->firstOrFail();

        $inscritos = InscripcionEvento::where('estudiante_id', $estudiante->id)
            ->pluck('evento_id')
            ->flip();

        $eventos = Evento::activos()
            ->withCount('inscripciones')
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn(Evento $e) => [
                'id'                => $e->id,
                'nombre'            => $e->nombre,
                'descripcion'       => $e->descripcion,
                'tipo'              => $e->tipo,
                'tipo_label'        => $e->tipo_label,
                'tipo_color'        => $e->tipo_color,
                'lugar'             => $e->lugar,
                'fecha_inicio'      => $e->fecha_inicio?->format('Y-m-d'),
                'fecha_fin'         => $e->fecha_fin?->format('Y-m-d'),
                'cupo_maximo'       => $e->cupo_maximo,
                'inscritos'         => $e->inscripciones_count,
                'cupos_disponibles' => is_null($e->cupo_maximo) ? null : max(0, $e->cupo_maximo - $e->inscripciones_count),
                'lleno'             => ! is_null($e->cupo_maximo) && $e->inscripciones_count >= $e->cupo_maximo,
                'inscrito'          => isset($inscritos[$e->id]),
            ]);

        return response()->json(['data' => $eventos]);
    }

    public function inscribirse(Request $request, Evento $evento)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->firstOrFail();

        abort_unless($evento->activo, 403, 'Este evento no está disponible.');

        $yaInscrito = InscripcionEvento::where('evento_id', $evento->id)
            ->where('estudiante_id', $estudiante->id)
            ->exists();

        if ($yaInscrito) {
            return response()->json(['message' => 'Ya estás inscrito en este evento.'], 422);
        }

        if (! is_null($evento->cupo_maximo)) {
            $inscritos = InscripcionEvento::where('evento_id', $evento->id)->count();
            if ($inscritos >= $evento->cupo_maximo) {
                return response()->json(['message' => 'El evento ya no tiene cupo disponible.'], 422);
            }
        }

        InscripcionEvento::create([
            'evento_id'         => $evento->id,
            'estudiante_id'     => $estudiante->id,
            'fecha_inscripcion' => now()->toDateString(),
            'asistio'           => false,
        ]);

        return response()->json(['message' => '¡Inscripción exitosa!']);
    }
}
