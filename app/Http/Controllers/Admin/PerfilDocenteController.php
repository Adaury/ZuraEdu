<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Planificacion;
use App\Models\PlanClase;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\Auth;

class PerfilDocenteController extends Controller
{
    public function show(Docente $docente)
    {
        $schoolYear = SchoolYear::actual();

        $docente->load([
            'user',
            'especialidades',
            'especialidadesCoordinadas',
            'asignaciones' => function ($q) use ($schoolYear) {
                if ($schoolYear) {
                    $q->where('school_year_id', $schoolYear->id)
                      ->where('activo', true)
                      ->with(['asignatura', 'grupo.grado', 'grupo.seccion', 'grupo.matriculas']);
                }
            },
        ]);

        $asignacionIds = $docente->asignaciones->pluck('id');

        // Planificaciones técnicas del año
        $planificaciones = collect();
        if ($schoolYear && $asignacionIds->isNotEmpty()) {
            $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.grupo'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('school_year_id', $schoolYear->id)
                ->latest()->get();
        }

        // Planes de clase del año
        $planesClase = collect();
        if ($schoolYear && $asignacionIds->isNotEmpty()) {
            $planesClase = PlanClase::whereIn('asignacion_id', $asignacionIds)
                ->where('school_year_id', $schoolYear->id)
                ->latest()->get();
        }

        // Observaciones emitidas por este docente
        $observacionesEmitidas = Observacion::with(['estudiante', 'asignacion.asignatura'])
            ->where('docente_id', $docente->id)
            ->when($schoolYear, fn($q) => $q->whereHas('asignacion',
                fn($s) => $s->where('school_year_id', $schoolYear->id)
            ))
            ->latest()->limit(20)->get();

        $rendimiento = $schoolYear
            ? $this->buildRendimiento($docente->asignaciones, $schoolYear)
            : [];

        return view('admin.perfiles.docente', compact(
            'docente', 'schoolYear',
            'planificaciones', 'planesClase', 'observacionesEmitidas',
            'rendimiento'
        ));
    }

    // ── Informe de actividad docente PDF ─────────────────────────────────
    public function informePdf(Docente $docente)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $docente->load([
            'user',
            'asignaciones' => fn($q) => $q->where('school_year_id', $schoolYear->id)->where('activo', true)
                ->with(['asignatura', 'grupo.grado', 'grupo.seccion', 'grupo.matriculas']),
        ]);

        $asignacionIds = $docente->asignaciones->pluck('id');

        $planificaciones = $asignacionIds->isNotEmpty()
            ? Planificacion::whereIn('asignacion_id', $asignacionIds)->where('school_year_id', $schoolYear->id)->get()
            : collect();

        $planesClase = $asignacionIds->isNotEmpty()
            ? PlanClase::whereIn('asignacion_id', $asignacionIds)->where('school_year_id', $schoolYear->id)->get()
            : collect();

        $rendimiento = $this->buildRendimiento($docente->asignaciones, $schoolYear);

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.docente_pdf',
            compact('docente', 'schoolYear', 'planificaciones', 'planesClase', 'rendimiento', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($docente->nombre_completo ?? 'docente');
        return $pdf->download("informe_docente_{$slug}.pdf");
    }

    // ── Excel informe de actividad docente ───────────────────────────────
    public function informeExcel(Docente $docente)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $docente->load([
            'user',
            'asignaciones' => fn($q) => $q->where('school_year_id', $schoolYear->id)->where('activo', true)
                ->with(['asignatura', 'grupo.grado', 'grupo.seccion', 'grupo.matriculas']),
        ]);

        $asignacionIds = $docente->asignaciones->pluck('id');

        $planificaciones = $asignacionIds->isNotEmpty()
            ? Planificacion::whereIn('asignacion_id', $asignacionIds)->where('school_year_id', $schoolYear->id)->get()
            : collect();

        $planesClase = $asignacionIds->isNotEmpty()
            ? PlanClase::whereIn('asignacion_id', $asignacionIds)->where('school_year_id', $schoolYear->id)->get()
            : collect();

        $rendimiento = $this->buildRendimiento($docente->asignaciones, $schoolYear);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Informe Docente');

        // Encabezado
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Informe de Actividad Docente — ' . $schoolYear->nombre);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', 'Docente: ' . $docente->nombre_completo . ' | Fecha: ' . now()->format('d/m/Y'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Resumen
        $sheet->mergeCells('A5:H5');
        $sheet->setCellValue('A5', 'RESUMEN');
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('e0e7ff');

        $sheet->setCellValue('A6', 'Asignaciones activas');  $sheet->setCellValue('B6', $docente->asignaciones->count());
        $sheet->setCellValue('A7', 'Planificaciones');        $sheet->setCellValue('B7', $planificaciones->count());
        $sheet->setCellValue('A8', 'Planes de clase');        $sheet->setCellValue('B8', $planesClase->count());

        // Tabla rendimiento
        $headers = ['#', 'Asignatura', 'Grupo', 'Total Est.', 'Con Nota', 'Promedio', 'Aprobados', 'Reprobados'];
        $col = 'A'; $row = 10;
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        $i = 1;
        foreach ($docente->asignaciones as $asig) {
            $r = $rendimiento[$asig->id] ?? [];
            $row++;
            $bg = ($i % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $i++);
            $sheet->setCellValue('B' . $row, $asig->asignatura?->nombre);
            $sheet->setCellValue('C' . $row, $asig->grupo?->nombre_completo);
            $sheet->setCellValue('D' . $row, $r['total'] ?? 0);
            $sheet->setCellValue('E' . $row, $r['con_nota'] ?? 0);
            $sheet->setCellValue('F' . $row, $r['promedio'] ?? '—');
            $sheet->setCellValue('G' . $row, $r['aprobados'] ?? 0);
            $sheet->setCellValue('H' . $row, $r['reprobados'] ?? 0);
            $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            if (($r['reprobados'] ?? 0) > 0) {
                $sheet->getStyle("H{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fee2e2');
            }
        }

        foreach (['A'=>5,'B'=>30,'C'=>22,'D'=>12,'E'=>12,'F'=>12,'G'=>12,'H'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug   = \Illuminate\Support\Str::slug($docente->nombre_completo ?? 'docente');
        $filename = "informe_docente_{$slug}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function miPerfil()
    {
        $user    = Auth::user();
        $docente = $user->docente;

        if (!$docente) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Tu cuenta no tiene un perfil de docente asociado.');
        }

        return $this->show($docente);
    }

    private function buildRendimiento(\Illuminate\Database\Eloquent\Collection $asignaciones, SchoolYear $schoolYear): array
    {
        if ($asignaciones->isEmpty()) return [];

        $grupoIds      = $asignaciones->pluck('grupo_id')->unique();
        $asignacionIds = $asignaciones->pluck('id');

        $matriculasPorGrupo = Matricula::whereIn('grupo_id', $grupoIds)
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->get()
            ->groupBy('grupo_id');

        $allMatIds = $matriculasPorGrupo->flatten(1)->pluck('id');

        $califsPorAsig = CalificacionAcademica::whereIn('asignacion_id', $asignacionIds)
            ->whereNotNull('nota_final')
            ->when($allMatIds->isNotEmpty(), fn($q) => $q->whereIn('matricula_id', $allMatIds))
            ->get()
            ->groupBy('asignacion_id');

        $result = [];
        foreach ($asignaciones as $asig) {
            $total  = $matriculasPorGrupo->get($asig->grupo_id, collect())->count();
            $califs = $califsPorAsig->get($asig->id, collect());

            $result[$asig->id] = [
                'total'      => $total,
                'con_nota'   => $califs->count(),
                'promedio'   => $califs->avg('nota_final') ? round($califs->avg('nota_final'), 1) : null,
                'aprobados'  => $califs->where('situacion', 'A')->count(),
                'reprobados' => $califs->where('situacion', 'R')->count(),
            ];
        }

        return $result;
    }
}
