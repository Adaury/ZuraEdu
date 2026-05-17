<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\EvaIntento;
use App\Models\EvaPregunta;
use App\Models\EvaQuiz;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Traits\HasDocenteContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EvaQuizDocenteController extends Controller
{
    use HasDocenteContext;

    private function autorizar(Asignacion $asignacion): void
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
    }

    // ── Lista de quizzes de una asignación ───────────────────────────────────
    public function index(Asignacion $asignacion)
    {
        $this->autorizar($asignacion);

        $quizzes = EvaQuiz::where('asignacion_id', $asignacion->id)
            ->withCount('preguntas')
            ->withCount('intentos')
            ->orderByDesc('created_at')
            ->get();

        $schoolYear = SchoolYear::actual();
        $totalEst   = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        return view('portal.docente.evaluaciones.index', compact(
            'asignacion', 'quizzes', 'totalEst'
        ));
    }

    // ── Crear quiz ───────────────────────────────────────────────────────────
    public function store(Request $request, Asignacion $asignacion)
    {
        $this->autorizar($asignacion);

        $data = $request->validate([
            'titulo'           => 'required|string|max:200',
            'instrucciones'    => 'nullable|string|max:2000',
            'duracion_minutos' => 'nullable|integer|min:1|max:300',
            'intentos_max'     => 'required|integer|min:1|max:10',
            'mostrar_resultados'=> 'boolean',
            'aleatorizar'      => 'boolean',
            'disponible_desde' => 'nullable|date',
            'disponible_hasta' => 'nullable|date|after_or_equal:disponible_desde',
        ]);

        $quiz = EvaQuiz::create([
            'asignacion_id'    => $asignacion->id,
            'titulo'           => $data['titulo'],
            'instrucciones'    => $data['instrucciones'] ?? null,
            'duracion_minutos' => $data['duracion_minutos'] ?? null,
            'intentos_max'     => $data['intentos_max'],
            'mostrar_resultados'=> $request->boolean('mostrar_resultados', true),
            'aleatorizar'      => $request->boolean('aleatorizar'),
            'disponible_desde' => $data['disponible_desde'] ?? null,
            'disponible_hasta' => $data['disponible_hasta'] ?? null,
            'publicado'        => false,
        ]);

        return redirect()->route('portal.docente.evaluaciones.show', [$asignacion, $quiz])
            ->with('success', 'Evaluación creada. Ahora agrega las preguntas.');
    }

    // ── Constructor / editor de preguntas ────────────────────────────────────
    public function show(Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $quiz->load('preguntas');

        $schoolYear = SchoolYear::actual();
        $totalEst   = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        $intentosCount = EvaIntento::where('quiz_id', $quiz->id)->where('estado', 'finalizado')->count();

        return view('portal.docente.evaluaciones.show', compact(
            'asignacion', 'quiz', 'totalEst', 'intentosCount'
        ));
    }

    // ── Actualizar configuración del quiz ────────────────────────────────────
    public function update(Request $request, Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $data = $request->validate([
            'titulo'           => 'required|string|max:200',
            'instrucciones'    => 'nullable|string|max:2000',
            'duracion_minutos' => 'nullable|integer|min:1|max:300',
            'intentos_max'     => 'required|integer|min:1|max:10',
            'mostrar_resultados'=> 'boolean',
            'aleatorizar'      => 'boolean',
            'disponible_desde' => 'nullable|date',
            'disponible_hasta' => 'nullable|date|after_or_equal:disponible_desde',
        ]);

        $quiz->update([
            'titulo'           => $data['titulo'],
            'instrucciones'    => $data['instrucciones'] ?? null,
            'duracion_minutos' => $data['duracion_minutos'] ?? null,
            'intentos_max'     => $data['intentos_max'],
            'mostrar_resultados'=> $request->boolean('mostrar_resultados', true),
            'aleatorizar'      => $request->boolean('aleatorizar'),
            'disponible_desde' => $data['disponible_desde'] ?? null,
            'disponible_hasta' => $data['disponible_hasta'] ?? null,
        ]);

        return back()->with('success', 'Configuración actualizada.');
    }

    // ── Publicar / despublicar ───────────────────────────────────────────────
    public function togglePublicado(Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        if (! $quiz->publicado && $quiz->preguntas()->count() === 0) {
            return back()->with('error', 'Debes agregar al menos una pregunta antes de publicar.');
        }

        $quiz->update(['publicado' => ! $quiz->publicado]);
        $msg = $quiz->publicado ? 'Evaluación publicada. Los estudiantes ya pueden acceder.' : 'Evaluación despublicada.';
        return back()->with('success', $msg);
    }

    // ── Eliminar quiz ────────────────────────────────────────────────────────
    public function destroy(Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $quiz->delete();
        return redirect()->route('portal.docente.evaluaciones.index', $asignacion)
            ->with('success', 'Evaluación eliminada.');
    }

    // ── AJAX: agregar pregunta ───────────────────────────────────────────────
    public function storePregunta(Request $request, Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $data = $request->validate([
            'enunciado'   => 'required|string|max:1000',
            'tipo'        => 'required|in:multiple,verdadero_falso,abierta',
            'puntos'      => 'required|numeric|min:0.5|max:100',
            'explicacion' => 'nullable|string|max:500',
            'opciones'    => 'nullable|array',
            'opciones.*.texto'   => 'required_unless:tipo,abierta|string|max:300',
            'opciones.*.correcta'=> 'boolean',
        ]);

        $orden = $quiz->preguntas()->max('orden') + 1;

        $opciones = null;
        if ($data['tipo'] === 'verdadero_falso') {
            $correcta = $request->input('correcta_vf', 'V') === 'V';
            $opciones = [
                ['texto' => 'Verdadero', 'correcta' => $correcta],
                ['texto' => 'Falso',     'correcta' => ! $correcta],
            ];
        } elseif ($data['tipo'] === 'multiple' && ! empty($data['opciones'])) {
            $opciones = collect($data['opciones'])
                ->filter(fn($o) => ! empty($o['texto']))
                ->values()
                ->map(fn($o) => ['texto' => $o['texto'], 'correcta' => ! empty($o['correcta'])])
                ->toArray();
        }

        $pregunta = EvaPregunta::create([
            'quiz_id'     => $quiz->id,
            'orden'       => $orden,
            'enunciado'   => $data['enunciado'],
            'tipo'        => $data['tipo'],
            'opciones'    => $opciones,
            'puntos'      => $data['puntos'],
            'explicacion' => $data['explicacion'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pregunta' => $pregunta, 'total' => $quiz->preguntas()->count()]);
        }

        return back()->with('success', 'Pregunta agregada.');
    }

    // ── AJAX: eliminar pregunta ──────────────────────────────────────────────
    public function destroyPregunta(Asignacion $asignacion, EvaQuiz $quiz, EvaPregunta $pregunta)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id || $pregunta->quiz_id !== $quiz->id, 404);

        $pregunta->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'total' => $quiz->preguntas()->count()]);
        }

        return back()->with('success', 'Pregunta eliminada.');
    }

    // ── Resultados del quiz ──────────────────────────────────────────────────
    public function resultados(Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $quiz->load('preguntas');

        $schoolYear = SchoolYear::actual();
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $intentos = EvaIntento::where('quiz_id', $quiz->id)
            ->where('estado', 'finalizado')
            ->with('matricula.estudiante')
            ->get();

        // Mejor intento por estudiante
        $mejores = $intentos->groupBy('matricula_id')
            ->map(fn($g) => $g->sortByDesc('puntuacion')->first());

        $puntajeTotal = $quiz->puntaje_total;

        $stats = [
            'completaron'  => $mejores->count(),
            'pendientes'   => $matriculas->count() - $mejores->count(),
            'promedio'     => $mejores->count() ? round($mejores->avg('porcentaje'), 1) : null,
            'aprobados'    => $mejores->filter(fn($i) => $i->porcentaje >= 60)->count(),
            'puntajeTotal' => $puntajeTotal,
        ];

        // Análisis por pregunta
        $analisisPregunta = $quiz->preguntas->map(function ($p) use ($intentos) {
            $total     = $intentos->count();
            $correctas = 0;
            foreach ($intentos as $intento) {
                $resp = ($intento->respuestas ?? [])[$p->id] ?? null;
                if ($resp && ($resp['correcta'] ?? false)) $correctas++;
            }
            return [
                'pregunta'   => $p,
                'total'      => $total,
                'correctas'  => $correctas,
                'pct'        => $total > 0 ? round($correctas / $total * 100) : null,
            ];
        });

        // Detectar si hay preguntas abiertas pendientes de revisión
        $tieneAbiertas = $quiz->preguntas->where('tipo', 'abierta')->isNotEmpty();
        $pendientesRevision = 0;
        if ($tieneAbiertas) {
            foreach ($mejores as $intento) {
                foreach ($quiz->preguntas->where('tipo', 'abierta') as $p) {
                    $resp = ($intento->respuestas ?? [])[$p->id] ?? null;
                    if ($resp && ($resp['valor'] ?? '') !== '' && ($resp['puntos'] ?? 0) == 0) {
                        $pendientesRevision++;
                    }
                }
            }
        }

        return view('portal.docente.evaluaciones.resultados', compact(
            'asignacion', 'quiz', 'matriculas', 'mejores', 'stats',
            'analisisPregunta', 'puntajeTotal', 'pendientesRevision'
        ));
    }

    // ── Ver intento individual (docente) ─────────────────────────────────────
    public function verIntento(Asignacion $asignacion, EvaQuiz $quiz, EvaIntento $intento)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id || $intento->quiz_id !== $quiz->id, 404);
        abort_if($intento->estado !== 'finalizado', 404);

        $quiz->load('preguntas');
        $intento->load('matricula.estudiante');

        $pendientesRevision = 0;
        foreach ($quiz->preguntas->where('tipo', 'abierta') as $p) {
            $resp = ($intento->respuestas ?? [])[$p->id] ?? null;
            if ($resp && ($resp['valor'] ?? '') !== '' && ($resp['puntos'] ?? 0) == 0) {
                $pendientesRevision++;
            }
        }

        return view('portal.docente.evaluaciones.intento', compact(
            'asignacion', 'quiz', 'intento', 'pendientesRevision'
        ));
    }

    // ── AJAX: calificar pregunta abierta ──────────────────────────────────────
    public function calificarAbierta(Request $request, Asignacion $asignacion, EvaQuiz $quiz, EvaIntento $intento)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id || $intento->quiz_id !== $quiz->id, 404);

        $pregunta = EvaPregunta::where('quiz_id', $quiz->id)->findOrFail($request->input('pregunta_id'));

        $request->validate([
            'pregunta_id' => 'required|integer',
            'puntos'      => "required|numeric|min:0|max:{$pregunta->puntos}",
        ]);

        $puntos = (float) $request->input('puntos');
        $respuestas = $intento->respuestas ?? [];

        if (! isset($respuestas[$pregunta->id])) {
            $respuestas[$pregunta->id] = ['valor' => '', 'correcta' => false, 'puntos' => 0];
        }
        $respuestas[$pregunta->id]['puntos']   = $puntos;
        $respuestas[$pregunta->id]['correcta'] = $puntos > 0;

        $puntuacionTotal = collect($respuestas)->sum('puntos');

        $intento->update([
            'respuestas' => $respuestas,
            'puntuacion' => $puntuacionTotal,
        ]);

        return response()->json([
            'ok'         => true,
            'puntuacion' => $puntuacionTotal,
            'porcentaje' => $intento->porcentaje,
        ]);
    }

    // ── Generar examen imprimible PDF ────────────────────────────────────────
    public function examenPdf(Request $request, Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $quiz->load('preguntas');
        $preguntas = $quiz->preguntas->sortBy('orden')->values();

        $versionA = $preguntas;
        $versionB = $this->shuffleParaVersionB($preguntas);

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        $claveA = $this->buildAnswerKey($versionA);
        $claveB = $this->buildAnswerKey($versionB);

        $pdf = Pdf::loadView('portal.docente.evaluaciones.examen_pdf', [
            'asignacion' => $asignacion,
            'quiz'       => $quiz,
            'versionA'   => $versionA,
            'versionB'   => $versionB,
            'claveA'     => $claveA,
            'claveB'     => $claveB,
            'tenant'     => $tenant,
        ])->setPaper('letter', 'portrait');

        return $pdf->download('examen-' . Str::slug($quiz->titulo) . '.pdf');
    }

    private function shuffleParaVersionB($preguntas)
    {
        $shuffled = $preguntas->shuffle()->values();
        return $shuffled->map(function ($p) {
            if ($p->tipo === 'multiple' && $p->opciones) {
                $clone          = clone $p;
                $clone->opciones = collect($p->opciones)->shuffle()->values()->toArray();
                return $clone;
            }
            return $p;
        });
    }

    private function buildAnswerKey($preguntas): array
    {
        // Mismo orden que el examen: MC → VF → Abierta
        $grouped = collect([
            ...$preguntas->where('tipo', 'multiple')->values()->all(),
            ...$preguntas->where('tipo', 'verdadero_falso')->values()->all(),
            ...$preguntas->where('tipo', 'abierta')->values()->all(),
        ]);

        $key = [];
        foreach ($grouped as $idx => $p) {
            $num = $idx + 1;
            if ($p->tipo === 'verdadero_falso') {
                $correcta  = collect($p->opciones ?? [])->firstWhere('correcta', true);
                $key[$num] = $correcta ? ($correcta['texto'] === 'Verdadero' ? 'V' : 'F') : '?';
            } elseif ($p->tipo === 'multiple') {
                $correctIdx = collect($p->opciones ?? [])->search(fn($o) => $o['correcta'] ?? false);
                $key[$num]  = $correctIdx !== false ? chr(65 + $correctIdx) : '?';
            } else {
                $key[$num] = 'Desarrollo';
            }
        }
        return $key;
    }

    // ── PDF de resultados ─────────────────────────────────────────────────────
    public function resultadosPdf(Asignacion $asignacion, EvaQuiz $quiz)
    {
        $this->autorizar($asignacion);
        abort_if($quiz->asignacion_id !== $asignacion->id, 404);

        $quiz->load('preguntas');

        $schoolYear = SchoolYear::actual();
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $intentos = EvaIntento::where('quiz_id', $quiz->id)
            ->where('estado', 'finalizado')
            ->get();

        $mejores = $intentos->groupBy('matricula_id')
            ->map(fn($g) => $g->sortByDesc('puntuacion')->first());

        $puntajeTotal = $quiz->puntaje_total;

        $stats = [
            'completaron'  => $mejores->count(),
            'pendientes'   => $matriculas->count() - $mejores->count(),
            'promedio'     => $mejores->count() ? round($mejores->avg('porcentaje'), 1) : null,
            'aprobados'    => $mejores->filter(fn($i) => $i->porcentaje >= 60)->count(),
            'puntajeTotal' => $puntajeTotal,
        ];

        $analisisPregunta = $quiz->preguntas->map(function ($p) use ($intentos) {
            $total     = $intentos->count();
            $correctas = 0;
            foreach ($intentos as $intento) {
                $resp = ($intento->respuestas ?? [])[$p->id] ?? null;
                if ($resp && ($resp['correcta'] ?? false)) $correctas++;
            }
            return [
                'pregunta'  => $p,
                'total'     => $total,
                'correctas' => $correctas,
                'pct'       => $total > 0 ? round($correctas / $total * 100) : null,
            ];
        });

        $pdf = Pdf::loadView('portal.docente.evaluaciones.resultados_pdf', compact(
            'asignacion', 'quiz', 'matriculas', 'mejores', 'stats', 'analisisPregunta', 'puntajeTotal'
        ))->setPaper('letter', 'landscape');

        return $pdf->download('resultados-' . Str::slug($quiz->titulo) . '.pdf');
    }
}
