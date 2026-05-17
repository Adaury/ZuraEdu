<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Rubrica;
use App\Models\RubricaAplicacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class RubricaAdminController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $query = Rubrica::with(['docente', 'asignatura'])
            ->withCount('aplicaciones')
            ->latest();

        if ($request->filled('docente_id'))    $query->where('docente_id', $request->docente_id);
        if ($request->filled('asignatura_id')) $query->where('asignatura_id', $request->asignatura_id);
        if ($request->filled('search'))        $query->where('titulo', 'like', '%'.$request->search.'%');

        $rubricas = $query->paginate(20)->withQueryString();

        // KPIs globales
        $totalRubricas     = Rubrica::count();
        $totalAplicaciones = RubricaAplicacion::count();
        $promedioGlobal    = RubricaAplicacion::whereNotNull('puntaje_max')
            ->where('puntaje_max', '>', 0)
            ->selectRaw('AVG(puntaje / puntaje_max * 100) as avg_pct')
            ->value('avg_pct');
        $docentesActivos   = Rubrica::distinct('docente_id')->count('docente_id');

        $docentes    = Docente::orderBy('apellidos')->get();
        $asignaturas = Asignatura::orderBy('nombre')->get();

        return view('admin.rubricas.index', compact(
            'rubricas', 'schoolYear',
            'totalRubricas', 'totalAplicaciones', 'promedioGlobal', 'docentesActivos',
            'docentes', 'asignaturas'
        ));
    }

    public function show(Rubrica $rubrica)
    {
        $rubrica->load(['docente', 'asignatura']);

        $aplicaciones = RubricaAplicacion::with([
            'asignacion.asignatura', 'asignacion.grupo',
            'matricula.estudiante',
        ])
        ->where('rubrica_id', $rubrica->id)
        ->orderBy('asignacion_id')
        ->orderByDesc('aplicado_en')
        ->get();

        // Agrupar por asignacion_id
        $porAsignacion = $aplicaciones->groupBy('asignacion_id');

        // Stats por criterio: distribución de niveles
        $criterios    = $rubrica->criterios ?? [];
        $niveles      = $rubrica->niveles   ?? [];
        $distribucion = [];
        foreach ($criterios as $ci => $crit) {
            $dist = array_fill(0, count($niveles), 0);
            foreach ($aplicaciones as $ap) {
                $nIdx = $ap->resultados[$ci] ?? null;
                if ($nIdx !== null && isset($dist[$nIdx])) {
                    $dist[$nIdx]++;
                }
            }
            $distribucion[$ci] = $dist;
        }

        $totalAplicadas  = $aplicaciones->count();
        $promedioGlobal  = $totalAplicadas
            ? round($aplicaciones->avg('porcentaje'), 1)
            : 0;
        $sobre60         = $aplicaciones->filter(fn($a) => $a->porcentaje >= 60)->count();

        return view('admin.rubricas.show', compact(
            'rubrica', 'porAsignacion', 'criterios', 'niveles', 'distribucion',
            'totalAplicadas', 'promedioGlobal', 'sobre60'
        ));
    }
}
