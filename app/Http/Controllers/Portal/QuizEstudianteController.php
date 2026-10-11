<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClaseVirtual;
use App\Models\Estudiante;
use App\Models\MaterialClase;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\ZcIntento;
use App\Models\ZcOpcion;
use App\Models\ZcPregunta;
use App\Models\ZcQuiz;
use App\Models\ZcRespuesta;
use App\Services\ZuraClassGradeSync;
use Illuminate\Http\Request;

class QuizEstudianteController extends Controller
{
    private function getMatricula(): Matricula
    {
        $estudiante = Estudiante::where('user_id', auth()->id())->first();
        abort_unless($estudiante, 403);

        $schoolYear = SchoolYear::actual();
        $matricula  = Matricula::where('estudiante_id', $estudiante->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        abort_unless($matricula, 403, 'No tiene matrícula activa.');
        return $matricula;
    }

    /** Segundos de gracia tras el límite de tiempo (latencia de red / reloj) antes de rechazar respuestas. */
    private const GRACIA_SEGUNDOS = 30;

    private function expirado(ZcIntento $intento, ZcQuiz $quiz): bool
    {
        if (! $quiz->duracion_minutos || ! $intento->iniciado_en) {
            return false;
        }

        return now()->greaterThan(
            $intento->iniciado_en->copy()->addMinutes($quiz->duracion_minutos)->addSeconds(self::GRACIA_SEGUNDOS)
        );
    }

    // ── Pantalla de inicio del quiz ──────────────────────────────────────
    public function iniciar(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $matricula = $this->getMatricula();

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($claseVirtual->asignacion->grupo_id === $matricula->grupo_id, 403);
        // Un quiz en borrador (no publicado) no se puede abrir por URL directa.
        abort_unless($material->publicado, 404);

        $quiz = $material->quiz()->with('preguntas')->firstOrFail();

        // Verificar si puede intentar
        if (!$quiz->puedeIntentar($matricula->id)) {
            return back()->with('error', "Ya alcanzaste el máximo de {$quiz->intentos_max} intento(s).");
        }

        // Si hay intento activo, redirigir a él
        $intentoActivo = $quiz->intentoActivo($matricula->id);
        if ($intentoActivo) {
            return redirect()->route('portal.estudiante.classroom.quiz.tomar', [$claseVirtual, $material, $intentoActivo]);
        }

        $intentosPrevios = ZcIntento::where('quiz_id', $quiz->id)
            ->where('matricula_id', $matricula->id)
            ->where('estado', 'finalizado')
            ->get();

        return view('portal.classroom.estudiante.quiz_inicio', compact(
            'claseVirtual', 'material', 'quiz', 'matricula', 'intentosPrevios'
        ));
    }

    // ── Crear nuevo intento ──────────────────────────────────────────────
    public function comenzar(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $matricula = $this->getMatricula();
        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($claseVirtual->asignacion->grupo_id === $matricula->grupo_id, 403);
        abort_unless($material->publicado, 404);

        $quiz = $material->quiz()->with('preguntas.opciones')->firstOrFail();

        // Un intento ya en curso se reanuda (antes, un POST directo creaba otro
        // en paralelo y se podía "ensayar" con uno y enviar el otro).
        if ($activo = $quiz->intentoActivo($matricula->id)) {
            return redirect()->route('portal.estudiante.classroom.quiz.tomar', [$claseVirtual, $material, $activo]);
        }

        if (!$quiz->puedeIntentar($matricula->id)) {
            return back()->with('error', "Máximo de intentos alcanzado.");
        }

        $numeroIntento = ZcIntento::where('quiz_id', $quiz->id)
            ->where('matricula_id', $matricula->id)->count() + 1;

        $intento = ZcIntento::create([
            'quiz_id'        => $quiz->id,
            'matricula_id'   => $matricula->id,
            'estado'         => 'en_curso',
            'puntuacion_max' => $quiz->puntaje_total,
            'iniciado_en'    => now(),
            'numero_intento' => $numeroIntento,
        ]);

        return redirect()->route('portal.estudiante.classroom.quiz.tomar', [$claseVirtual, $material, $intento]);
    }

    // ── Pantalla de tomar el quiz ────────────────────────────────────────
    public function tomar(ClaseVirtual $claseVirtual, MaterialClase $material, ZcIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_unless($intento->matricula_id === $matricula->id, 403);
        abort_unless($intento->estado === 'en_curso', 422, 'Este intento ya está finalizado.');
        // El intento debe ser del quiz/material/aula de la URL (padre→hijo contra la BD).
        abort_unless(
            $intento->quiz && $intento->quiz->material_id === $material->id
                && $material->clase_virtual_id === $claseVirtual->id,
            404
        );

        $quiz      = $intento->quiz()->with([
            'preguntas' => fn($q) => $quiz_order = $intento->quiz?->aleatorizar_preguntas
                ? $q->inRandomOrder()
                : $q->orderBy('orden'),
            'preguntas.opciones' => fn($q) => $q->orderBy('orden'),
        ])->first();

        // Tiempo restante
        $tiempoRestante = null;
        if ($quiz->duracion_minutos) {
            $finPrevisto = $intento->iniciado_en->addMinutes($quiz->duracion_minutos);
            $tiempoRestante = max(0, now()->diffInSeconds($finPrevisto, false));
            if ($tiempoRestante <= 0) {
                // Tiempo expirado — finalizar automáticamente (con el material, para que
                // la nota llegue a la entrega y al libro de notas igual que al enviar).
                return $this->finalizarIntento($intento, $quiz, $matricula, $material);
            }
        }

        // Respuestas ya guardadas (para reanudar)
        $respuestasGuardadas = ZcRespuesta::where('intento_id', $intento->id)
            ->get()->keyBy('pregunta_id');

        return view('portal.classroom.estudiante.quiz_tomar', compact(
            'claseVirtual', 'material', 'quiz', 'intento',
            'matricula', 'tiempoRestante', 'respuestasGuardadas'
        ));
    }

    // ── Guardar respuesta (AJAX) ─────────────────────────────────────────
    public function guardarRespuesta(Request $request, ZcIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_unless($intento->matricula_id === $matricula->id, 403);
        abort_unless($intento->estado === 'en_curso', 422);

        $request->validate([
            'pregunta_id'    => 'required|integer',
            'opcion_id'      => 'nullable|integer',
            'texto_respuesta'=> 'nullable|string|max:3000',
        ]);

        $quiz = $intento->quiz;
        abort_unless($quiz, 404);

        // Fuera de tiempo no se aceptan respuestas nuevas (antes solo se vigilaba al abrir la pantalla).
        abort_if($this->expirado($intento, $quiz), 422, 'El tiempo del quiz terminó.');

        // La pregunta debe ser de ESTE quiz y la opción de ESA pregunta (no confiar en los IDs del cliente).
        $pregunta = ZcPregunta::where('quiz_id', $quiz->id)->find($request->pregunta_id);
        abort_unless($pregunta, 422, 'Pregunta inválida.');

        $opcionId = null;
        if ($request->filled('opcion_id')) {
            $opcionId = ZcOpcion::where('pregunta_id', $pregunta->id)->where('id', $request->opcion_id)->value('id');
            abort_unless($opcionId, 422, 'Opción inválida.');
        }

        ZcRespuesta::updateOrCreate(
            ['intento_id' => $intento->id, 'pregunta_id' => $pregunta->id],
            ['opcion_id' => $opcionId, 'texto_respuesta' => $request->texto_respuesta]
        );

        return response()->json(['ok' => true]);
    }

    // ── Enviar / finalizar ───────────────────────────────────────────────
    public function enviar(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material, ZcIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_unless($intento->matricula_id === $matricula->id, 403);
        abort_unless($intento->estado === 'en_curso', 422);

        $quiz = $intento->quiz()->with('preguntas.opciones')->first();

        // padre→hijo contra la BD: la nota se escribe en el material de la URL, así que ese material debe ser el del quiz del intento
        // y estar en un aula de SU grupo (si no, un estudiante podría dirigir la nota de un quiz a otra actividad).
        abort_unless($quiz && $quiz->material_id === $material->id && $material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($claseVirtual->asignacion->grupo_id === $matricula->grupo_id, 403);

        // Guardar respuestas del formulario (fallback si no usaron AJAX). Si el tiempo ya
        // terminó (con gracia), se ignora lo que llegue en este envío y se califica con lo
        // guardado antes de vencer: antes el límite solo se vigilaba al abrir la pantalla.
        $fueraDeTiempo = $this->expirado($intento, $quiz);

        foreach ($fueraDeTiempo ? [] : $request->all() as $key => $valor) {
            if (str_starts_with($key, 'pregunta_')) {
                $preguntaId = (int) str_replace('pregunta_', '', $key);
                $pregunta   = $quiz->preguntas->find($preguntaId);
                if (!$pregunta) continue;

                $opcionId = null;
                $texto    = null;
                if ($pregunta->tipo === 'abierta') {
                    $texto = is_string($valor) ? $valor : null;
                } else {
                    $opcionId = is_numeric($valor) ? (int) $valor : null;
                    // La opción debe pertenecer a esa pregunta.
                    if ($opcionId && ! $pregunta->opciones->contains('id', $opcionId)) {
                        $opcionId = null;
                    }
                }

                ZcRespuesta::updateOrCreate(
                    ['intento_id' => $intento->id, 'pregunta_id' => $preguntaId],
                    ['opcion_id' => $opcionId, 'texto_respuesta' => $texto]
                );
            }
        }

        return $this->finalizarIntento($intento, $quiz, $matricula, $material);
    }

    // ── Lógica de finalización + autocorrección ──────────────────────────
    private function finalizarIntento(ZcIntento $intento, ZcQuiz $quiz, Matricula $matricula, ?MaterialClase $material = null)
    {
        $respuestas   = ZcRespuesta::where('intento_id', $intento->id)->get()->keyBy('pregunta_id');
        $puntuacion   = 0;

        foreach ($quiz->preguntas as $pregunta) {
            $resp = $respuestas->get($pregunta->id);
            if (!$resp) continue;

            if ($pregunta->tipo === 'abierta') {
                // Preguntas abiertas: se marcan para corrección manual
                $resp->update(['es_correcta' => null, 'puntos_obtenidos' => null]);
                continue;
            }

            $opcionCorrecta = $pregunta->opcionCorrecta();
            $esCorrecta     = $opcionCorrecta && $resp->opcion_id === $opcionCorrecta->id;
            $ptsObtenidos   = $esCorrecta ? $pregunta->puntos : 0;
            $puntuacion    += $ptsObtenidos;

            $resp->update([
                'es_correcta'     => $esCorrecta,
                'puntos_obtenidos'=> $ptsObtenidos,
            ]);
        }

        $intento->update([
            'estado'       => 'finalizado',
            'puntuacion'   => $puntuacion,
            'finalizado_en'=> now(),
        ]);

        // Entrega + libro de notas
        if ($material && $quiz->autocorreccion) {
            $puntajeMax    = $quiz->puntaje_total ?: 100;
            $tieneAbiertas = $quiz->preguntas->contains('tipo', 'abierta');

            // Nota del estudiante = su MEJOR intento finalizado (el docente ve "mejor intento"
            // en los resultados; antes la entrega quedaba con el último, aunque fuera peor).
            $mejor = (float) ZcIntento::where('quiz_id', $quiz->id)
                ->where('matricula_id', $matricula->id)
                ->where('estado', 'finalizado')
                ->max('puntuacion');
            $nota = round(($mejor / $puntajeMax) * ($material->puntos ?? 100), 2);

            $entrega = \App\Models\EntregaClassroom::firstOrNew([
                'material_id'  => $material->id,
                'matricula_id' => $matricula->id,
            ]);

            if ($tieneAbiertas) {
                // Las preguntas abiertas no tienen pantalla de corrección propia: la parte
                // automática NO es la nota final. Queda como entrega pendiente para que el docente
                // la califique con el flujo normal de entregas (antes se marcaba "calificado"
                // con las abiertas en 0 y esa nota baja llegaba al libro de notas).
                if ($entrega->estado !== 'calificado') {
                    $entrega->fill([
                        'estado'        => 'entregado',
                        'fecha_entrega' => now(),
                        'contenido'     => "Quiz completado — {$mejor}/{$puntajeMax} pts autocorregidos; "
                                         . 'tiene preguntas abiertas pendientes de revisión del docente.',
                    ])->save();
                }
            } else {
                $entrega->fill([
                    'calificacion'   => $nota,
                    'estado'         => 'calificado',
                    'fecha_entrega'  => $entrega->fecha_entrega ?? now(),
                    'fecha_revision' => now(),
                    'contenido'      => "Quiz completado — {$mejor}/{$puntajeMax} pts",
                ])->save();

                if ($material->periodo_id) {
                    app(ZuraClassGradeSync::class)->sincronizar($entrega->fresh());
                }
            }
        }

        $claseVirtual = $intento->quiz->material->claseVirtual;
        return redirect()->route('portal.estudiante.classroom.quiz.resultado', [
            $claseVirtual,
            $intento->quiz->material,
            $intento,
        ])->with('success', 'Quiz completado.');
    }

    // ── Pantalla de resultados ───────────────────────────────────────────
    public function resultado(ClaseVirtual $claseVirtual, MaterialClase $material, ZcIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_unless($intento->matricula_id === $matricula->id, 403);

        $quiz = $intento->quiz()->with([
            'preguntas.opciones',
            'preguntas.respuestas' => fn($q) => $q->where('intento_id', $intento->id),
        ])->first();

        $respuestas = ZcRespuesta::where('intento_id', $intento->id)
            ->get()->keyBy('pregunta_id');

        return view('portal.classroom.estudiante.quiz_resultado', compact(
            'claseVirtual', 'material', 'quiz', 'intento', 'matricula', 'respuestas'
        ));
    }
}
