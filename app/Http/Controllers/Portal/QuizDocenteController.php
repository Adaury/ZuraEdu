<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\MaterialClase;
use App\Models\ZcIntento;
use App\Models\ZcOpcion;
use App\Models\ZcPregunta;
use App\Models\ZcQuiz;
use Illuminate\Http\Request;

class QuizDocenteController extends Controller
{
    private function getDocente(): Docente
    {
        $docente = Docente::where('user_id', auth()->id())->first();
        abort_unless($docente, 403);
        return $docente;
    }

    private function autorizarClase(ClaseVirtual $clase, Docente $docente): void
    {
        abort_unless($clase->asignacion->docente_id === $docente->id, 403);
    }

    // ── Crear quiz ───────────────────────────────────────────────────────
    public function crear(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion.asignatura');
        $this->autorizarClase($claseVirtual, $docente);
        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_if($material->quiz()->exists(), 422, 'Este material ya tiene un quiz.');

        return view('portal.classroom.docente.quiz_crear', compact('claseVirtual', 'material'));
    }

    // ── Guardar quiz + preguntas ─────────────────────────────────────────
    public function guardar(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);
        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        $data = $request->validate([
            'duracion_minutos'      => 'nullable|integer|min:1|max:300',
            'intentos_max'          => 'required|integer|min:1|max:10',
            'autocorreccion'        => 'boolean',
            'aleatorizar_preguntas' => 'boolean',
            'mostrar_respuestas'    => 'boolean',
            'preguntas'             => 'required|array|min:1',
            'preguntas.*.enunciado' => 'required|string|max:1000',
            'preguntas.*.tipo'      => 'required|in:multiple,verdadero_falso,abierta',
            'preguntas.*.puntos'    => 'required|numeric|min:0.5|max:100',
            'preguntas.*.opciones'  => 'nullable|array',
        ]);

        $quiz = ZcQuiz::create([
            'material_id'           => $material->id,
            'duracion_minutos'      => $data['duracion_minutos'] ?? null,
            'intentos_max'          => $data['intentos_max'],
            'autocorreccion'        => $request->boolean('autocorreccion', true),
            'aleatorizar_preguntas' => $request->boolean('aleatorizar_preguntas'),
            'mostrar_respuestas'    => $request->boolean('mostrar_respuestas', true),
        ]);

        foreach ($data['preguntas'] as $i => $preguntaData) {
            $pregunta = ZcPregunta::create([
                'quiz_id'   => $quiz->id,
                'enunciado' => $preguntaData['enunciado'],
                'tipo'      => $preguntaData['tipo'],
                'puntos'    => $preguntaData['puntos'],
                'orden'     => $i,
            ]);

            // Crear opciones para preguntas de selección
            if ($preguntaData['tipo'] !== 'abierta' && !empty($preguntaData['opciones'])) {
                foreach ($preguntaData['opciones'] as $j => $opcionData) {
                    if (empty($opcionData['texto'])) continue;
                    ZcOpcion::create([
                        'pregunta_id' => $pregunta->id,
                        'texto'       => $opcionData['texto'],
                        'es_correcta' => isset($opcionData['correcta']) && $opcionData['correcta'],
                        'orden'       => $j,
                    ]);
                }
            } elseif ($preguntaData['tipo'] === 'verdadero_falso') {
                // Auto-generar opciones V/F si no vienen
                ZcOpcion::create(['pregunta_id'=>$pregunta->id,'texto'=>'Verdadero','es_correcta'=>($preguntaData['correcta_vf']??'V')==='V','orden'=>0]);
                ZcOpcion::create(['pregunta_id'=>$pregunta->id,'texto'=>'Falso','es_correcta'=>($preguntaData['correcta_vf']??'V')==='F','orden'=>1]);
            }
        }

        return redirect()->route('portal.docente.classroom.show', $claseVirtual)
            ->with('success', 'Quiz creado correctamente con '.$quiz->preguntas()->count().' preguntas.');
    }

    // ── Editar quiz ──────────────────────────────────────────────────────
    public function editar(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion.asignatura');
        $this->autorizarClase($claseVirtual, $docente);
        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        $quiz = $material->quiz()->with('preguntas.opciones')->firstOrFail();
        return view('portal.classroom.docente.quiz_editar', compact('claseVirtual', 'material', 'quiz'));
    }

    // ── Actualizar quiz ──────────────────────────────────────────────────
    public function actualizar(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $quiz = $material->quiz()->firstOrFail();
        $quiz->update([
            'duracion_minutos'      => $request->duracion_minutos,
            'intentos_max'          => $request->intentos_max ?? 1,
            'autocorreccion'        => $request->boolean('autocorreccion', true),
            'aleatorizar_preguntas' => $request->boolean('aleatorizar_preguntas'),
            'mostrar_respuestas'    => $request->boolean('mostrar_respuestas', true),
        ]);

        // Regenerar preguntas si se enviaron nuevas
        if ($request->has('preguntas')) {
            $quiz->preguntas()->delete(); // Cascada borra opciones y respuestas
            foreach ($request->preguntas as $i => $preguntaData) {
                if (empty($preguntaData['enunciado'])) continue;
                $pregunta = ZcPregunta::create([
                    'quiz_id'   => $quiz->id,
                    'enunciado' => $preguntaData['enunciado'],
                    'tipo'      => $preguntaData['tipo'] ?? 'multiple',
                    'puntos'    => $preguntaData['puntos'] ?? 1,
                    'orden'     => $i,
                ]);
                if (!empty($preguntaData['opciones'])) {
                    foreach ($preguntaData['opciones'] as $j => $op) {
                        if (empty($op['texto'])) continue;
                        ZcOpcion::create([
                            'pregunta_id' => $pregunta->id,
                            'texto'       => $op['texto'],
                            'es_correcta' => isset($op['correcta']) && $op['correcta'],
                            'orden'       => $j,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('portal.docente.classroom.show', $claseVirtual)
            ->with('success', 'Quiz actualizado.');
    }

    // ── Eliminar quiz ────────────────────────────────────────────────────
    public function eliminar(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $material->quiz?->delete();
        return back()->with('success', 'Quiz eliminado.');
    }

    // ── Resultados del quiz (todos los estudiantes) ──────────────────────
    public function resultados(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        $quiz = $material->quiz()->with('preguntas')->firstOrFail();

        $intentos = ZcIntento::where('quiz_id', $quiz->id)
            ->where('estado', 'finalizado')
            ->with(['matricula.estudiante'])
            ->orderByDesc('puntuacion')
            ->get();

        $matriculas = \App\Models\Matricula::with('estudiante')
            ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')
            ->get();

        // Mejor intento por estudiante
        $mejoresIntentos = $intentos->groupBy('matricula_id')->map(fn($g) =>
            $g->sortByDesc('puntuacion')->first()
        );

        $stats = [
            'promedio'     => $intentos->avg('puntuacion') ? round($intentos->avg('puntuacion'), 1) : null,
            'aprobados'    => $intentos->filter(fn($i) => $i->porcentaje >= 60)->count(),
            'completaron'  => $mejoresIntentos->count(),
            'pendientes'   => $matriculas->count() - $mejoresIntentos->count(),
        ];

        return view('portal.classroom.docente.quiz_resultados', compact(
            'claseVirtual', 'material', 'quiz', 'matriculas',
            'mejoresIntentos', 'stats'
        ));
    }
}
