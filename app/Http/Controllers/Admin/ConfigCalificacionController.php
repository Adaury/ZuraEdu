<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ConfigCalificacion;
use App\Models\Docente;
use App\Models\ResultadoAprendizaje;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ConfigCalificacionController extends Controller
{
    // ── Helper: get docente for auth user ─────────────────────────────────
    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            return Docente::where('user_id', $user->id)->first()
                ?? Docente::where('email', $user->email)->first();
        }
        return null;
    }

    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $configs = $schoolYear
            ? ConfigCalificacion::where('school_year_id', $schoolYear->id)->get()->keyBy('componente')
            : collect();
        $schoolYears = SchoolYear::orderByDesc('activo')->get();
        return view('admin.config_calificacion.index', compact('schoolYear', 'configs', 'schoolYears'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'componentes'    => 'required|array',
        ]);

        $total = 0;
        foreach ($request->componentes as $comp => $data) {
            $total += (float)($data['peso'] ?? 0);
        }
        if (abs($total - 100) > 0.01) {
            return back()->withErrors(['total' => "Los pesos deben sumar 100%. Suma actual: {$total}%"])->withInput();
        }

        foreach ($request->componentes as $comp => $data) {
            ConfigCalificacion::updateOrCreate(
                ['school_year_id' => $request->school_year_id, 'componente' => $comp],
                ['peso' => (float)$data['peso'], 'activo' => isset($data['activo'])]
            );
        }

        return back()->with('success', 'Configuración guardada. Total: 100%');
    }

    // ── Configurar RA ──────────────────────────────────────────────────────

    public function indexRa(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $docente    = $this->docenteActual();

        if ($docente) {
            // Docente: solo asignaturas de sus asignaciones activas con num_ra > 0
            $asignaturasIds = Asignacion::where('school_year_id', $schoolYear?->id ?? 0)
                ->where('docente_id', $docente->id)
                ->where('activo', true)
                ->pluck('asignatura_id')
                ->unique();

            $asignaturas = Asignatura::whereIn('id', $asignaturasIds)
                ->where('num_ra', '>', 0)
                ->orderBy('nombre')
                ->get();
        } else {
            // Admin / Encargado: todas las asignaturas con num_ra > 0
            $asignaturas = Asignatura::where('num_ra', '>', 0)
                ->orderBy('nombre')
                ->get();
        }

        $asignaturaSelId = $request->asignatura_id ?? optional($asignaturas->first())->id;
        $asignaturaSelected = $asignaturaSelId
            ? $asignaturas->firstWhere('id', $asignaturaSelId)
            : null;

        $ras = $asignaturaSelected
            ? ResultadoAprendizaje::where('asignatura_id', $asignaturaSelected->id)
                ->orderBy('numero')
                ->get()
            : collect();

        return view('admin.config_calificacion.ra', compact(
            'schoolYear', 'asignaturas', 'asignaturaSelected', 'ras'
        ));
    }

    public function updateRa(Request $request)
    {
        $request->validate([
            'asignatura_id' => 'required|exists:asignaturas,id',
            'pesos'         => 'required|array',
        ]);

        // Validate sum = 100 (±0.5 tolerance)
        $total = 0;
        foreach ($request->pesos as $raId => $peso) {
            $total += (float) $peso;
        }
        if (abs($total - 100) > 0.5) {
            return response()->json([
                'success' => false,
                'message' => 'Los pesos deben sumar 100%. Suma actual: ' . round($total, 1) . '%',
            ], 422);
        }

        foreach ($request->pesos as $raId => $peso) {
            ResultadoAprendizaje::where('id', (int) $raId)
                ->where('asignatura_id', $request->asignatura_id)
                ->update(['peso' => round((float) $peso, 2)]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesos de RA guardados correctamente.',
            'total'   => round($total, 2),
        ]);
    }

    public function getRaDatos(Request $request)
    {
        $request->validate(['asignatura_id' => 'required|exists:asignaturas,id']);

        $ras = ResultadoAprendizaje::where('asignatura_id', $request->asignatura_id)
            ->orderBy('numero')
            ->get();

        return response()->json([
            'ras' => $ras->map(fn ($ra) => [
                'id'          => $ra->id,
                'numero'      => $ra->numero,
                'descripcion' => $ra->descripcion,
                'peso'        => $ra->peso,
                'activo'      => $ra->activo,
            ]),
        ]);
    }
}
