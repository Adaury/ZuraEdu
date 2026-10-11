<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicRiskScore;
use App\Models\ActivityLog;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Services\AcademicRiskScoreService;
use Illuminate\Http\Request;

class AcademicRiskController extends Controller
{
    // Acceso: Gate 'acceso-direccion-coordinacion' aplicado en routes/admin/riesgo.php
    // (Administrador, Director y los 3 coordinadores; super_admin entra por Gate::before).
    // Alcance: Administrador, Director y Coordinador Académico ven todo el colegio; el
    // Coordinador de Primer/Segundo Ciclo solo ve estudiantes (y grupos/secciones) de su ciclo.
    public function __construct(private AcademicRiskScoreService $service)
    {
    }

    /**
     * Ciclos que el usuario puede ver, o null si ve todo el colegio.
     * Un usuario sin rol de alcance total ni de coordinador de ciclo (no debería
     * llegar aquí por el Gate) obtiene [] y no ve nada.
     */
    private function ciclosVisibles(): ?array
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')
            || $user->hasAnyRole(['Administrador', 'Director', 'Coordinador Académico'])) {
            return null;
        }

        // Inicial se agrupa con Primer Ciclo (primeros años) y Bachillerato con
        // Segundo Ciclo (últimos años), para que ningún grado quede sin coordinador.
        $ciclos = [];
        if ($user->hasRole('Coordinador Primer Ciclo'))  array_push($ciclos, 'inicial', 'primer_ciclo');
        if ($user->hasRole('Coordinador Segundo Ciclo')) array_push($ciclos, 'segundo_ciclo', 'bachillerato');

        return $ciclos;
    }

    /** Restringe una consulta de AcademicRiskScore a estudiantes con matrícula activa en los ciclos dados. */
    private function limitarACiclos($query, ?array $ciclos, ?int $schoolYearId)
    {
        if ($ciclos === null) {
            return $query;
        }

        return $query->whereHas('estudiante.matriculas', fn ($m) => $m
            ->where('school_year_id', $schoolYearId)
            ->where('estado', 'activa')
            ->whereHas('grupo.grado', fn ($g) => $g->whereIn('ciclo', $ciclos)));
    }

    /** 403 si el estudiante no tiene matrícula activa en un ciclo que el usuario puede ver. */
    private function autorizarCicloDelEstudiante(int $estudianteId, int $schoolYearId): void
    {
        $ciclos = $this->ciclosVisibles();
        if ($ciclos === null) {
            return;
        }

        abort_unless(
            Matricula::where('estudiante_id', $estudianteId)
                ->where('school_year_id', $schoolYearId)
                ->where('estado', 'activa')
                ->whereHas('grupo.grado', fn ($g) => $g->whereIn('ciclo', $ciclos))
                ->exists(),
            403,
            'Este estudiante no pertenece a tu ciclo.'
        );
    }

    /** GET /admin/riesgo */
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $ciclos     = $this->ciclosVisibles();

        $grados     = Grado::orderBy('orden')
            ->when($ciclos !== null, fn ($q) => $q->whereIn('ciclo', $ciclos))
            ->get();
        $grupos     = $schoolYear
            ? Grupo::with(['grado', 'seccion'])
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->when($ciclos !== null, fn ($q) => $q->whereHas('grado', fn ($g) => $g->whereIn('ciclo', $ciclos)))
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
        $this->limitarACiclos($query, $ciclos, $schoolYear?->id);

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
                  ->orWhere('numero_matricula', 'like', "%{$search}%")
            );
        }

        $scores = $query->paginate(50)->withQueryString();

        // Estadísticas por nivel
        $resumenQuery = AcademicRiskScore::where('school_year_id', $schoolYear?->id ?? 0);
        $this->limitarACiclos($resumenQuery, $ciclos, $schoolYear?->id);
        $resumen = (clone $resumenQuery)
            ->selectRaw('nivel, COUNT(*) as total')
            ->groupBy('nivel')
            ->pluck('total', 'nivel');

        $totalEst  = $resumen->sum();
        $ultimoCalc = (clone $resumenQuery)->max('calculado_en');

        return view('admin.riesgo.index', compact(
            'scores', 'schoolYear', 'grados', 'grupos',
            'resumen', 'totalEst', 'ultimoCalc',
            'nivelFiltro', 'grupoFiltro', 'search',
        ));
    }

    /** GET /admin/riesgo/{score} */
    public function show(AcademicRiskScore $score)
    {
        $this->autorizarCicloDelEstudiante($score->estudiante_id, $score->school_year_id);

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

        ActivityLog::registrar(
            'riesgo.recalculado_todos',
            SchoolYear::class,
            $schoolYear->id,
            "Risk Score recalculado manualmente para {$count} estudiantes ({$schoolYear->nombre})"
        );

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

        // No confiar en el ID de la URL: el estudiante debe existir en este
        // tenant (scope BelongsToTenant) y tener matrícula activa en el año.
        $estudiante = Estudiante::findOrFail($estudianteId);
        abort_unless(
            Matricula::where('estudiante_id', $estudiante->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('estado', 'activa')
                ->exists(),
            404
        );

        $this->autorizarCicloDelEstudiante($estudiante->id, $schoolYear->id);

        $data = $this->service->calcularParaEstudiante($estudiante->id, $schoolYear->id);

        $ars = AcademicRiskScore::updateOrCreate(
            [
                'tenant_id'      => tenant_id() ?? 0,
                'estudiante_id'  => $estudiante->id,
                'school_year_id' => $schoolYear->id,
            ],
            array_merge($data, [
                'tenant_id'    => tenant_id() ?? 0,
                'calculado_en' => now(),
            ])
        );

        ActivityLog::registrar(
            'riesgo.recalculado',
            AcademicRiskScore::class,
            $ars->id,
            "Risk Score recalculado manualmente para el estudiante #{$estudiante->id}: {$ars->score} ({$ars->nivel})"
        );

        return response()->json([
            'score' => $ars->score,
            'nivel' => $ars->nivel,
            'data'  => $data,
        ]);
    }
}
