<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\FaltaDisciplinaria;
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
            $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $matIds = $matriculas->pluck('id');

            $puntosPorMat   = PuntoEstudiante::whereIn('matricula_id', $matIds)
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');

            $insigniasPorMat = InsigniaEstudiante::whereIn('matricula_id', $matIds)
                ->selectRaw('matricula_id, COUNT(*) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');

            $ranking = $matriculas->map(fn($m) => [
                'matricula' => $m,
                'total'     => (int) ($puntosPorMat[$m->id]    ?? 0),
                'insignias' => (int) ($insigniasPorMat[$m->id] ?? 0),
            ])->sortByDesc('total')->values();
        }

        // Estadísticas globales del año — puntos_estudiante/insignias_estudiante no
        // tienen columna tenant_id propia (solo matricula_id), así que el scope se
        // aplica vía la relación con Matricula, que sí es tenant-scoped.
        $totalPuntos    = PuntoEstudiante::whereHas('matricula')->count();
        $totalInsignias = InsigniaEstudiante::whereHas('matricula')->count();
        $matriculasConPuntos = PuntoEstudiante::whereHas('matricula')->distinct('matricula_id')->count('matricula_id');

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

        // puntos_estudiante no tiene tenant_id propio y `exists:` no filtra por centro: la matrícula
        // debe resolverse con el scope del tenant (antes se podían dar puntos a una matrícula ajena).
        $matricula = Matricula::findOrFail($data['matricula_id']);
        $data['matricula_id'] = $matricula->id;

        $punto = PuntoEstudiante::create($data);
        $this->verificarInsigniasAcumulado($data['matricula_id']);

        ActivityLog::registrar(
            'gamificacion.puntos_asignados',
            PuntoEstudiante::class,
            $punto->id,
            "+{$data['puntos']} pts ({$data['categoria']}) a matrícula #{$matricula->id}: {$data['concepto']}"
        );

        $redirect = $request->input('_redirect');
        // Prefijo con "/" final: "http://sge.test" también "empezaba" con "http://sge.test.evil.com".
        if ($redirect && str_starts_with($redirect, rtrim(url('/'), '/') . '/')) {
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

            // Cada concepto se otorga UNA vez por matrícula (= una vez por año escolar). Antes se
            // deduplicaba por fecha: generar otro día volvía a dar 50/40/20 puntos y las
            // insignias de 100/500 puntos se "compraban" pulsando el botón varios días.
            if ($promedio !== null) {
                if ($promedio >= 90) {
                    // Si ya cobró el tramo ≥ 80 (30 pts), solo se le suma la diferencia.
                    $yaTramo80 = PuntoEstudiante::where('matricula_id', $matricula->id)
                        ->where('concepto', 'Promedio académico ≥ 80')->exists();
                    if ($this->crearPuntoSiNoExiste($matricula->id, 'Promedio académico ≥ 90', 'academico', $yaTramo80 ? 20 : 50, $hoy)) {
                        $generados++;
                    }
                } elseif ($promedio >= 80) {
                    $yaTramo90 = PuntoEstudiante::where('matricula_id', $matricula->id)
                        ->where('concepto', 'Promedio académico ≥ 90')->exists();
                    if (! $yaTramo90 && $this->crearPuntoSiNoExiste($matricula->id, 'Promedio académico ≥ 80', 'academico', 30, $hoy)) {
                        $generados++;
                    }
                }
            }

            // ── Puntos de asistencia ───────────────────────────────────
            $pctAsistencia = $this->calcularPorcentajeAsistencia($matricula);

            if ($pctAsistencia !== null && $pctAsistencia >= 95) {
                if ($this->crearPuntoSiNoExiste($matricula->id, 'Asistencia ≥ 95%', 'asistencia', 40, $hoy)) {
                    $generados++;
                }
            }

            // ── Puntos por sin faltas disciplinarias (del año escolar, con datos suficientes) ──
            if ($this->sinFaltasEnElAnio($matricula, $schoolYear)) {
                if ($this->crearPuntoSiNoExiste($matricula->id, 'Sin faltas disciplinarias', 'conducta', 20, $hoy)) {
                    $generados++;
                }
            }

            // Verificar insignias tras generar puntos
            $this->verificarInsigniasCompletas($matricula, $schoolYear);
        }

        ActivityLog::registrar(
            'gamificacion.puntos_generados',
            Grupo::class,
            (int) $grupoId,
            "Puntos automáticos del grupo #{$grupoId}: {$generados} registro(s) nuevo(s) para {$matriculas->count()} estudiante(s)"
        );

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
            $grupoMats = Matricula::where('grupo_id', $matricula->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->pluck('id');

            $puntosPorMat = PuntoEstudiante::whereIn('matricula_id', $grupoMats)
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');

            $ranking = $grupoMats->map(fn($id) => ['id' => $id, 'total' => (int) ($puntosPorMat[$id] ?? 0)])
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
        // El binding de PuntoEstudiante no está filtrado por centro (sin tenant_id): se verifica
        // contra la matrícula, que sí lo está.
        $matricula = Matricula::findOrFail($punto->matricula_id);

        ActivityLog::registrar(
            'gamificacion.punto_eliminado',
            PuntoEstudiante::class,
            $punto->id,
            "Punto eliminado de matrícula #{$matricula->id}: {$punto->puntos} pts ({$punto->categoria}) — {$punto->concepto}"
        );

        $punto->delete();

        // Las insignias por acumulado ya no corresponden si el total bajó del umbral.
        $total = (int) PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
        if ($total < 500) {
            InsigniaEstudiante::where('matricula_id', $matricula->id)->where('tipo', 'quinientos_puntos')->delete();
        }
        if ($total < 100) {
            InsigniaEstudiante::where('matricula_id', $matricula->id)->where('tipo', 'cien_puntos')->delete();
        }

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
            $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();
            $matIds = $matriculas->pluck('id');
            $puntosPorMat    = PuntoEstudiante::whereIn('matricula_id', $matIds)->selectRaw('matricula_id, SUM(puntos) as total')->groupBy('matricula_id')->pluck('total', 'matricula_id');
            $insigniasPorMat = InsigniaEstudiante::whereIn('matricula_id', $matIds)->selectRaw('matricula_id, COUNT(*) as total')->groupBy('matricula_id')->pluck('total', 'matricula_id');
            $ranking = $matriculas->map(fn($m) => [
                'matricula' => $m,
                'total'     => (int) ($puntosPorMat[$m->id]    ?? 0),
                'insignias' => (int) ($insigniasPorMat[$m->id] ?? 0),
            ])->sortByDesc('total')->values();
        }

        $config = (object)['nombre_centro' => \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'))];

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
            $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                ->where('grupo_id', $grupoId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();
            $matIds = $matriculas->pluck('id');
            $puntosPorMat    = PuntoEstudiante::whereIn('matricula_id', $matIds)->selectRaw('matricula_id, SUM(puntos) as total')->groupBy('matricula_id')->pluck('total', 'matricula_id');
            $insigniasPorMat = InsigniaEstudiante::whereIn('matricula_id', $matIds)->selectRaw('matricula_id, COUNT(*) as total')->groupBy('matricula_id')->pluck('total', 'matricula_id');
            $ranking = $matriculas->map(fn($m) => [
                'matricula' => $m,
                'total'     => (int) ($puntosPorMat[$m->id]    ?? 0),
                'insignias' => (int) ($insigniasPorMat[$m->id] ?? 0),
            ])->sortByDesc('total')->values();
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

    /** Registros de asistencia mínimos para otorgar premios de asistencia/conducta (con 1 día todos eran "100 %"). */
    private const MIN_REGISTROS_ASISTENCIA = 20;

    /** @return bool true si creó el punto (false si el concepto ya estaba otorgado en esta matrícula) */
    private function crearPuntoSiNoExiste(int $matriculaId, string $concepto, string $categoria, int $puntos, $fecha): bool
    {
        $existe = PuntoEstudiante::where('matricula_id', $matriculaId)
            ->where('concepto', $concepto)
            ->exists();

        if ($existe) {
            return false;
        }

        PuntoEstudiante::create([
            'matricula_id' => $matriculaId,
            'concepto'     => $concepto,
            'categoria'    => $categoria,
            'puntos'       => $puntos,
            'fecha'        => $fecha,
        ]);

        return true;
    }

    /**
     * Sin faltas DEL AÑO ESCOLAR (antes: ninguna falta en toda la historia del estudiante, incluso
     * resueltas de años anteriores) y con datos suficientes (sin ellos, en la primera semana todos
     * "no tenían faltas").
     */
    private function sinFaltasEnElAnio(Matricula $matricula, $schoolYear): bool
    {
        if (Asistencia::where('matricula_id', $matricula->id)->count() < self::MIN_REGISTROS_ASISTENCIA) {
            return false;
        }

        $desde = $schoolYear?->fecha_inicio ?? now()->startOfYear();
        $hasta = $schoolYear?->fecha_fin    ?? now()->endOfYear();

        return ! FaltaDisciplinaria::where('estudiante_id', $matricula->estudiante_id)
            ->whereBetween('fecha', [$desde, $hasta])
            ->exists();
    }

    private function calcularPromedioMatricula(Matricula $matricula, $schoolYear): ?float
    {
        $notasTec = Calificacion::where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->get(['nota_final']);

        // Solo notas publicadas (igual que las técnicas): los puntos y la insignia son visibles para
        // el estudiante y no deben adelantar ni filtrar notas que el docente aún no publica.
        $notasAcad = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')
            ->get(['nota_final']);

        return (new \App\Services\PromedioEstudianteService())->calcular($notasAcad, $notasTec);
    }

    private function calcularPorcentajeAsistencia(Matricula $matricula): ?float
    {
        $asistencias = Asistencia::where('matricula_id', $matricula->id)->get(['estado']);
        $total = $asistencias->count();
        // Con pocos registros el porcentaje no significa nada (1 día presente = 100 %).
        if ($total < self::MIN_REGISTROS_ASISTENCIA) return null;

        // Mismos estados que cuentan como asistido en el resto del sistema (tarde y excusa incluidos).
        $presentes = $asistencias->whereIn('estado', Asistencia::ESTADOS_ASISTIDO)->count();
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
        if ($this->sinFaltasEnElAnio($matricula, $schoolYear)) {
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
