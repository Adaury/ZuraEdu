<?php
namespace App\Services;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
class SigerdExportService
{
    private string $headerColor = '1e3a6e';
    private string $altRowColor = 'f0f4ff';

    public function exportarNomina(SchoolYear $sy, ?int $grupoId, string $formato)
    {
        set_time_limit(300);
        $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $sy->id)->where('estado', 'activa')
            ->when($grupoId, fn ($q, $id) => $q->where('grupo_id', $id))->get();
        $headers = ['No.', 'RNE/Cedula', 'Nombres', 'Apellidos', 'Sexo', 'Fecha Nac.', 'Lugar Nac.', 'Municipio', 'Provincia', 'Grado', 'Seccion', 'Modalidad', 'Estado'];
        $rows = [];
        foreach ($matriculas as $i => $m) {
            $e = $m->estudiante;
            $rows[] = [
                $i + 1, $e?->cedula ?? '', $e?->nombres ?? '', $e?->apellidos ?? '', $e?->sexo ?? '',
                $e?->fecha_nacimiento ? $e->fecha_nacimiento->format('d/m/Y') : '',
                $e?->lugar_nacimiento ?? '', $e?->municipio ?? '', $e?->provincia ?? '',
                $m->grupo?->grado?->nombre ?? '', $m->grupo?->seccion?->nombre ?? '', 'Regular', $e?->estado ?? '',
            ];
        }
        $filename = 'SIGERD_Nomina_' . date('Ymd_His');
        return match ($formato) {
            'excel' => $this->excelResponse($headers, $rows, 'Nomina Matricula', $filename . '.xlsx'),
            'csv'   => $this->csvResponse($headers, $rows, $filename . '.csv'),
            'pdf'   => Pdf::loadView('admin.sigerd.exports.nomina_pdf', compact('matriculas'))->download($filename . '.pdf'),
            default => $this->excelResponse($headers, $rows, 'Nomina Matricula', $filename . '.xlsx'),
        };
    }

    public function exportarCalificaciones(SchoolYear $sy, int $grupoId, ?int $periodoId, string $formato)
    {
        set_time_limit(300);
        $asignaciones = Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $grupoId)->where('school_year_id', $sy->id)->get();
        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $grupoId)->where('school_year_id', $sy->id)->where('estado', 'activa')->get();

        // Bulk-load calificaciones: 1 query en lugar de N×M
        $matIds  = $matriculas->pluck('id');
        $asigIds = $asignaciones->pluck('id');
        $calMap  = CalificacionAcademica::whereIn('matricula_id', $matIds)
            ->whereIn('asignacion_id', $asigIds)
            ->get()
            ->keyBy(fn($c) => $c->matricula_id . '_' . $c->asignacion_id);

        $filename = 'SIGERD_Calificaciones_' . $grupoId . '_' . date('Ymd_His');
        if ($formato === 'excel') {
            $spreadsheet = new Spreadsheet();
            $first = true;
            foreach ($asignaciones as $asig) {
                $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
                $first = false;
                $sheet->setTitle(mb_substr($asig->asignatura?->nombre ?? 'Asignatura', 0, 30));
                $hdr = ['No.', 'RNE', 'Apellidos', 'Nombres', 'P1', 'P2', 'P3', 'P4', 'N.F.', 'Situacion'];
                $this->applySheetHeaders($sheet, $hdr);
                $row = 2;
                foreach ($matriculas as $i => $m) {
                    $cal = $calMap->get($m->id . '_' . $asig->id);
                    $p1 = $cal ? round(((float)$cal->avg_comp1_p1 + (float)$cal->avg_comp2_p1 + (float)$cal->avg_comp3_p1 + (float)$cal->avg_comp4_p1) / 4, 2) : '';
                    $p2 = $cal ? round(((float)$cal->avg_comp1_p2 + (float)$cal->avg_comp2_p2 + (float)$cal->avg_comp3_p2 + (float)$cal->avg_comp4_p2) / 4, 2) : '';
                    $p3 = $cal ? round(((float)$cal->avg_comp1_p3 + (float)$cal->avg_comp2_p3 + (float)$cal->avg_comp3_p3 + (float)$cal->avg_comp4_p3) / 4, 2) : '';
                    $p4 = $cal ? round(((float)$cal->avg_comp1_p4 + (float)$cal->avg_comp2_p4 + (float)$cal->avg_comp3_p4 + (float)$cal->avg_comp4_p4) / 4, 2) : '';
                    $rd = [$i+1, $m->estudiante?->cedula??'', $m->estudiante?->apellidos??'', $m->estudiante?->nombres??'', $p1,$p2,$p3,$p4, $cal?->nota_final??'', $cal?->situacion??''];
                    $col = 'A';
                    foreach ($rd as $val) { $sheet->setCellValue($col . $row, $val); $col++; }
                    if ($row % 2 === 0) {
                        $lc = chr(ord('A') + count($rd) - 1);
                        $sheet->getStyle('A' . $row . ':' . $lc . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($this->altRowColor);
                    }
                    $row++;
                }
                foreach (range('A', chr(ord('A') + count($hdr) - 1)) as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }
                $sheet->freezePane('A2');
            }
            return response()->streamDownload(function () use ($spreadsheet) {
                (new Xlsx($spreadsheet))->save('php://output');
            }, $filename . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }
        $headers = ['No.', 'RNE', 'Apellidos', 'Nombres', 'Asignatura', 'P1', 'P2', 'P3', 'P4', 'N.F.', 'Situacion'];
        $rows = []; $idx = 1;
        foreach ($asignaciones as $asig) {
            foreach ($matriculas as $m) {
                $cal = $calMap->get($m->id . '_' . $asig->id);
                $rows[] = [$idx++, $m->estudiante?->cedula??'', $m->estudiante?->apellidos??'', $m->estudiante?->nombres??'', $asig->asignatura?->nombre??'', $cal?->nota_final??'', $cal?->nota_final??'', $cal?->nota_final??'', $cal?->nota_final??'', $cal?->nota_final??'', $cal?->situacion??''];
            }
        }
        if ($formato === 'csv') return $this->csvResponse($headers, $rows, $filename . '.csv');

        // Construir filas estructuradas para el PDF
        $filasPdf = [];
        foreach ($asignaciones as $asig) {
            $filasAsig = [];
            foreach ($matriculas as $m) {
                $cal = $calMap->get($m->id . '_' . $asig->id);
                $filasAsig[] = [
                    'estudiante' => $m->estudiante,
                    'cedula'     => $m->estudiante?->cedula ?? '',
                    'nombre'     => ($m->estudiante?->apellidos ?? '') . ', ' . ($m->estudiante?->nombres ?? ''),
                    'p1' => $cal ? round((((float)$cal->avg_comp1_p1 + (float)$cal->avg_comp2_p1 + (float)$cal->avg_comp3_p1 + (float)$cal->avg_comp4_p1) / 4), 2) : null,
                    'p2' => $cal ? round((((float)$cal->avg_comp1_p2 + (float)$cal->avg_comp2_p2 + (float)$cal->avg_comp3_p2 + (float)$cal->avg_comp4_p2) / 4), 2) : null,
                    'p3' => $cal ? round((((float)$cal->avg_comp1_p3 + (float)$cal->avg_comp2_p3 + (float)$cal->avg_comp3_p3 + (float)$cal->avg_comp4_p3) / 4), 2) : null,
                    'p4' => $cal ? round((((float)$cal->avg_comp1_p4 + (float)$cal->avg_comp2_p4 + (float)$cal->avg_comp3_p4 + (float)$cal->avg_comp4_p4) / 4), 2) : null,
                    'nota_final' => $cal?->nota_final,
                    'situacion'  => $cal?->situacion ?? '',
                ];
            }
            $filasPdf[] = ['asignacion' => $asig, 'filas' => $filasAsig];
        }
        return Pdf::loadView('admin.sigerd.exports.calificaciones_pdf', [
            'filasPdf' => $filasPdf, 'sy' => $sy,
        ])->setPaper('letter', 'landscape')->download($filename . '.pdf');
    }

    public function exportarDocentes(SchoolYear $sy, string $formato)
    {
        set_time_limit(300);
        $asignacionesGrupo = Asignacion::with(['docente', 'asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $sy->id)->get()->groupBy('docente_id');
        $headers = ['No.', 'Cedula', 'Nombres', 'Apellidos', 'Especialidad', 'Titulo Academico', 'Cargo', 'Asignatura(s)', 'Grado/Grupo'];
        $rows = []; $idx = 1; $docenteRows = [];
        foreach ($asignacionesGrupo as $docenteId => $group) {
            $docente     = $group->first()?->docente;
            $asignaturas = $group->pluck('asignatura.nombre')->unique()->filter()->implode(', ');
            $grupos      = $group->map(fn ($a) => $a->grupo?->nombre_completo ?? '')->unique()->filter()->implode(', ');
            $rows[]       = [$idx++, $docente?->cedula??'', $docente?->nombres??'', $docente?->apellidos??'', $docente?->especialidad??'', $docente?->titulo_academico??'', $docente?->cargo??'', $asignaturas, $grupos];
            $docenteRows[] = compact('docente', 'asignaturas', 'grupos');
        }
        $filename = 'SIGERD_Docentes_' . date('Ymd_His');
        return match ($formato) {
            'csv'   => $this->csvResponse($headers, $rows, $filename . '.csv'),
            'pdf'   => Pdf::loadView('admin.sigerd.exports.docentes_pdf', [
                            'docenteRows' => $docenteRows, 'sy' => $sy,
                       ])->download($filename . '.pdf'),
            default => $this->excelResponse($headers, $rows, 'Docentes', $filename . '.xlsx'),
        };
    }

    public function exportarAsistencia(SchoolYear $sy, ?int $grupoId, string $desde, string $hasta, string $formato)
    {
        set_time_limit(300);
        $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $sy->id)->where('estado', 'activa')
            ->when($grupoId, fn ($q, $id) => $q->where('grupo_id', $id))->get();

        $matIds  = $matriculas->pluck('id');
        $asisMap = Asistencia::whereIn('matricula_id', $matIds)
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('matricula_id, estado, COUNT(*) as total')
            ->groupBy('matricula_id', 'estado')
            ->get()
            ->groupBy('matricula_id');

        $headers = ['No.', 'RNE', 'Nombres', 'Apellidos', 'Grado', 'Seccion', 'Total Dias', 'Presentes', 'Tardanzas', 'Ausentes', 'Justificados', '% Asistencia'];
        $rows = []; $filasPdf = [];
        foreach ($matriculas as $i => $m) {
            $stats = $asisMap->get($m->id, collect())->keyBy('estado');
            $pres  = (int)($stats->get('presente')?->total   ?? 0);
            $tard  = (int)($stats->get('tardanza')?->total   ?? 0);
            $ause  = (int)($stats->get('ausente')?->total    ?? 0);
            $just  = (int)($stats->get('justificado')?->total ?? 0);
            $total = $pres + $tard + $ause + $just;
            $pct   = $total > 0 ? round(($pres + $tard + $just) * 100 / $total, 2) : 0;
            $rows[]      = [$i+1, $m->estudiante?->cedula??'', $m->estudiante?->nombres??'', $m->estudiante?->apellidos??'', $m->grupo?->grado?->nombre??'', $m->grupo?->seccion?->nombre??'', $total, $pres, $tard, $ause, $just, $pct.'%'];
            $filasPdf[]  = ['matricula' => $m, 'pres' => $pres, 'tard' => $tard, 'ause' => $ause, 'just' => $just, 'total' => $total, 'pct' => $pct];
        }
        $filename = 'SIGERD_Asistencia_' . date('Ymd_His');
        return match ($formato) {
            'csv'   => $this->csvResponse($headers, $rows, $filename . '.csv'),
            'pdf'   => Pdf::loadView('admin.sigerd.exports.asistencia_pdf', [
                            'filasPdf' => $filasPdf, 'desde' => $desde, 'hasta' => $hasta, 'sy' => $sy,
                       ])->download($filename . '.pdf'),
            default => $this->excelResponse($headers, $rows, 'Asistencia', $filename . '.xlsx'),
        };
    }

    public function validarNomina(SchoolYear $sy, ?int $grupoId): array
    {
        $matriculas = Matricula::with('estudiante')->where('school_year_id', $sy->id)->where('estado', 'activa')
            ->when($grupoId, fn ($q, $id) => $q->where('grupo_id', $id))->get();
        $errores = []; $cedulasVistas = [];
        foreach ($matriculas as $i => $m) {
            $e = $m->estudiante;
            $name = trim(($e?->nombres ?? '') . '  ' . ($e?->apellidos ?? ''));
            $no = $i + 1;
            if (empty($e?->cedula)) {
                $errores[] = ['no' => $no, 'nombre' => $name, 'descripcion' => 'Sin cedula/RNE'];
            } elseif (in_array($e->cedula, $cedulasVistas)) {
                $errores[] = ['no' => $no, 'nombre' => $name, 'descripcion' => 'Cedula duplicada: ' . $e->cedula];
            }
            if (!empty($e?->cedula)) { $cedulasVistas[] = $e->cedula; }
            if (empty($e?->fecha_nacimiento)) {
                $errores[] = ['no' => $no, 'nombre' => $name, 'descripcion' => 'Sin fecha de nacimiento'];
            }
        }
        return ['ok' => empty($errores), 'errores' => $errores, 'total' => $matriculas->count()];
    }

    public function validarCalificaciones(SchoolYear $sy, int $grupoId, ?int $periodoId): array
    {
        $matriculas = Matricula::with('estudiante')->where('grupo_id', $grupoId)->where('school_year_id', $sy->id)->where('estado', 'activa')->get();

        // Bulk-load calificaciones: 1 query en lugar de N (1 por matricula)
        $matIds = $matriculas->pluck('id');
        $calMap = CalificacionAcademica::whereIn('matricula_id', $matIds)
            ->get()->groupBy('matricula_id');

        $errores = [];
        foreach ($matriculas as $i => $m) {
            $name = trim(($m->estudiante?->nombres??'')  . '  ' . ($m->estudiante?->apellidos??''));
            $cals = $calMap->get($m->id, collect());
            if ($cals->isEmpty()) {
                $errores[] = ['no' => $i+1, 'nombre' => $name, 'descripcion' => 'Sin calificaciones registradas']; continue;
            }
            foreach ($cals as $cal) {
                if ($cal->nota_final !== null && ((float)$cal->nota_final < 0 || (float)$cal->nota_final > 100)) {
                    $errores[] = ['no' => $i+1, 'nombre' => $name, 'descripcion' => 'Nota final fuera de rango (0-100): ' . $cal->nota_final];
                }
            }
        }
        return ['ok' => empty($errores), 'errores' => $errores, 'total' => $matriculas->count()];
    }

    private function excelResponse(array $headers, array $rows, string $sheetTitle, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
        $this->applySheetHeaders($sheet, $headers);
        $row = 2;
        foreach ($rows as $rowData) {
            $col = 'A';
            foreach ($rowData as $val) { $sheet->setCellValue($col . $row, $val); $col++; }
            if ($row % 2 === 0) {
                $lc = chr(ord('A') + count($rowData) - 1);
                $sheet->getStyle('A' . $row . ':' . $lc . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($this->altRowColor);
            }
            $row++;
        }
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
        $sheet->freezePane('A2');
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function applySheetHeaders($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) { $sheet->setCellValue($col . '1', $header); $col++; }
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->headerColor]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function csvResponse(array $headers, array $rows, string $filename)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $headers);
        foreach ($rows as $row) { fputcsv($handle, $row); }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
