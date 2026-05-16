<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Rubrica;
use App\Models\RubricaAplicacion;
use App\Models\SchoolYear;
use App\Traits\HasDocenteContext;
use Illuminate\Http\Request;

class RubricaDocenteController extends Controller
{
    use HasDocenteContext;

    private function autorizarAsignacion(Asignacion $asignacion): void
    {
        if ($asignacion->docente_id !== $this->getDocente()->id) abort(403);
    }

    private function autorizarRubrica(Rubrica $rubrica): void
    {
        if ($rubrica->docente_id !== $this->getDocente()->id) abort(403);
    }

    // ── Lista de rúbricas del docente ────────────────────────────────────────
    public function index()
    {
        $docente = $this->getDocente();

        $rubricas = Rubrica::where('docente_id', $docente->id)
            ->with('asignatura')
            ->withCount('aplicaciones')
            ->orderByDesc('updated_at')
            ->get();

        return view('portal.docente.rubricas.index', compact('rubricas'));
    }

    // ── Crear rúbrica ────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $docente = $this->getDocente();

        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'descripcion'  => 'nullable|string|max:1000',
            'asignatura_id'=> 'nullable|exists:asignaturas,id',
        ]);

        $rubrica = Rubrica::create([
            'docente_id'    => $docente->id,
            'asignatura_id' => $data['asignatura_id'] ?? null,
            'titulo'        => $data['titulo'],
            'descripcion'   => $data['descripcion'] ?? null,
            'niveles' => [
                ['nombre' => 'Insuficiente', 'pct' => 0,   'color' => '#ef4444'],
                ['nombre' => 'En proceso',   'pct' => 50,  'color' => '#f59e0b'],
                ['nombre' => 'Logrado',      'pct' => 75,  'color' => '#3b82f6'],
                ['nombre' => 'Excelente',    'pct' => 100, 'color' => '#10b981'],
            ],
            'criterios' => [
                ['nombre' => 'Criterio 1', 'puntos' => 25, 'descriptores' => ['', '', '', '']],
                ['nombre' => 'Criterio 2', 'puntos' => 25, 'descriptores' => ['', '', '', '']],
                ['nombre' => 'Criterio 3', 'puntos' => 25, 'descriptores' => ['', '', '', '']],
                ['nombre' => 'Criterio 4', 'puntos' => 25, 'descriptores' => ['', '', '', '']],
            ],
        ]);

        return redirect()->route('portal.docente.rubricas.show', $rubrica)
            ->with('success', 'Rúbrica creada. Personaliza los criterios y niveles.');
    }

    // ── Constructor de rúbrica ───────────────────────────────────────────────
    public function show(Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);

        $docente     = $this->getDocente();
        $asignaturas = \App\Models\Asignatura::whereHas('asignaciones', fn($q) => $q->where('docente_id', $docente->id))
            ->orderBy('nombre')->get();

        $asignaciones = Asignacion::where('docente_id', $docente->id)
            ->with(['asignatura', 'grupo'])
            ->get();

        return view('portal.docente.rubricas.show', compact('rubrica', 'asignaturas', 'asignaciones'));
    }

    // ── Guardar cambios a la rúbrica (título, niveles, criterios) ────────────
    public function update(Request $request, Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);

        $data = $request->validate([
            'titulo'        => 'required|string|max:200',
            'descripcion'   => 'nullable|string|max:1000',
            'asignatura_id' => 'nullable|exists:asignaturas,id',
            'niveles'       => 'required|array|min:2|max:6',
            'niveles.*.nombre' => 'required|string|max:60',
            'niveles.*.pct'    => 'required|integer|min:0|max:100',
            'niveles.*.color'  => 'required|string|max:20',
            'criterios'        => 'required|array|min:1',
            'criterios.*.nombre'=> 'required|string|max:200',
            'criterios.*.puntos'=> 'required|numeric|min:1|max:1000',
            'criterios.*.descriptores' => 'nullable|array',
        ]);

        $rubrica->update([
            'titulo'        => $data['titulo'],
            'descripcion'   => $data['descripcion'] ?? null,
            'asignatura_id' => $data['asignatura_id'] ?? null,
            'niveles'       => $data['niveles'],
            'criterios'     => $data['criterios'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'puntaje_max' => $rubrica->puntaje_max]);
        }

        return back()->with('success', 'Rúbrica actualizada.');
    }

    // ── Eliminar ─────────────────────────────────────────────────────────────
    public function destroy(Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);
        $rubrica->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('portal.docente.rubricas.index')
            ->with('success', 'Rúbrica eliminada.');
    }

    // ── Aplicar rúbrica a estudiantes de una asignación ──────────────────────
    public function aplicar(Request $request, Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);

        $docente      = $this->getDocente();
        $asignacionId = $request->input('asignacion_id');

        $asignaciones = Asignacion::where('docente_id', $docente->id)
            ->with(['asignatura', 'grupo'])
            ->get();

        $asignacion = $asignacionId
            ? Asignacion::where('id', $asignacionId)->where('docente_id', $docente->id)->firstOrFail()
            : null;

        $matriculas    = collect();
        $aplicaciones  = collect();

        if ($asignacion) {
            $schoolYear = SchoolYear::actual();
            $matriculas = Matricula::with('estudiante')
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $aplicaciones = RubricaAplicacion::where('rubrica_id', $rubrica->id)
                ->where('asignacion_id', $asignacion->id)
                ->get()
                ->keyBy('matricula_id');
        }

        return view('portal.docente.rubricas.aplicar', compact(
            'rubrica', 'asignaciones', 'asignacion', 'matriculas', 'aplicaciones'
        ));
    }

    // ── Guardar aplicación de un estudiante (AJAX) ───────────────────────────
    public function guardarAplicacion(Request $request, Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);

        $data = $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'matricula_id'  => 'required|exists:matriculas,id',
            'resultados'    => 'required|array',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        // Verificar que la asignación pertenece al docente
        $asignacion = Asignacion::where('id', $data['asignacion_id'])
            ->where('docente_id', $this->getDocente()->id)
            ->firstOrFail();

        $puntaje    = $rubrica->calcularPuntaje($data['resultados']);
        $puntajeMax = $rubrica->puntaje_max;

        $aplicacion = RubricaAplicacion::updateOrCreate(
            [
                'rubrica_id'    => $rubrica->id,
                'asignacion_id' => $asignacion->id,
                'matricula_id'  => $data['matricula_id'],
            ],
            [
                'resultados'    => $data['resultados'],
                'puntaje'       => $puntaje,
                'puntaje_max'   => $puntajeMax,
                'observaciones' => $data['observaciones'] ?? null,
                'aplicado_en'   => now(),
            ]
        );

        return response()->json([
            'ok'         => true,
            'puntaje'    => $puntaje,
            'puntaje_max'=> $puntajeMax,
            'porcentaje' => $aplicacion->porcentaje,
        ]);
    }

    // ── Resultados de la aplicación ──────────────────────────────────────────
    public function resultados(Request $request, Rubrica $rubrica)
    {
        $this->autorizarRubrica($rubrica);

        $docente      = $this->getDocente();
        $asignacionId = $request->input('asignacion_id');

        $asignaciones = Asignacion::where('docente_id', $docente->id)
            ->with(['asignatura', 'grupo'])
            ->get();

        $asignacion   = null;
        $matriculas   = collect();
        $aplicaciones = collect();
        $stats        = null;

        if ($asignacionId) {
            $asignacion = Asignacion::where('id', $asignacionId)->where('docente_id', $docente->id)->firstOrFail();
            $schoolYear = SchoolYear::actual();

            $matriculas = Matricula::with('estudiante')
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $aplicaciones = RubricaAplicacion::where('rubrica_id', $rubrica->id)
                ->where('asignacion_id', $asignacion->id)
                ->get()
                ->keyBy('matricula_id');

            if ($aplicaciones->isNotEmpty()) {
                $stats = [
                    'completados' => $aplicaciones->count(),
                    'pendientes'  => $matriculas->count() - $aplicaciones->count(),
                    'promedio'    => round($aplicaciones->avg('porcentaje'), 1),
                    'aprobados'   => $aplicaciones->filter(fn($a) => $a->porcentaje >= 60)->count(),
                ];
            }
        }

        return view('portal.docente.rubricas.resultados', compact(
            'rubrica', 'asignaciones', 'asignacion', 'matriculas', 'aplicaciones', 'stats'
        ));
    }
}
