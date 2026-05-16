<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\BancoPregunta;
use App\Models\EvaPregunta;
use App\Models\EvaQuiz;
use App\Traits\HasDocenteContext;
use Illuminate\Http\Request;

class BancoPreguntasController extends Controller
{
    use HasDocenteContext;

    // ── Lista / filtros ──────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $docente = $this->getDocente();

        $query = BancoPregunta::where('docente_id', $docente->id)
            ->with('asignatura')
            ->orderByDesc('updated_at');

        if ($request->filled('asignatura_id')) {
            $query->where('asignatura_id', $request->asignatura_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('q')) {
            $query->where('enunciado', 'like', '%' . $request->q . '%');
        }

        $preguntas = $query->paginate(20)->withQueryString();

        // Para los filtros
        $asignaturas = Asignatura::whereHas('asignaciones', fn($q) => $q->where('docente_id', $docente->id))
            ->orderBy('nombre')->get();

        $categorias = BancoPregunta::where('docente_id', $docente->id)
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        $totalBanco = BancoPregunta::where('docente_id', $docente->id)->count();

        return view('portal.docente.banco_preguntas', compact(
            'preguntas', 'asignaturas', 'categorias', 'totalBanco'
        ));
    }

    // ── Crear pregunta ───────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $docente = $this->getDocente();

        $data = $request->validate([
            'enunciado'      => 'required|string|max:2000',
            'tipo'           => 'required|in:multiple,verdadero_falso,abierta',
            'asignatura_id'  => 'nullable|exists:asignaturas,id',
            'puntos_default' => 'required|numeric|min:0.5|max:100',
            'explicacion'    => 'nullable|string|max:500',
            'categoria'      => 'nullable|string|max:100',
            'opciones'       => 'nullable|array',
            'opciones.*.texto'   => 'required_unless:tipo,abierta|string|max:300',
            'opciones.*.correcta'=> 'boolean',
        ]);

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

        $pregunta = BancoPregunta::create([
            'docente_id'     => $docente->id,
            'asignatura_id'  => $data['asignatura_id'] ?? null,
            'enunciado'      => $data['enunciado'],
            'tipo'           => $data['tipo'],
            'opciones'       => $opciones,
            'puntos_default' => $data['puntos_default'],
            'explicacion'    => $data['explicacion'] ?? null,
            'categoria'      => $data['categoria'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pregunta' => $pregunta->load('asignatura')]);
        }

        return back()->with('success', 'Pregunta guardada en el banco.');
    }

    // ── Actualizar ───────────────────────────────────────────────────────────
    public function update(Request $request, BancoPregunta $bancoPregunta)
    {
        $docente = $this->getDocente();
        abort_if($bancoPregunta->docente_id !== $docente->id, 403);

        $data = $request->validate([
            'enunciado'      => 'required|string|max:2000',
            'tipo'           => 'required|in:multiple,verdadero_falso,abierta',
            'asignatura_id'  => 'nullable|exists:asignaturas,id',
            'puntos_default' => 'required|numeric|min:0.5|max:100',
            'explicacion'    => 'nullable|string|max:500',
            'categoria'      => 'nullable|string|max:100',
            'opciones'       => 'nullable|array',
            'opciones.*.texto'   => 'required_unless:tipo,abierta|string|max:300',
            'opciones.*.correcta'=> 'boolean',
        ]);

        $opciones = $bancoPregunta->opciones;
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
        } elseif ($data['tipo'] === 'abierta') {
            $opciones = null;
        }

        $bancoPregunta->update([
            'asignatura_id'  => $data['asignatura_id'] ?? null,
            'enunciado'      => $data['enunciado'],
            'tipo'           => $data['tipo'],
            'opciones'       => $opciones,
            'puntos_default' => $data['puntos_default'],
            'explicacion'    => $data['explicacion'] ?? null,
            'categoria'      => $data['categoria'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Pregunta actualizada.');
    }

    // ── Eliminar ─────────────────────────────────────────────────────────────
    public function destroy(BancoPregunta $bancoPregunta)
    {
        $docente = $this->getDocente();
        abort_if($bancoPregunta->docente_id !== $docente->id, 403);

        $bancoPregunta->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Pregunta eliminada del banco.');
    }

    // ── AJAX: obtener preguntas para selector en quiz ────────────────────────
    public function listar(Request $request)
    {
        $docente = $this->getDocente();

        $query = BancoPregunta::where('docente_id', $docente->id)
            ->with('asignatura');

        if ($request->filled('asignatura_id')) {
            $query->where('asignatura_id', $request->asignatura_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('q')) {
            $query->where('enunciado', 'like', '%' . $request->q . '%');
        }

        $preguntas = $query->orderByDesc('updated_at')->limit(50)->get();

        return response()->json($preguntas);
    }

    // ── Importar selección al quiz ───────────────────────────────────────────
    public function importarAlQuiz(Request $request, EvaQuiz $quiz)
    {
        $docente = $this->getDocente();
        abort_if($quiz->asignacion->docente_id !== $docente->id, 403);

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:banco_preguntas,id',
        ]);

        $bancoPreguntasList = BancoPregunta::whereIn('id', $data['ids'])
            ->where('docente_id', $docente->id)
            ->get();

        $orden = $quiz->preguntas()->max('orden') ?? 0;
        $importadas = 0;

        foreach ($bancoPreguntasList as $bp) {
            $orden++;
            EvaPregunta::create([
                'quiz_id'     => $quiz->id,
                'orden'       => $orden,
                'enunciado'   => $bp->enunciado,
                'tipo'        => $bp->tipo,
                'opciones'    => $bp->opciones,
                'puntos'      => $bp->puntos_default,
                'explicacion' => $bp->explicacion,
            ]);
            $bp->increment('usos');
            $importadas++;
        }

        return response()->json([
            'ok'         => true,
            'importadas' => $importadas,
            'total'      => $quiz->preguntas()->count(),
        ]);
    }
}
