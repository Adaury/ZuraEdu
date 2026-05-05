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

    // ── Pantalla de inicio del quiz ──────────────────────────────────────
    public function iniciar(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $matricula = $this->getMatricula();

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        abort_unless($claseVirtual->asignacion->grupo_id === $matricula->grupo_id, 403);

        $quiz = $material->quiz()->with('preguntas')->firstOrFail();

        // Verificar si puede intentar
        if (!$quiz->puedeIntentar($matricula->id)) {
            return back()->with('error', "Ya alcanzaste el máximo de {$quiz->intentos_max} intento(s).");
        }

        // Si hay intento activo, redirigir a él
        $intentoActivo = $quiz->intentoActivo($matricula->id);
        if ($intentoActivo) {
            return redirect()->route('portal.estudiante.quiz.tomar', [$claseVirtual, $material, $intentoActivo]);
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

        $quiz = $material->quiz()->with('preguntas.opciones')->firstOrFail();

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

        return redirect()->route('portal.estudiante.quiz.tomar', [$claseVirtual, $material, $intento]);
    }

    // ── Pantalla de tomar el quiz ────────────────────────────────────────
    public function tomar(ClaseVirtual $claseVirtual, MaterialClase $material, ZcIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_unless($intento->matricula_id === $matricula->id, 403);
        abort_unless($intento->estado === 'en_curso', 422, 'Este intento ya está finalizado.');

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
                // Tiempo expirado — finalizar automáticamente
                return $this->finalizarIntento($intento, $quiz, $matricula);
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
            'pregunta_id'    => 'required|exists:zc_preguntas,id',
            'opcion_id'      => 'nullable|exists:zc_opciones,id',
            'texto_respuesta'=> 'nullable|string|max:3000',
        ]);

        ZcRespuesta::updateOrCreate(
            ['intento_id' => $intento->id, 'pregunta_id' => $request->pregunta_id],
            ['opcion_id' => $request->opcion_id, 'texto_respuesta' => $request->texto_respuesta]
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

        // Guardar respuestas del formulario (fallback si no usaron AJAX)
        foreach ($request->all() as $key => $valor) {
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

        // Sincronizar nota con libro de notas si tiene período asignado
        if ($material && $quiz->autocorreccion) {
            $material_model = $material instanceof MaterialClase ? $material : MaterialClase::find($material);
            if ($material_model) {
                $puntajeMax = $quiz->puntaje_total ?: 100;
                $notaSobre100 = round(($puntuacion / $puntajeMax) * ($material_model->puntos ?? 100), 2);

                \App\Models\EntregaClassroom::updateOrCreate(
                    ['material_id' => $material_model->id, 'matricula_id' => $matricula->id],
                    [
                        'calificacion'  => $notaSobre100,
                        'estado'        => 'calificado',
                        'fecha_entrega' => now(),
                        'fecha_revision'=> now(),
                        'contenido'     => "Quiz completado — {$puntuacion}/{$puntajeMax} pts",
                    ]
                );

                if ($material_model->periodo_id) {
                    app(ZuraClassGradeSync::class)->sincronizar(
                        \App\Models\EntregaClassroom::where('material_id', $material_model->id)
                            ->where('matricula_id', $matricula->id)->first()
                    );
                }
            }
        }

        $claseVirtual = $intento->quiz->material->claseVirtual;
        return redirect()->route('portal.estudiante.quiz.resultado', [
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
