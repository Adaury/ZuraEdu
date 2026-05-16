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
use Illuminate\Http\Request;

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

        return view('portal.docente.evaluaciones.resultados', compact(
            'asignacion', 'quiz', 'matriculas', 'mejores', 'stats', 'analisisPregunta', 'puntajeTotal'
        ));
    }
}
