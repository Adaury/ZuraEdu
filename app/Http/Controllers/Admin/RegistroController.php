<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Grupo, SchoolYear, Matricula, Asignacion, Periodo, EvaluacionRegistro, Promocion, BoletinObservacion};
use App\Services\RegistroAcademicoService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RegistroController extends Controller
{
    public function __construct(private RegistroAcademicoService $svc) {}

    // ── Listado de grupos ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');

        $cicloFiltro = $request->get('ciclo'); // 'primer_ciclo' | 'segundo_ciclo'

        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->when($cicloFiltro, fn($q) => $q->whereHas('grado', fn($g) => $g->where('ciclo', $cicloFiltro)))
            ->orderBy('grado_id')
            ->get();

        $periodos = Periodo::where('school_year_id', $schoolYear->id)
            ->orderBy('numero')
            ->get();

        return view('admin.registro.index', compact('grupos', 'schoolYear', 'cicloFiltro', 'periodos'));
    }

    // ── Registro MINERD de un grupo ───────────────────────────────────────────

    public function show(Grupo $grupo, Request $request)
    {
        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $periodoId  = $request->get('periodo');

        $datos = $this->svc->buildRegistro($grupo, $schoolYear);

        // Periodo activo para filtrar el ingreso de notas
        $periodosAll = $datos['periodos'];
        $periodoActivo = $periodoId
            ? $periodosAll->firstWhere('id', $periodoId)
            : $periodosAll->firstWhere('activo', true) ?? $periodosAll->first();

        return view('admin.registro.show', array_merge($datos, [
            'periodoActivo' => $periodoActivo,
        ]));
    }

    // ── Guardar evaluación (AJAX cell-edit) ───────────────────────────────────

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'matricula_id'  => 'required|exists:matriculas,id',
            'asignacion_id' => 'required|exists:asignaciones,id',
            'periodo_id'    => 'required|exists:periodos,id',
            'school_year_id'=> 'required|exists:school_years,id',
            'tipo'          => 'required|in:indicador,competencia',
            'referencia_id' => 'required|integer|min:1',
            'valor'         => 'nullable|numeric',
        ]);

        $valor = $request->input('valor');

        if ($valor !== null) {
            $eval = $this->svc->guardarEvaluacion(
                $request->matricula_id,
                $request->asignacion_id,
                $request->periodo_id,
                $request->school_year_id,
                $request->tipo,
                $request->referencia_id,
                $valor,
                Auth::id()
            );
        } else {
            // Borrar la evaluación si el valor está vacío
            EvaluacionRegistro::where([
                'matricula_id'   => $request->matricula_id,
                'asignacion_id'  => $request->asignacion_id,
                'periodo_id'     => $request->periodo_id,
                'indicador_id'   => $request->tipo === 'indicador'   ? $request->referencia_id : null,
                'competencia_id' => $request->tipo === 'competencia' ? $request->referencia_id : null,
            ])->delete();
        }

        // Recalcular promedios en tiempo real
        $promedios = $this->recalcularPromedios($request);

        return response()->json(['ok' => true, 'promedios' => $promedios]);
    }

    // ── Guardar observación del boletín ───────────────────────────────────────

    public function guardarObservacion(Request $request): JsonResponse
    {
        $request->validate([
            'matricula_id'   => 'required|exists:matriculas,id',
            'school_year_id' => 'required|exists:school_years,id',
            'periodo_id'     => 'nullable|exists:periodos,id',
            'tipo'           => 'required|in:academica,conducta,sugerencia,general',
            'contenido'      => 'required|string|max:1000',
        ]);

        $docente = Auth::user()->docente ?? null;

        BoletinObservacion::updateOrCreate(
            [
                'matricula_id'   => $request->matricula_id,
                'school_year_id' => $request->school_year_id,
                'periodo_id'     => $request->periodo_id,
                'tipo'           => $request->tipo,
            ],
            ['contenido' => $request->contenido, 'docente_id' => $docente?->id]
        );

        return response()->json(['ok' => true]);
    }

    // ── Calcular y publicar promociones de un grupo ──────────────────────────

    public function calcularPromociones(Grupo $grupo): JsonResponse
    {
        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $matriculas = Matricula::where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->with(['estudiante', 'grupo.grado'])
            ->get();

        // buildRegistro una sola vez para todo el grupo (4 queries en lugar de 4N)
        $registroGrupo = $this->svc->buildRegistro($grupo, $schoolYear);

        $resultados = [];
        foreach ($matriculas as $m) {
            $promo = $this->svc->calcularPromocion($m, $schoolYear, $registroGrupo);
            $resultados[] = [
                'estudiante' => $m->estudiante?->nombre_completo ?? '(sin nombre)',
                'estado'     => $promo->estado_label,
                'promedio'   => $promo->promedio_final,
            ];
        }

        return response()->json(['ok' => true, 'resultados' => $resultados]);
    }

    // ── Vista enfocada por materia (MINERD por asignación) ───────────────────

    public function calificaciones(Grupo $grupo, Request $request)
    {
        $schoolYear   = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $asignacionId = $request->get('asignacion_id');
        $periodoId    = $request->get('periodo_id');

        $ciclo = $grupo->grado->ciclo ?? 'primer_ciclo';

        // Todos los grupos activos (para el selector de grupo + sus asignaciones)
        $grupos = Grupo::with(['grado', 'seccion', 'asignaciones' => fn($q) =>
                $q->with(['asignatura', 'docente'])
                  ->where('school_year_id', $schoolYear->id)
                  ->where('activo', true)
                  ->orderBy('id')])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->orderBy('grado_id')
            ->get();

        // Asignaciones activas del grupo para el selector
        $asignaciones = Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        $periodos = $this->getPeriodos($schoolYear);

        // Si no hay selección, mostrar solo el panel de selección
        if (! $asignacionId || ! $periodoId) {
            return view('admin.registro.calificaciones', compact(
                'grupo', 'ciclo', 'grupos', 'asignaciones', 'periodos', 'schoolYear'
            ));
        }

        $asignacion = Asignacion::with([
            'asignatura.competenciasActivas' => fn($q) => $q->where('ciclo', $ciclo)
                ->orderBy('orden')
                ->with(['indicadoresActivos']),
            'docente',
        ])->findOrFail($asignacionId);

        $periodo = Periodo::findOrFail($periodoId);

        // Estudiantes
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->orderBy('numero_orden')
            ->get();

        $matriculaIds = $matriculas->pluck('id');

        // Evaluaciones para esta asignación (todos los períodos para calcular promedios)
        $rawEvals = EvaluacionRegistro::whereIn('matricula_id', $matriculaIds)
            ->where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->get();

        // Mapa: [matricula_id][ref_key][periodo_id] = valor_cualitativo
        // ref_key = "il_{id}" o "ce_{id}"
        $evalMap = [];
        foreach ($rawEvals as $e) {
            $key = $e->indicador_id ? "il_{$e->indicador_id}" : "ce_{$e->competencia_id}";
            $evalMap[$e->matricula_id][$key][$e->periodo_id] = $e->valor_cualitativo ?? $e->nota_numerica;
        }

        return view('admin.registro.calificaciones', compact(
            'grupo', 'ciclo', 'grupos', 'asignaciones', 'asignacion', 'periodos', 'periodo',
            'matriculas', 'evalMap', 'schoolYear'
        ));
    }

    // ── PDF de calificaciones por materia ─────────────────────────────────────

    public function calificacionesPdf(Grupo $grupo, Request $request)
    {
        $schoolYear   = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $asignacionId = $request->get('asignacion_id');
        $periodoId    = $request->get('periodo_id');
        $ciclo        = $grupo->grado->ciclo ?? 'primer_ciclo';

        $asignacion = Asignacion::with([
            'asignatura.competenciasActivas' => fn($q) => $q->where('ciclo', $ciclo)
                ->orderBy('orden')->with(['indicadoresActivos']),
            'docente',
        ])->findOrFail($asignacionId);

        $periodo = Periodo::findOrFail($periodoId);

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->orderBy('numero_orden')
            ->get();

        $matriculaIds = $matriculas->pluck('id');

        $rawEvals = EvaluacionRegistro::whereIn('matricula_id', $matriculaIds)
            ->where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->get();

        $evalMap = [];
        foreach ($rawEvals as $e) {
            $key = $e->indicador_id ? "il_{$e->indicador_id}" : "ce_{$e->competencia_id}";
            $evalMap[$e->matricula_id][$key][$e->periodo_id] = $e->valor_cualitativo ?? $e->nota_numerica;
        }

        $periodos = $this->getPeriodos($schoolYear);
        $grupo->load(['grado', 'seccion']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.registro.calificaciones_pdf', compact(
            'grupo', 'ciclo', 'asignacion', 'periodo', 'periodos',
            'matriculas', 'evalMap', 'schoolYear'
        ))->setPaper('letter', 'landscape');

        $nombre = 'Registro_' . \Str::slug($asignacion->asignatura->nombre) .
                  '_' . $grupo->seccion->nombre . '_P' . $periodo->numero . '.pdf';

        return $pdf->download($nombre);
    }

    // ── Guardar lote de evaluaciones ──────────────────────────────────────────

    public function guardarLote(Request $request): JsonResponse
    {
        $request->validate([
            'evaluaciones'              => 'required|array|min:1',
            'evaluaciones.*.matricula_id'   => 'required|exists:matriculas,id',
            'evaluaciones.*.asignacion_id'  => 'required|exists:asignaciones,id',
            'evaluaciones.*.periodo_id'     => 'required|exists:periodos,id',
            'evaluaciones.*.school_year_id' => 'required|exists:school_years,id',
            'evaluaciones.*.tipo'           => 'required|in:indicador,competencia',
            'evaluaciones.*.referencia_id'  => 'required|integer|min:1',
            'evaluaciones.*.valor'          => 'nullable|numeric',
        ]);

        $guardadas = 0;
        foreach ($request->evaluaciones as $item) {
            $valor = $item['valor'];
            if ($valor !== null && $valor !== '') {
                $this->svc->guardarEvaluacion(
                    $item['matricula_id'],
                    $item['asignacion_id'],
                    $item['periodo_id'],
                    $item['school_year_id'],
                    $item['tipo'],
                    $item['referencia_id'],
                    $valor,
                    Auth::id()
                );
                $guardadas++;
            }
        }

        return response()->json(['ok' => true, 'guardadas' => $guardadas]);
    }

    // ── Exportar PDF del registro de un grupo ────────────────────────────────

    public function exportarPdf(Grupo $grupo)
    {
        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $datos = $this->svc->buildRegistro($grupo, $schoolYear);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.registro.pdf', $datos)
            ->setPaper('a3', 'landscape');

        $nombre = 'Registro_' . str_replace(' ', '_', $grupo->grado->nombre) .
                  '_' . $grupo->seccion->nombre . '_' . $schoolYear->nombre . '.pdf';

        return $pdf->download($nombre);
    }

    // ── Exportar Excel del registro de un grupo ──────────────────────────────

    public function exportarExcel(Grupo $grupo)
    {
        $schoolYear   = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $datos        = $this->svc->buildRegistro($grupo, $schoolYear);
        $ciclo        = $datos['ciclo'];
        $asignaciones = $datos['asignaciones'];
        $registro     = $datos['registro'];
        $umbral       = $ciclo === 'primer_ciclo' ? 2.5 : 65;

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Registro');

        $totalCols = 2 + $asignaciones->count() + 2;
        $lastCol   = chr(64 + $totalCols);

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'REGISTRO ACADÉMICO — ' . $grupo->nombre_completo . ' — ' . $schoolYear->nombre);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Estudiante');
        $col = 3;
        foreach ($asignaciones as $asig) {
            $sheet->setCellValue(chr(64 + $col) . '2', \Str::limit($asig->asignatura?->nombre ?? '—', 14));
            $col++;
        }
        $promCol = $col;
        $sheet->setCellValue(chr(64 + $promCol) . '2', 'Prom.');
        $col++;
        $sitCol = $col;
        $sheet->setCellValue(chr(64 + $sitCol) . '2', 'Situación');
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(28);

        foreach ($registro as $i => $row) {
            $r        = $i + 3;
            $matricula = $row['matricula'];
            $sheet->setCellValue("A{$r}", $i + 1);
            $sheet->setCellValue("B{$r}", trim(($matricula->estudiante?->apellidos ?? '') . ', ' . ($matricula->estudiante?->nombres ?? '')));

            $col = 3;
            foreach ($row['materias'] as $materia) {
                $nota = $materia['promedio'];
                $cell = chr(64 + $col) . $r;
                $sheet->setCellValue($cell, $nota !== null ? number_format($nota, 1) : '—');
                if ($nota !== null) {
                    $rgb = $materia['aprobada'] ? '065f46' : '991b1b';
                    $sheet->getStyle($cell)->getFont()->getColor()->setRGB($rgb);
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }
                $col++;
            }

            $prom     = $row['promedio_general'];
            $promCell = chr(64 + $promCol) . $r;
            $sheet->setCellValue($promCell, $prom !== null ? number_format($prom, 1) : '—');
            if ($prom !== null) {
                $rgb = $prom >= $umbral ? '065f46' : '991b1b';
                $sheet->getStyle($promCell)->getFont()->getColor()->setRGB($rgb);
                $sheet->getStyle($promCell)->getFont()->setBold(true);
            }

            $sit     = $prom !== null ? ($prom >= $umbral ? 'Aprobado' : 'Reprobado') : '—';
            $sitCell = chr(64 + $sitCol) . $r;
            $sheet->setCellValue($sitCell, $sit);
            if ($sit === 'Aprobado') {
                $sheet->getStyle($sitCell)->getFont()->getColor()->setRGB('065f46');
            } elseif ($sit === 'Reprobado') {
                $sheet->getStyle($sitCell)->getFont()->getColor()->setRGB('991b1b');
            }

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
        }

        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->freezePane('C3');

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'reg_') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'Registro_' . \Str::slug($grupo->grado?->nombre ?? 'grupo') . '_' . ($grupo->seccion?->nombre ?? '') . '_' . $schoolYear->nombre . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend();
    }

    // ── Helper: recalcular promedios en tiempo real ───────────────────────────

    private function recalcularPromedios(Request $request): array
    {
        $evals = EvaluacionRegistro::where('matricula_id', $request->matricula_id)
            ->where('asignacion_id', $request->asignacion_id)
            ->where('school_year_id', $request->school_year_id)
            ->get();

        $numericVals = $evals->pluck('nota_numerica')->filter()->values();
        $cualVals    = $evals->pluck('valor_cualitativo')->filter()->values();

        return [
            'promedio_numerico'   => $numericVals->count()
                ? round($numericVals->avg(), 2) : null,
            'promedio_cualitativo'=> $cualVals->count()
                ? round($cualVals->avg(), 2) : null,
        ];
    }
}
