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

        PuntoEstudiante::create($data);
        $this->verificarInsigniasAcumulado($data['matricula_id']);

        $redirect = $request->input('_redirect');
        if ($redirect && str_starts_with($redirect, url('/'))) {
            return redirect($redirect)->with('success', "Se asignaron {$data['puntos']} puntos correctamente.");
        }

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

    // ── Detalle de un estudiante ─────────────────────────────────────────
    public function detalle(Request $request, \App\Models\Matricula $matricula)
    {
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $totalPuntos = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
        $insigniasObtenidas = InsigniaEstudiante::where('matricula_id', $matricula->id)->get()->keyBy('tipo');

        $historial = PuntoEstudiante::where('matricula_id', $matricula->id)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get();

        $puntosCategoria = [];
        foreach (PuntoEstudiante::CATEGORIAS as $cat => $info) {
            $puntosCategoria[$cat] = PuntoEstudiante::where('matricula_id', $matricula->id)
                ->where('categoria', $cat)->sum('puntos');
        }

        $posicion = null;
        if ($matricula->grupo_id) {
            $ranking = Matricula::where('grupo_id', $matricula->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'total' => PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos')])
                ->sortByDesc('total')->values();
            $idx = $ranking->search(fn($r) => $r['id'] === $matricula->id);
            $posicion = $idx !== false ? $idx + 1 : null;
        }

        return view('admin.gamificacion.detalle', compact(
            'matricula', 'totalPuntos', 'insigniasObtenidas',
            'historial', 'puntosCategoria', 'posicion', 'schoolYear'
        ));
    }

    // ── Eliminar un punto ────────────────────────────────────────────────
    public function eliminarPunto(PuntoEstudiante $punto)
    {
        $punto->delete();
        return back()->with('success', 'Punto eliminado correctamente.');
    }

    // ── PDF del ranking del grupo ────────────────────────────────────────
    public function rankingPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')->orderBy('seccion_id')->get();

        $grupoId = $request->input('grupo_id', $grupos->first()?->id);
        $grupo   = $grupos->find($grupoId);

        $ranking = collect();
        if ($grupoId) {
            $ranking = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->map(function (Matricula $m) {
                    return [
                        'matricula'  => $m,
                        'total'      => PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos'),
                        'insignias'  => InsigniaEstudiante::where('matricula_id', $m->id)->count(),
                    ];
                })
                ->sortByDesc('total')->values();
        }

        $config = \App\Models\ConfigInstitucional::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.gamificacion.ranking_pdf', compact('ranking', 'grupo', 'schoolYear', 'config'))
            ->setPaper('a4', 'portrait');

        $nombreGrupo = $grupo ? str_replace(' ', '_', trim(($grupo->grado?->nombre ?? '') . '_' . ($grupo->seccion?->nombre ?? ''))) : 'grupo';

        return $pdf->download("ranking_gamificacion_{$nombreGrupo}.pdf");
    }

    public function rankingExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')->orderBy('seccion_id')->get();

        $grupoId = $request->input('grupo_id', $grupos->first()?->id);
        $grupo   = $grupos->find($grupoId);

        $ranking = collect();
        if ($grupoId) {
            $ranking = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->map(fn($m) => [
                    'matricula' => $m,
                    'total'     => PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos'),
                    'insignias' => InsigniaEstudiante::where('matricula_id', $m->id)->count(),
                ])
                ->sortByDesc('total')->values();
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Ranking');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338ca']],
        ];

        $titulo = 'Ranking de Gamificación — ' . ($grupo ? ($grupo->grado?->nombre . ' ' . $grupo->seccion?->nombre) : 'Todos') . ' — ' . now()->format('d/m/Y');
        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', $titulo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Apellidos', 'Grupo', 'Puntos', 'Insignias'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($ranking as $i => $item) {
            $row = $i + 4;
            $est = $item['matricula']->estudiante;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $est?->nombres ?? '—');
            $ws->setCellValue("C{$row}", $est?->apellidos ?? '—');
            $ws->setCellValue("D{$row}", $item['matricula']->grupo?->nombre_completo ?? '—');
            $ws->setCellValue("E{$row}", $item['total']);
            $ws->setCellValue("F{$row}", $item['insignias']);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eef2ff');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'gami_') . '.xlsx';
        $writer->save($tmp);

        $nombreGrupo = $grupo ? str_replace(' ', '_', trim(($grupo->grado?->nombre ?? '') . '_' . ($grupo->seccion?->nombre ?? ''))) : 'todos';
        return response()->download($tmp, "ranking_gamificacion_{$nombreGrupo}_" . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
            $periodos = $this->getPeriodos($schoolYear);
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
