<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicRiskScore;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Services\AcademicRiskScoreService;
use Illuminate\Http\Request;

class AcademicRiskController extends Controller
{
    public function __construct(private AcademicRiskScoreService $service)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()->hasAnyRole([
                'Administrador', 'Director',
                'Coordinador Académico',
                'Coordinador Primer Ciclo',
                'Coordinador Segundo Ciclo',
            ]), 403);
            return $next($request);
        });
    }

    /** GET /admin/riesgo */
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grados     = Grado::orderBy('orden')->get();
        $grupos     = $schoolYear
            ? Grupo::with(['grado', 'seccion'])
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->orderBy('grado_id')
                ->get()
            : collect();

        // Filtros
        $nivelFiltro = $request->input('nivel');
        $grupoFiltro = $request->input('grupo_id');
        $search      = $request->input('q');

        $query = AcademicRiskScore::with([
                'estudiante.matriculas' => fn($q) => $schoolYear
                    ? $q->where('school_year_id', $schoolYear->id)->where('estado', 'activa')->with('grupo.grado', 'grupo.seccion')
                    : $q,
            ])
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->orderByDesc('score');

        if ($nivelFiltro) {
            $query->where('nivel', $nivelFiltro);
        }

        if ($grupoFiltro) {
            $query->whereHas('estudiante.matriculas', fn($q) =>
                $q->where('grupo_id', $grupoFiltro)
                  ->where('school_year_id', $schoolYear?->id)
                  ->where('estado', 'activa')
            );
        }

        if ($search) {
            $query->whereHas('estudiante', fn($q) =>
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%")
            );
        }

        $scores = $query->paginate(50)->withQueryString();

        // Estadísticas por nivel
        $resumen = AcademicRiskScore::where('school_year_id', $schoolYear?->id ?? 0)
            ->selectRaw('nivel, COUNT(*) as total')
            ->groupBy('nivel')
            ->pluck('total', 'nivel');

        $totalEst  = $resumen->sum();
        $ultimoCalc = AcademicRiskScore::where('school_year_id', $schoolYear?->id ?? 0)
            ->max('calculado_en');

        return view('admin.riesgo.index', compact(
            'scores', 'schoolYear', 'grados', 'grupos',
            'resumen', 'totalEst', 'ultimoCalc',
            'nivelFiltro', 'grupoFiltro', 'search',
        ));
    }

    /** GET /admin/riesgo/{score} */
    public function show(AcademicRiskScore $score)
    {
        $score->load([
            'estudiante',
            'schoolYear',
        ]);

        // Datos del grupo actual del estudiante
        $matricula = $score->estudiante?->matriculas()
            ->with('grupo.grado', 'grupo.seccion')
            ->where('school_year_id', $score->school_year_id)
            ->where('estado', 'activa')
            ->first();

        return view('admin.riesgo.show', compact('score', 'matricula'));
    }

    /** POST /admin/riesgo/calcular */
    public function calcular(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        abort_if(! $schoolYear, 422, 'No hay año escolar activo.');

        $count = $this->service->calcularTodos($schoolYear->id);

        if ($request->expectsJson()) {
            return response()->json(['count' => $count, 'message' => "Scores calculados para {$count} estudiantes."]);
        }

        return back()->with('success', "Risk Score calculado para {$count} estudiantes.");
    }

    /** POST /admin/riesgo/{estudiante}/recalcular (individual) */
    public function recalcularUno(int $estudianteId)
    {
        $schoolYear = SchoolYear::actual();
        abort_if(! $schoolYear, 422, 'No hay año escolar activo.');

        $data = $this->service->calcularParaEstudiante($estudianteId, $schoolYear->id);

        $ars = AcademicRiskScore::updateOrCreate(
            [
                'tenant_id'      => tenant_id() ?? 0,
                'estudiante_id'  => $estudianteId,
                'school_year_id' => $schoolYear->id,
            ],
            array_merge($data, [
                'tenant_id'    => tenant_id() ?? 0,
                'calculado_en' => now(),
            ])
        );

        return response()->json([
            'score' => $ars->score,
            'nivel' => $ars->nivel,
            'data'  => $data,
        ]);
    }
}
