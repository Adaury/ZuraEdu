<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use App\Models\PreguntaEncuesta;
use App\Models\OpcionPregunta;
use Illuminate\Http\Request;

class EncuestaController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────
    public function index()
    {
        $encuestas = Encuesta::withCount(['preguntas', 'respuestas'])
            ->latest()
            ->paginate(20);

        return view('admin.encuestas.index', compact('encuestas'));
    }

    // ── Create ────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin.encuestas.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'dirigida_a'   => 'required|in:padres,estudiantes,todos',
            'activo'       => 'boolean',
            'fecha_cierre' => 'nullable|date|after_or_equal:today',
            'preguntas'    => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tipo'  => 'required|in:opcion_multiple,texto_libre,escala_1_5',
            'preguntas.*.opciones'   => 'nullable|array',
            'preguntas.*.opciones.*' => 'nullable|string',
        ]);

        $encuesta = Encuesta::create([
            'titulo'       => $data['titulo'],
            'descripcion'  => $data['descripcion'] ?? null,
            'dirigida_a'   => $data['dirigida_a'],
            'activo'       => $request->boolean('activo', true),
            'fecha_cierre' => $data['fecha_cierre'] ?? null,
        ]);

        foreach ($data['preguntas'] as $orden => $preguntaData) {
            $pregunta = PreguntaEncuesta::create([
                'encuesta_id' => $encuesta->id,
                'texto'       => $preguntaData['texto'],
                'tipo'        => $preguntaData['tipo'],
                'orden'       => $orden,
            ]);

            if ($preguntaData['tipo'] === 'opcion_multiple' && ! empty($preguntaData['opciones'])) {
                foreach (array_values(array_filter($preguntaData['opciones'])) as $opOrden => $textoOpcion) {
                    if (trim($textoOpcion) !== '') {
                        OpcionPregunta::create([
                            'pregunta_id' => $pregunta->id,
                            'texto'       => trim($textoOpcion),
                            'orden'       => $opOrden,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('admin.encuestas.index')
                         ->with('success', 'Encuesta creada correctamente.');
    }

    // ── Show (resultados) ─────────────────────────────────────────────────
    public function show(Encuesta $encuesta)
    {
        $encuesta->load(['preguntas.opciones', 'preguntas.respuestas']);

        $estadisticas = $encuesta->preguntas->map(function ($pregunta) {
            return [
                'pregunta'     => $pregunta,
                'estadisticas' => $pregunta->estadisticas(),
            ];
        });

        $totalParticipantes = $encuesta->totalParticipantes();

        return view('admin.encuestas.show', compact('encuesta', 'estadisticas', 'totalParticipantes'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Encuesta $encuesta)
    {
        $encuesta->delete();
        return back()->with('success', 'Encuesta eliminada.');
    }

    // ── Toggle activo ─────────────────────────────────────────────────────
    public function toggleActivo(Encuesta $encuesta)
    {
        $encuesta->update(['activo' => ! $encuesta->activo]);

        $estado = $encuesta->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Encuesta {$estado}.");
    }
}
