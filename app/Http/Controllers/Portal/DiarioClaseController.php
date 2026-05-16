<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\DiarioClase;
use App\Models\SchoolYear;
use App\Traits\HasDocenteContext;
use Illuminate\Http\Request;

class DiarioClaseController extends Controller
{
    use HasDocenteContext;

    private function autorizar(Asignacion $asignacion): void
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
    }

    // ── Lista + formulario ───────────────────────────────────────────────────
    public function index(Request $request, Asignacion $asignacion)
    {
        $this->autorizar($asignacion);
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        $anio    = $request->input('anio', now()->year);
        $mes     = $request->input('mes');

        $query = DiarioClase::where('asignacion_id', $asignacion->id)
            ->whereYear('fecha', $anio)
            ->orderByDesc('fecha');

        if ($mes) {
            $query->whereMonth('fecha', $mes);
        }

        $entradas = $query->get();

        // Meses con entradas para filtro
        $mesesConEntradas = DiarioClase::where('asignacion_id', $asignacion->id)
            ->selectRaw('YEAR(fecha) as anio, MONTH(fecha) as mes')
            ->groupByRaw('YEAR(fecha), MONTH(fecha)')
            ->orderByRaw('YEAR(fecha) DESC, MONTH(fecha) DESC')
            ->get();

        $totalEntradas = DiarioClase::where('asignacion_id', $asignacion->id)->count();

        return view('portal.docente.diario_clase', compact(
            'asignacion', 'entradas', 'mesesConEntradas', 'totalEntradas', 'anio', 'mes'
        ));
    }

    // ── Crear entrada ────────────────────────────────────────────────────────
    public function store(Request $request, Asignacion $asignacion)
    {
        $this->autorizar($asignacion);
        $docente = $this->getDocente();

        $data = $request->validate([
            'fecha'          => 'required|date',
            'tema'           => 'required|string|max:300',
            'actividades'    => 'nullable|string|max:3000',
            'observaciones'  => 'nullable|string|max:2000',
            'incidencias'    => 'nullable|string|max:1000',
            'asistentes'     => 'nullable|integer|min:0|max:200',
        ]);

        // Upsert por fecha (una entrada por clase por día)
        DiarioClase::updateOrCreate(
            ['asignacion_id' => $asignacion->id, 'fecha' => $data['fecha']],
            array_merge($data, ['docente_id' => $docente->id])
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Entrada guardada en el diario.');
    }

    // ── Actualizar ───────────────────────────────────────────────────────────
    public function update(Request $request, Asignacion $asignacion, DiarioClase $diarioClase)
    {
        $this->autorizar($asignacion);
        abort_if($diarioClase->asignacion_id !== $asignacion->id, 404);

        $data = $request->validate([
            'tema'          => 'required|string|max:300',
            'actividades'   => 'nullable|string|max:3000',
            'observaciones' => 'nullable|string|max:2000',
            'incidencias'   => 'nullable|string|max:1000',
            'asistentes'    => 'nullable|integer|min:0|max:200',
        ]);

        $diarioClase->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Entrada actualizada.');
    }

    // ── Eliminar ─────────────────────────────────────────────────────────────
    public function destroy(Asignacion $asignacion, DiarioClase $diarioClase)
    {
        $this->autorizar($asignacion);
        abort_if($diarioClase->asignacion_id !== $asignacion->id, 404);

        $diarioClase->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Entrada eliminada.');
    }

    // ── PDF ──────────────────────────────────────────────────────────────────
    public function pdf(Request $request, Asignacion $asignacion)
    {
        $this->autorizar($asignacion);
        $docente = $this->getDocente();
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        $anio = $request->input('anio', now()->year);
        $mes  = $request->input('mes');

        $entradas = DiarioClase::where('asignacion_id', $asignacion->id)
            ->whereYear('fecha', $anio)
            ->when($mes, fn($q) => $q->whereMonth('fecha', $mes))
            ->orderBy('fecha')
            ->get();

        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.diario_clase_pdf', compact(
            'docente', 'asignacion', 'entradas', 'anio', 'mes', 'inst'
        ))->setPaper('letter', 'portrait');

        $slug = \Str::slug($asignacion->asignatura?->nombre ?? 'asig');
        return $pdf->stream("Diario_{$slug}_{$anio}.pdf");
    }
}
