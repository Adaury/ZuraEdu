<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Models\Asignacion;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PlanifAnualController extends Controller
{
    use HasDocenteContext;

    public function index(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $planes = PlanifAnual::with(['unidades'])
            ->where('asignacion_id', $asignacion->id)
            ->where('docente_id', $docente->id)
            ->latest()
            ->get();

        return view('portal.docente.planif_anual.index', compact(
            'docente', 'asignacion', 'planes', 'schoolYear'
        ));
    }

    public function store(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate(['titulo' => 'required|string|max:200']);

        $plan = PlanifAnual::create([
            'docente_id'     => $docente->id,
            'asignacion_id'  => $asignacion->id,
            'school_year_id' => SchoolYear::actual()?->id,
            'titulo'         => $request->titulo,
            'descripcion'    => $request->descripcion,
        ]);

        return redirect()->route('portal.docente.planif-anual.show', [$asignacion, $plan]);
    }

    public function show(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id || $plan->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $plan->load('unidades');
        $competencias = PlanifUnidad::COMPETENCIAS;

        return view('portal.docente.planif_anual.show', compact(
            'docente', 'asignacion', 'plan', 'competencias'
        ));
    }

    public function updatePlan(Request $request, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $plan->update($request->only(['titulo', 'descripcion']));
        return response()->json(['ok' => true]);
    }

    public function destroy(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $plan->delete();
        return redirect()->route('portal.docente.planif-anual.index', $asignacion)
            ->with('success', 'Plan eliminado.');
    }

    public function storeUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $siguiente = ($plan->unidades()->max('numero') ?? 0) + 1;

        $unidad = PlanifUnidad::create([
            'planif_anual_id' => $plan->id,
            'numero'          => $siguiente,
            'titulo'          => $request->input('titulo', "Unidad {$siguiente}"),
            'periodo'         => $request->input('periodo'),
        ]);

        return response()->json(['ok' => true, 'unidad' => $unidad]);
    }

    public function updateUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $unidad->update($request->only([
            'titulo', 'periodo', 'semanas', 'objetivos', 'competencias',
            'indicadores', 'contenidos', 'estrategias', 'recursos',
            'evaluacion', 'fecha_inicio', 'fecha_fin',
        ]));

        return response()->json(['ok' => true]);
    }

    public function destroyUnidad(Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $unidad->delete();

        // Renumerar
        $plan->unidades()->orderBy('numero')->each(function ($u, $i) {
            $u->update(['numero' => $i + 1]);
        });

        return response()->json(['ok' => true]);
    }

    public function moverUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $dir = $request->input('dir'); // 'up' | 'down'
        $unidades = $plan->unidades()->orderBy('numero')->get();
        $idx = $unidades->search(fn($u) => $u->id === $unidad->id);

        if ($dir === 'up' && $idx > 0) {
            $prev = $unidades[$idx - 1];
            [$unidad->numero, $prev->numero] = [$prev->numero, $unidad->numero];
            $unidad->save(); $prev->save();
        } elseif ($dir === 'down' && $idx < $unidades->count() - 1) {
            $next = $unidades[$idx + 1];
            [$unidad->numero, $next->numero] = [$next->numero, $unidad->numero];
            $unidad->save(); $next->save();
        }

        return response()->json(['ok' => true]);
    }

    public function pdf(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $plan->load('unidades');

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $plan->school_year_id ? \App\Models\BoletinConfig::getOrCreate($plan->school_year_id) : null;
        $schoolYear = $plan->schoolYear;
        $competencias = PlanifUnidad::COMPETENCIAS;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.planif_anual_pdf',
            compact('docente', 'asignacion', 'plan', 'si', 'config', 'schoolYear', 'competencias')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($plan->titulo);
        return $pdf->download("planificacion_{$slug}.pdf");
    }
}
