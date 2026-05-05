<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\EvaluacionDocente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionDocenteController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $docentes = Docente::activos()->orderBy('apellidos')->get();

        $query = EvaluacionDocente::with(['docente', 'evaluador'])
            ->orderByDesc('created_at');

        if ($request->filled('docente_id')) {
            $query->where('docente_id', $request->docente_id);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo_evaluado', 'like', '%' . $request->periodo . '%');
        }

        $evaluaciones = $query->paginate(15)->withQueryString();

        return view('admin.evaluaciones_docentes.index', compact('evaluaciones', 'docentes'));
    }

    // ── Dashboard ──────────────────────────────────────────────────────────
    public function dashboard()
    {
        $total = EvaluacionDocente::count();

        $promedioInstitucional = $total > 0
            ? round(EvaluacionDocente::avg('promedio'), 2)
            : null;

        // % por nivel
        $niveles = [
            'Excelente'  => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 4.5)->count() / $total * 100, 1) : 0,
            'Bueno'      => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 3.5)->where('promedio', '<', 4.5)->count() / $total * 100, 1) : 0,
            'Regular'    => $total > 0 ? round(EvaluacionDocente::where('promedio', '>=', 2.5)->where('promedio', '<', 3.5)->count() / $total * 100, 1) : 0,
            'Deficiente' => $total > 0 ? round(EvaluacionDocente::where('promedio', '<', 2.5)->count() / $total * 100, 1) : 0,
        ];

        // Ranking de docentes (promedio de todas sus evaluaciones)
        $ranking = Docente::withCount('evaluaciones as total_evaluaciones')
            ->with('evaluaciones')
            ->having('total_evaluaciones', '>', 0)
            ->get()
            ->map(function ($docente) {
                $docente->promedio_general = round($docente->evaluaciones->avg('promedio'), 2);
                return $docente;
            })
            ->sortByDesc('promedio_general')
            ->values();

        return view('admin.evaluaciones_docentes.dashboard', compact(
            'total', 'promedioInstitucional', 'niveles', 'ranking'
        ));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $docentes = Docente::activos()->orderBy('apellidos')->get();
        $docentePresel = $request->filled('docente_id')
            ? Docente::find($request->docente_id)
            : null;

        return view('admin.evaluaciones_docentes.create', compact('docentes', 'docentePresel'));
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'docente_id'          => 'required|exists:docentes,id',
            'periodo_evaluado'    => 'required|string|max:100',
            'puntualidad'         => 'required|integer|min:1|max:5',
            'dominio_contenido'   => 'required|integer|min:1|max:5',
            'metodologia'         => 'required|integer|min:1|max:5',
            'relacion_estudiantes'=> 'required|integer|min:1|max:5',
            'planificacion'       => 'required|integer|min:1|max:5',
            'observaciones'       => 'nullable|string|max:2000',
        ]);

        $data['evaluador_id'] = Auth::id();

        $evaluacion = EvaluacionDocente::create($data);

        return redirect()
            ->route('admin.evaluaciones-docentes.show', $evaluacion)
            ->with('success', 'Evaluación registrada correctamente.');
    }

    // ── Show ───────────────────────────────────────────────────────────────
    public function show(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->load(['docente', 'evaluador']);

        return view('admin.evaluaciones_docentes.show', [
            'evaluacion' => $evaluacionDocente,
        ]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────
    public function destroy(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->delete();

        return redirect()
            ->route('admin.evaluaciones-docentes.index')
            ->with('success', 'Evaluación eliminada correctamente.');
    }

    // ── PDF ────────────────────────────────────────────────────────────────
    public function pdf(EvaluacionDocente $evaluacionDocente)
    {
        $evaluacionDocente->load(['docente', 'evaluador']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView(
            'admin.evaluaciones_docentes.pdf',
            ['evaluacion' => $evaluacionDocente, 'inst' => $inst]
        )->setPaper('letter', 'portrait');

        $nombre = 'evaluacion_docente_' .
            str_replace([' ', ','], ['_', ''], strtolower($evaluacionDocente->docente->apellidos ?? 'docente')) .
            '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
