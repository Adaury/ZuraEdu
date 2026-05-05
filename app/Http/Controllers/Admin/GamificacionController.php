<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Grupo;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\PuntoEstudiante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GamificacionController extends Controller
{
    // ── Index: ranking por grupo ──────────────────────────────────────────
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupos     = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        $grupoId = $request->input('grupo_id', $grupos->first()?->id);

        // Ranking: total de puntos por matrícula en el grupo seleccionado
        $ranking = collect();
        if ($grupoId) {
            $ranking = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->map(function (Matricula $m) {
                    $total    = PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos');
                    $insignias = InsigniaEstudiante::where('matricula_id', $m->id)->count();
                    return [
                        'matricula'  => $m,
                        'total'      => $total,
                        'insignias'  => $insignias,
                    ];
                })
                ->sortByDesc('total')
                ->values();
        }

        // Estadísticas globales del año
        $totalPuntos    = PuntoEstudiante::count();
        $totalInsignias = InsigniaEstudiante::count();
        $matriculasConPuntos = PuntoEstudiante::distinct('matricula_id')->count('matricula_id');

        return view('admin.gamificacion.index', compact(
            'grupos', 'grupoId', 'ranking',
            'totalPuntos', 'totalInsignias', 'matriculasConPuntos',
            'schoolYear'
        ));
    }

    // ── Asignar puntos manualmente (POST) ────────────────────────────────
    public function asignarPuntos(Request $request)
    {
        $data = $request->validate([
            'matricula_id' => ['required', 'exists:matriculas,id'],
            'concepto'     => ['required', 'string', 'max:255'],
            'categoria'    => ['required', 'in:academico,asistencia,conducta,participacion,extra'],
            'puntos'       => ['required', 'integer', 'min:1', 'max:500'],
            'fecha'        => ['required', 'date'],
        ]);

        $punto = PuntoEstudiante::create($data);

        // Verificar si se desbloquean insignias por acumulado
        $this->verificarInsigniasAcumulado($data['matricula_id']);

        return back()->with('success', "Se asignaron {$data['puntos']} puntos correctamente.");
    }

    // ── Generar puntos automáticos para un grupo (POST) ──────────────────
    public function generarPuntos(Request $request)
    {
        $request->validate([
            'grupo_id' => ['required', 'exists:grupos,id'],
        ]);

        $schoolYear = SchoolYear::actual();
        $grupoId    = $request->input('grupo_id');
        $hoy        = today();

        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $grupoId)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $generados = 0;

        foreach ($matriculas as $matricula) {
            // ── Puntos académicos ──────────────────────────────────────
            $promedio = $this->calcularPromedioMatricula($matricula, $schoolYear);

            if ($promedio !== null) {
                if ($promedio >= 90) {
                    $this->crearPuntoSiNoExiste($matricula->id, 'Promedio académico ≥ 90', 'academico', 50, $hoy);
                    $generados++;
                } elseif ($promedio >= 80) {
                    $this->crearPuntoSiNoExiste($matricula->id, 'Promedio académico ≥ 80', 'academico', 30, $hoy);
                    $generados++;
                }
            }

            // ── Puntos de asistencia ───────────────────────────────────
            $pctAsistencia = $this->calcularPorcentajeAsistencia($matricula);

            if ($pctAsistencia !== null && $pctAsistencia >= 95) {
                $this->crearPuntoSiNoExiste($matricula->id, 'Asistencia ≥ 95%', 'asistencia', 40, $hoy);
                $generados++;
            }

            // ── Puntos por sin faltas disciplinarias ───────────────────
            $sinFaltas = ! DB::table('faltas_disciplinarias')
                ->where('matricula_id', $matricula->id)
                ->exists();

            if ($sinFaltas) {
                $this->crearPuntoSiNoExiste($matricula->id, 'Sin faltas disciplinarias', 'conducta', 20, $hoy);
                $generados++;
            }

            // Verificar insignias tras generar puntos
            $this->verificarInsigniasCompletas($matricula, $schoolYear);
        }

        return back()->with('success', "Se generaron {$generados} registros de puntos para el grupo.");
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    private function crearPuntoSiNoExiste(int $matriculaId, string $concepto, string $categoria, int $puntos, $fecha): void
    {
        $existe = PuntoEstudiante::where('matricula_id', $matriculaId)
            ->where('concepto', $concepto)
            ->where('fecha', $fecha)
            ->exists();

        if (! $existe) {
            PuntoEstudiante::create([
                'matricula_id' => $matriculaId,
                'concepto'     => $concepto,
                'categoria'    => $categoria,
                'puntos'       => $puntos,
                'fecha'        => $fecha,
            ]);
        }
    }

    private function calcularPromedioMatricula(Matricula $matricula, $schoolYear): ?float
    {
        $notasTec = Calificacion::where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->pluck('nota_final');

        $notasAcad = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')
            ->pluck('nota_final');

        $todas = $notasTec->merge($notasAcad)->filter();

        return $todas->count() ? round($todas->avg(), 2) : null;
    }

    private function calcularPorcentajeAsistencia(Matricula $matricula): ?float
    {
        $asistencias = Asistencia::where('matricula_id', $matricula->id)->get();
        $total = $asistencias->count();
        if ($total === 0) return null;

        $presentes = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();
        return round($presentes / $total * 100, 1);
    }

    private function verificarInsigniasAcumulado(int $matriculaId): void
    {
        $total = PuntoEstudiante::where('matricula_id', $matriculaId)->sum('puntos');

        if ($total >= 100) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matriculaId, 'tipo' => 'cien_puntos'],
                ['fecha_obtencion' => today()]
            );
        }

        if ($total >= 500) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matriculaId, 'tipo' => 'quinientos_puntos'],
                ['fecha_obtencion' => today()]
            );
        }
    }

    private function verificarInsigniasCompletas(Matricula $matricula, $schoolYear): void
    {
        // Insignia: acumulado de puntos
        $this->verificarInsigniasAcumulado($matricula->id);

        // Insignia: top_estudiante (promedio ≥ 90)
        $promedio = $this->calcularPromedioMatricula($matricula, $schoolYear);
        if ($promedio !== null && $promedio >= 90) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matricula->id, 'tipo' => 'top_estudiante'],
                ['fecha_obtencion' => today()]
            );
        }

        // Insignia: asistencia_perfecta (≥ 95%)
        $pct = $this->calcularPorcentajeAsistencia($matricula);
        if ($pct !== null && $pct >= 95) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matricula->id, 'tipo' => 'asistencia_perfecta'],
                ['fecha_obtencion' => today()]
            );
        }

        // Insignia: sin_faltas
        $sinFaltas = ! DB::table('faltas_disciplinarias')
            ->where('matricula_id', $matricula->id)
            ->exists();

        if ($sinFaltas) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matricula->id, 'tipo' => 'sin_faltas'],
                ['fecha_obtencion' => today()]
            );
        }

        // Insignia: mejora_continua
        if ($schoolYear) {
            $periodos = Periodo::where('school_year_id', $schoolYear->id)->orderBy('numero')->get();
            $promediosPorPeriodo = [];

            foreach ($periodos as $periodo) {
                $notas = Calificacion::where('matricula_id', $matricula->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('publicado', true)
                    ->whereNotNull('nota_final')
                    ->pluck('nota_final');

                if ($notas->count()) {
                    $promediosPorPeriodo[$periodo->numero] = $notas->avg();
                }
            }

            $nums = array_keys($promediosPorPeriodo);
            sort($nums);
            for ($i = 1; $i < count($nums); $i++) {
                if ($promediosPorPeriodo[$nums[$i]] > $promediosPorPeriodo[$nums[$i - 1]]) {
                    InsigniaEstudiante::firstOrCreate(
                        ['matricula_id' => $matricula->id, 'tipo' => 'mejora_continua'],
                        ['fecha_obtencion' => today()]
                    );
                    break;
                }
            }
        }
    }
}
