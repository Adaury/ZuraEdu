<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\EvaIntento;
use App\Models\EvaQuiz;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class EvaQuizEstudianteController extends Controller
{
    private function getMatricula(): Matricula
    {
        $user       = auth()->user();
        $estudiante = $user->estudiante;
        abort_if(! $estudiante, 403);

        $schoolYear = SchoolYear::actual();
        $matricula  = Matricula::where('estudiante_id', $estudiante->id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->firstOrFail();

        return $matricula;
    }

    // ── Lista de quizzes disponibles para el estudiante ──────────────────────
    public function index()
    {
        $matricula = $this->getMatricula();

        $asignacionIds = \App\Models\Asignacion::where('grupo_id', $matricula->grupo_id)->pluck('id');

        $quizzes = EvaQuiz::whereIn('asignacion_id', $asignacionIds)
            ->where('publicado', true)
            ->with('asignacion.asignatura')
            ->withCount('preguntas')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($quiz) use ($matricula) {
                $intento = $quiz->intentoFinalizado($matricula->id);
                $activo  = $quiz->intentoActivo($matricula->id);
                return [
                    'quiz'       => $quiz,
                    'intento'    => $intento,
                    'activo'     => $activo,
                    'puede'      => $quiz->puedeIntentar($matricula->id),
                    'disponible' => $quiz->estaDisponible(),
                ];
            });

        return view('portal.estudiante.evaluaciones', compact('matricula', 'quizzes'));
    }

    // ── Detalle de un quiz (antes de iniciar) ────────────────────────────────
    public function show(EvaQuiz $quiz)
    {
        $matricula = $this->getMatricula();

        abort_unless(
            \App\Models\Asignacion::where('id', $quiz->asignacion_id)
                ->where('grupo_id', $matricula->grupo_id)
                ->exists(),
            403
        );

        $intentoPrevio = $quiz->intentoFinalizado($matricula->id);
        $intentoActivo = $quiz->intentoActivo($matricula->id);
        $puede         = $quiz->puedeIntentar($matricula->id);
        $disponible    = $quiz->estaDisponible();
        $quiz->loadCount('preguntas');

        return view('portal.estudiante.evaluacion_detalle', compact(
            'quiz', 'matricula', 'intentoPrevio', 'intentoActivo', 'puede', 'disponible'
        ));
    }

    // ── Iniciar un nuevo intento ─────────────────────────────────────────────
    public function iniciar(EvaQuiz $quiz)
    {
        $matricula = $this->getMatricula();

        abort_unless(
            \App\Models\Asignacion::where('id', $quiz->asignacion_id)
                ->where('grupo_id', $matricula->grupo_id)
                ->exists(),
            403
        );

        if (! $quiz->estaDisponible()) {
            return back()->with('error', 'Esta evaluación no está disponible en este momento.');
        }

        // Reutilizar intento en curso si existe
        $intento = $quiz->intentoActivo($matricula->id);
        if (! $intento) {
            if (! $quiz->puedeIntentar($matricula->id)) {
                return back()->with('error', 'Has alcanzado el número máximo de intentos.');
            }

            $preguntas = $quiz->preguntas;
            if ($quiz->aleatorizar) {
                $preguntas = $preguntas->shuffle();
            }

            $intento = EvaIntento::create([
                'quiz_id'        => $quiz->id,
                'matricula_id'   => $matricula->id,
                'estado'         => 'en_curso',
                'puntuacion_max' => $quiz->puntaje_total,
                'iniciado_en'    => now(),
                'respuestas'     => [],
            ]);
        }

        return redirect()->route('portal.estudiante.evaluaciones.tomar', $intento);
    }

    // ── Tomar el quiz ────────────────────────────────────────────────────────
    public function tomar(EvaIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_if($intento->matricula_id !== $matricula->id, 403);
        abort_if($intento->estado === 'finalizado', 302, route('portal.estudiante.evaluaciones.resultado', $intento));

        if ($intento->estado === 'finalizado') {
            return redirect()->route('portal.estudiante.evaluaciones.resultado', $intento);
        }

        $quiz = $intento->quiz()->with('preguntas')->first();

        // Verificar tiempo agotado
        if ($quiz->duracion_minutos && $intento->iniciado_en) {
            $limite = $intento->iniciado_en->copy()->addMinutes($quiz->duracion_minutos);
            if (now()->gt($limite)) {
                return $this->finalizarIntento($intento, $quiz);
            }
        }

        $segundosRestantes = null;
        if ($quiz->duracion_minutos && $intento->iniciado_en) {
            $transcurridos     = $intento->iniciado_en->diffInSeconds(now());
            $segundosRestantes = max(0, $quiz->duracion_minutos * 60 - $transcurridos);
        }

        return view('portal.estudiante.evaluacion_tomar', compact(
            'intento', 'quiz', 'matricula', 'segundosRestantes'
        ));
    }

    // ── AJAX: guardar respuesta individual ───────────────────────────────────
    public function guardarRespuesta(Request $request, EvaIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_if($intento->matricula_id !== $matricula->id || $intento->estado !== 'en_curso', 403);

        $data = $request->validate([
            'pregunta_id' => 'required|integer',
            'respuesta'   => 'nullable',
        ]);

        $pregunta = \App\Models\EvaPregunta::findOrFail($data['pregunta_id']);
        abort_if($pregunta->quiz_id !== $intento->quiz_id, 403);

        $respuestas = $intento->respuestas ?? [];
        $esCorrecta = $pregunta->esRespuestaCorrecta($data['respuesta']);

        $respuestas[$data['pregunta_id']] = [
            'valor'    => $data['respuesta'],
            'correcta' => $esCorrecta,
            'puntos'   => $esCorrecta ? $pregunta->puntos : 0,
        ];

        $intento->update(['respuestas' => $respuestas]);

        return response()->json(['ok' => true]);
    }

    // ── Enviar / finalizar el intento ────────────────────────────────────────
    public function enviar(Request $request, EvaIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_if($intento->matricula_id !== $matricula->id, 403);

        if ($intento->estado === 'finalizado') {
            return redirect()->route('portal.estudiante.evaluaciones.resultado', $intento);
        }

        $quiz = $intento->quiz()->with('preguntas')->first();
        return $this->finalizarIntento($intento, $quiz);
    }

    private function finalizarIntento(EvaIntento $intento, EvaQuiz $quiz)
    {
        $respuestas  = $intento->respuestas ?? [];
        $puntuacion  = 0;

        foreach ($quiz->preguntas as $p) {
            $resp = $respuestas[$p->id] ?? null;
            if ($resp && ($resp['correcta'] ?? false)) {
                $puntuacion += $p->puntos;
            }
        }

        $intento->update([
            'estado'        => 'finalizado',
            'puntuacion'    => $puntuacion,
            'puntuacion_max'=> $quiz->puntaje_total,
            'finalizado_en' => now(),
        ]);

        return redirect()->route('portal.estudiante.evaluaciones.resultado', $intento);
    }

    // ── Ver resultado del intento ────────────────────────────────────────────
    public function resultado(EvaIntento $intento)
    {
        $matricula = $this->getMatricula();
        abort_if($intento->matricula_id !== $matricula->id, 403);
        abort_if($intento->estado !== 'finalizado', 404);

        $quiz = $intento->quiz()->with('preguntas')->first();

        return view('portal.estudiante.evaluacion_resultado', compact(
            'intento', 'quiz', 'matricula'
        ));
    }
}
