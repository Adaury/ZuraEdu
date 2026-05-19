<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\RespuestaEncuesta;
use Illuminate\Http\Request;

class EncuestasApiController extends Controller
{
    /** GET /api/v1/encuestas */
    public function index(Request $request)
    {
        $user = $request->user();

        $rol = match(true) {
            $user->hasRole('Estudiante')     => 'estudiantes',
            $user->hasRole('Representante')  => 'padres',
            default                           => 'todos',
        };

        $encuestas = Encuesta::activas()
            ->dirigidaA($rol)
            ->withCount('preguntas')
            ->latest()
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'titulo'          => $e->titulo,
                'descripcion'     => $e->descripcion,
                'preguntas_count' => $e->preguntas_count,
                'fecha_cierre'    => $e->fecha_cierre?->toDateString(),
                'ya_respondio'    => $e->yaRespondio($user->id),
            ]);

        return response()->json(['encuestas' => $encuestas]);
    }

    /** GET /api/v1/encuestas/{encuesta} — detalle con preguntas y opciones */
    public function show(Request $request, Encuesta $encuesta)
    {
        abort_unless($encuesta->activo, 403, 'Encuesta no disponible.');
        if ($encuesta->fecha_cierre && $encuesta->fecha_cierre->isPast()) {
            return response()->json(['message' => 'La encuesta ha cerrado.'], 403);
        }

        $encuesta->load('preguntas.opciones');

        return response()->json([
            'encuesta' => [
                'id'           => $encuesta->id,
                'titulo'       => $encuesta->titulo,
                'descripcion'  => $encuesta->descripcion,
                'fecha_cierre' => $encuesta->fecha_cierre?->toDateString(),
                'ya_respondio' => $encuesta->yaRespondio($request->user()->id),
                'preguntas'    => $encuesta->preguntas->map(fn($p) => [
                    'id'      => $p->id,
                    'texto'   => $p->texto,
                    'tipo'    => $p->tipo,
                    'orden'   => $p->orden,
                    'opciones' => $p->opciones->map(fn($o) => [
                        'id'    => $o->id,
                        'texto' => $o->texto,
                    ])->values(),
                ])->values(),
            ],
        ]);
    }

    /** POST /api/v1/encuestas/{encuesta}/responder */
    public function responder(Request $request, Encuesta $encuesta)
    {
        abort_unless($encuesta->activo, 403, 'Encuesta no disponible.');

        if ($encuesta->fecha_cierre && $encuesta->fecha_cierre->isPast()) {
            return response()->json(['message' => 'La encuesta ha cerrado.'], 403);
        }

        if ($encuesta->yaRespondio($request->user()->id)) {
            return response()->json(['message' => 'Ya respondiste esta encuesta.'], 422);
        }

        $encuesta->load('preguntas.opciones');

        $rules = [];
        foreach ($encuesta->preguntas as $pregunta) {
            if ($pregunta->tipo === 'opcion_multiple') {
                $rules["respuestas.{$pregunta->id}.opcion_id"] = 'required|exists:opciones_pregunta,id';
            } elseif ($pregunta->tipo === 'escala_1_5') {
                $rules["respuestas.{$pregunta->id}.escala_valor"] = 'required|integer|min:1|max:5';
            } else {
                $rules["respuestas.{$pregunta->id}.respuesta_texto"] = 'required|string|max:1000';
            }
        }

        $validated = $request->validate($rules);

        foreach ($encuesta->preguntas as $pregunta) {
            $dato = $validated['respuestas'][$pregunta->id] ?? [];
            RespuestaEncuesta::create([
                'encuesta_id'     => $encuesta->id,
                'pregunta_id'     => $pregunta->id,
                'user_id'         => $request->user()->id,
                'opcion_id'       => $dato['opcion_id'] ?? null,
                'escala_valor'    => isset($dato['escala_valor']) ? (int) $dato['escala_valor'] : null,
                'respuesta_texto' => $dato['respuesta_texto'] ?? null,
            ]);
        }

        return response()->json(['message' => '¡Gracias por participar!']);
    }
}
