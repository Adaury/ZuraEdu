<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Observacion;
use App\Models\SchoolYear;

class PerfilEstudianteController extends Controller
{
    public function show(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();

        $estudiante->load([
            'user',
            'matriculas' => function ($q) {
                $q->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
                  ->orderByDesc('school_year_id');
            },
        ]);

        // Matrícula activa del año actual
        $matriculaActual = $schoolYear
            ? $estudiante->matriculas->firstWhere('school_year_id', $schoolYear->id)
            : null;

        // Calificaciones académicas del año actual
        $calificaciones = collect();
        if ($matriculaActual) {
            $calificaciones = CalificacionAcademica::where('matricula_id', $matriculaActual->id)
                ->where('school_year_id', $schoolYear->id)
                ->with(['asignacion.asignatura'])
                ->get();
        }

        // Promedio general
        $promedio = $calificaciones->whereNotNull('nota_final')->avg('nota_final');

        // Estado académico
        $estado = 'activo';
        if ($promedio !== null) {
            if ($promedio < 70) $estado = 'riesgo';
            if ($calificaciones->where('nota_final', '<', 70)->count() >= 3) $estado = 'baja';
        }

        // Asistencia del año actual
        $resumenAsistencia = ['total' => 0, 'presentes' => 0, 'ausentes' => 0, 'tardanzas' => 0, 'porcentaje' => null, 'por_materia' => collect()];
        if ($matriculaActual) {
            $asistencias = Asistencia::with('asignacion.asignatura')
                ->where('matricula_id', $matriculaActual->id)
                ->orderBy('fecha', 'desc')
                ->get();

            $total     = $asistencias->count();
            $presentes = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();
            $ausentes  = $asistencias->where('estado', 'ausente')->count();
            $tardanzas = $asistencias->where('estado', 'tardanza')->count();

            $porMateria = $asistencias->groupBy('asignacion_id')->map(function ($rows) {
                $t = $rows->count();
                $p = $rows->whereIn('estado', ['presente', 'tardanza'])->count();
                return [
                    'asignatura'  => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
                    'total'       => $t,
                    'presentes'   => $p,
                    'ausentes'    => $rows->where('estado', 'ausente')->count(),
                    'tardanzas'   => $rows->where('estado', 'tardanza')->count(),
                    'porcentaje'  => $t > 0 ? round($p / $t * 100, 1) : null,
                ];
            })->values();

            $resumenAsistencia = [
                'total'      => $total,
                'presentes'  => $presentes,
                'ausentes'   => $ausentes,
                'tardanzas'  => $tardanzas,
                'porcentaje' => $total > 0 ? round($presentes / $total * 100, 1) : null,
                'por_materia'=> $porMateria,
            ];
        }

        // Observaciones del año actual (todas, no solo públicas)
        $observaciones = Observacion::with(['docente', 'asignacion.asignatura'])
            ->where('estudiante_id', $estudiante->id)
            ->when($matriculaActual, fn($q) => $q->whereHas('asignacion',
                fn($s) => $s->where('grupo_id', $matriculaActual->grupo_id)
            ))
            ->latest()
            ->get();

        // Historial académico completo (todos los años)
        $matIds       = $estudiante->matriculas->pluck('id');
        $califsPorMat = CalificacionAcademica::with('asignacion.asignatura')
            ->whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');

        $historialAnios = $estudiante->matriculas->map(function ($m) use ($califsPorMat) {
            $califs = $califsPorMat->get($m->id, collect());
            return [
                'matricula'  => $m,
                'schoolYear' => $m->schoolYear,
                'califs'     => $califs,
                'promedio'   => $califs->whereNotNull('nota_final')->avg('nota_final'),
            ];
        })->filter(fn($h) => $h['schoolYear'] !== null);

        return view('admin.perfiles.estudiante', compact(
            'estudiante', 'schoolYear', 'matriculaActual',
            'calificaciones', 'promedio', 'estado', 'historialAnios',
            'resumenAsistencia', 'observaciones'
        ));
    }

    // ── Informe anual PDF ─────────────────────────────────────────────────
    public function informePdf(Estudiante $estudiante)
    {
        $estudiante->load([
            'user',
            'representantes',
            'matriculas.schoolYear',
            'matriculas.grupo.grado',
            'matriculas.grupo.seccion',
        ]);

        $schoolYear = SchoolYear::actual();

        // Historial completo por año
        $matIds       = $estudiante->matriculas->pluck('id');
        $califsPorMat = CalificacionAcademica::with('asignacion.asignatura')
            ->whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');
        $asisPorMat   = Asistencia::whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');

        $historial = $estudiante->matriculas->map(function ($m) use ($califsPorMat, $asisPorMat) {
            $calAcad     = $califsPorMat->get($m->id, collect());
            $asistencias = $asisPorMat->get($m->id, collect());
            $totalAs     = $asistencias->count();
            $presentesAs = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();

            return [
                'matricula'  => $m,
                'schoolYear' => $m->schoolYear,
                'califs'     => $calAcad,
                'promedio'   => $calAcad->whereNotNull('nota_final')->avg('nota_final'),
                'aprobadas'  => $calAcad->where('situacion', 'A')->count(),
                'reprobadas' => $calAcad->where('situacion', 'R')->count(),
                'asistencia' => $totalAs > 0 ? round($presentesAs / $totalAs * 100, 1) : null,
            ];
        })->sortByDesc(fn($h) => $h['schoolYear']?->id ?? 0)
          ->filter(fn($h) => $h['schoolYear'] !== null)
          ->values();

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.informe_pdf',
            compact('estudiante', 'historial', 'si', 'config', 'dir')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("informe_{$slug}.pdf");
    }

    public function informeExcel(Estudiante $estudiante)
    {
        $estudiante->load([
            'user', 'representantes',
            'matriculas.schoolYear',
            'matriculas.grupo.grado',
            'matriculas.grupo.seccion',
        ]);

        $matIds       = $estudiante->matriculas->pluck('id');
        $califsPorMat = CalificacionAcademica::with('asignacion.asignatura')
            ->whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');
        $asisPorMat   = Asistencia::whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');

        $historial = $estudiante->matriculas->map(function ($m) use ($califsPorMat, $asisPorMat) {
            $calAcad     = $califsPorMat->get($m->id, collect());
            $asistencias = $asisPorMat->get($m->id, collect());
            $totalAs     = $asistencias->count();
            $presentesAs = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();
            return [
                'matricula'  => $m,
                'schoolYear' => $m->schoolYear,
                'califs'     => $calAcad,
                'promedio'   => $calAcad->whereNotNull('nota_final')->avg('nota_final'),
                'aprobadas'  => $calAcad->where('situacion', 'A')->count(),
                'reprobadas' => $calAcad->where('situacion', 'R')->count(),
                'asistencia' => $totalAs > 0 ? round($presentesAs / $totalAs * 100, 1) : null,
            ];
        })->sortByDesc(fn($h) => $h['schoolYear']?->id ?? 0)
          ->filter(fn($h) => $h['schoolYear'] !== null)
          ->values();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Historial');

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Historial Académico — ' . $estudiante->nombre_completo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['Año', 'Grupo', 'Promedio', 'Aprobadas', 'Reprobadas', 'Asistencia'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($historial as $i => $h) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $h['schoolYear']?->nombre ?? '—');
            $ws->setCellValue("B{$row}", $h['matricula']?->grupo?->nombre_completo ?? '—');
            $prom = $h['promedio'];
            $ws->setCellValue("C{$row}", $prom !== null ? number_format($prom, 1) : '—');
            $ws->setCellValue("D{$row}", $h['aprobadas']);
            $ws->setCellValue("E{$row}", $h['reprobadas']);
            $ws->setCellValue("F{$row}", $h['asistencia'] !== null ? $h['asistencia'] . '%' : '—');

            if ($h['reprobadas'] > 0) {
                $ws->getStyle("E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('fee2e2');
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        $filename = "historial_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Certificado oficial de calificaciones ────────────────────────────
    public function certificadoNotas(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404, 'Sin año escolar activo.');

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa para el año actual.');

        $calAcad = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->sortBy('asignacion.asignatura.nombre');

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $promedio   = $calAcad->whereNotNull('nota_final')->avg('nota_final');
        $aprobadas  = $calAcad->where('situacion', 'A')->count();
        $reprobadas = $calAcad->where('situacion', 'R')->count();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.certificado_notas_pdf',
            compact('estudiante', 'matricula', 'calAcad', 'promedio',
                    'aprobadas', 'reprobadas', 'si', 'dir', 'cod', 'config', 'schoolYear')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("certificado_notas_{$slug}.pdf");
    }

    // ── Certificado de Buena Conducta PDF ────────────────────────────────
    public function certificadoConducta(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.certificado_conducta_pdf',
            compact('estudiante', 'matricula', 'si', 'dir', 'cod', 'config', 'schoolYear')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("conducta_{$slug}.pdf");
    }

    // ── Reporte de asistencia del estudiante PDF ─────────────────────────
    public function asistenciaPdf(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->latest()->first();

        if (! $matricula) abort(404);

        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->orderBy('fecha')
            ->get();

        $porAsignacion = $asistencias->groupBy('asignacion_id')->map(function ($rows) {
            $total    = $rows->count();
            $presentes= $rows->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();
            return [
                'asignatura' => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
                'total'      => $total,
                'presentes'  => $presentes,
                'ausentes'   => $rows->where('estado', 'ausente')->count(),
                'tardanzas'  => $rows->where('estado', 'tardanza')->count(),
                'justificado'=> $rows->where('estado', 'justificado')->count(),
                'pct'        => $total > 0 ? round($presentes / $total * 100, 1) : null,
            ];
        })->sortBy('asignatura')->values();

        $totalGeneral = $asistencias->count();
        $presGeneral  = $asistencias->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();
        $pctGeneral   = $totalGeneral > 0 ? round($presGeneral / $totalGeneral * 100, 1) : null;

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.perfiles.asistencia_pdf', compact(
            'estudiante', 'matricula', 'schoolYear', 'porAsignacion',
            'totalGeneral', 'presGeneral', 'pctGeneral', 'inst', 'config'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("asistencia_{$slug}.pdf");
    }

    // ── Historial Académico Multi-año (vista interactiva) ────────────────
    public function historialAcademico(Estudiante $estudiante)
    {
        $estudiante->load([
            'user',
            'matriculas' => function ($q) {
                $q->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
                  ->orderBy('school_year_id');
            },
        ]);

        // Construir historial año por año con detalle de asignaturas
        $matIds       = $estudiante->matriculas->pluck('id');
        $califsPorMat = CalificacionAcademica::with('asignacion.asignatura')
            ->whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');
        $asisPorMat   = Asistencia::whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');

        $historial = $estudiante->matriculas->map(function ($m) use ($califsPorMat, $asisPorMat) {
            $calAcad     = $califsPorMat->get($m->id, collect())
                ->sortBy(fn($c) => $c->asignacion?->asignatura?->nombre);
            $asistencias = $asisPorMat->get($m->id, collect());
            $totalAs     = $asistencias->count();
            $presentesAs = $asistencias->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();

            return [
                'matricula'  => $m,
                'schoolYear' => $m->schoolYear,
                'grado'      => $m->grupo?->grado?->nombre ?? '—',
                'seccion'    => $m->grupo?->seccion?->nombre ?? '',
                'grupo'      => $m->grupo?->nombre_completo ?? '—',
                'califs'     => $calAcad,
                'promedio'   => $calAcad->whereNotNull('nota_final')->avg('nota_final'),
                'aprobadas'  => $calAcad->where('situacion', 'A')->count(),
                'reprobadas' => $calAcad->where('situacion', 'R')->count(),
                'total_asig' => $calAcad->count(),
                'asistencia' => $totalAs > 0 ? round($presentesAs / $totalAs * 100, 1) : null,
                'situacion_general' => $calAcad->where('situacion', 'R')->count() === 0 &&
                                       $calAcad->whereNotNull('situacion')->count() > 0
                                       ? 'Promovido' : ($calAcad->whereNotNull('situacion')->count() > 0 ? 'Reprobado' : '—'),
            ];
        })->filter(fn($h) => $h['schoolYear'] !== null)->values();

        // Datos para Chart.js: años en orden cronológico
        $chartLabels   = $historial->pluck('schoolYear.nombre')->map(fn($n) => $n ?? '—')->values();
        $chartPromedios = $historial->pluck('promedio')->map(fn($p) => $p !== null ? round($p, 1) : null)->values();

        $si = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        return view('admin.perfiles.historial_academico', compact(
            'estudiante', 'historial', 'chartLabels', 'chartPromedios', 'si'
        ));
    }

    // ── Historial Académico Multi-año PDF oficial ─────────────────────────
    public function historialPdf(Estudiante $estudiante)
    {
        $estudiante->load([
            'user',
            'representantes',
            'matriculas' => function ($q) {
                $q->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
                  ->orderBy('school_year_id');
            },
        ]);

        $matIds       = $estudiante->matriculas->pluck('id');
        $califsPorMat = CalificacionAcademica::with('asignacion.asignatura')
            ->whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');
        $asisPorMat   = Asistencia::whereIn('matricula_id', $matIds)->get()->groupBy('matricula_id');

        $historial = $estudiante->matriculas->map(function ($m) use ($califsPorMat, $asisPorMat) {
            $calAcad     = $califsPorMat->get($m->id, collect())
                ->sortBy(fn($c) => $c->asignacion?->asignatura?->nombre);
            $asistencias = $asisPorMat->get($m->id, collect());
            $totalAs     = $asistencias->count();
            $presentesAs = $asistencias->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();

            return [
                'matricula'  => $m,
                'schoolYear' => $m->schoolYear,
                'grado'      => $m->grupo?->grado?->nombre ?? '—',
                'seccion'    => $m->grupo?->seccion?->nombre ?? '',
                'grupo'      => $m->grupo?->nombre_completo ?? '—',
                'califs'     => $calAcad,
                'promedio'   => $calAcad->whereNotNull('nota_final')->avg('nota_final'),
                'aprobadas'  => $calAcad->where('situacion', 'A')->count(),
                'reprobadas' => $calAcad->where('situacion', 'R')->count(),
                'total_asig' => $calAcad->count(),
                'asistencia' => $totalAs > 0 ? round($presentesAs / $totalAs * 100, 1) : null,
                'situacion_general' => $calAcad->where('situacion', 'R')->count() === 0 &&
                                       $calAcad->whereNotNull('situacion')->count() > 0
                                       ? 'Promovido' : ($calAcad->whereNotNull('situacion')->count() > 0 ? 'Reprobado' : '—'),
            ];
        })->filter(fn($h) => $h['schoolYear'] !== null)->values();

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $nivel  = \App\Models\ConfigInstitucional::get('nivel_educativo', '');
        $config = \App\Models\BoletinConfig::getOrCreate(
            \App\Models\SchoolYear::actual()?->id ?? 0
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.historial_pdf',
            compact('estudiante', 'historial', 'si', 'dir', 'cod', 'nivel', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("historial_academico_{$slug}.pdf");
    }

    // ── Reporte de asistencia del estudiante Excel ───────────────────────
    public function asistenciaExcel(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->latest()->first();

        if (! $matricula) abort(404);

        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->orderBy('fecha')
            ->get();

        $porAsignacion = $asistencias->groupBy('asignacion_id')->map(function ($rows) {
            $total    = $rows->count();
            $presentes= $rows->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();
            return [
                'asignatura' => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
                'total'      => $total,
                'presentes'  => $presentes,
                'ausentes'   => $rows->where('estado', 'ausente')->count(),
                'tardanzas'  => $rows->where('estado', 'tardanza')->count(),
                'justificado'=> $rows->where('estado', 'justificado')->count(),
                'pct'        => $total > 0 ? round($presentes / $total * 100, 1) : null,
            ];
        })->sortBy('asignatura')->values();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Asistencia');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', $inst);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:H2');
        $ws->setCellValue('A2', 'Reporte de Asistencia — ' . $estudiante->nombre_completo . ' — ' . $schoolYear->nombre);
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $ws->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Asignatura', 'Total', 'Presentes', 'Ausentes', 'Tardanzas', 'Justificados', '% Asistencia'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '4', $h);
        }
        $ws->getStyle('A4:H4')->applyFromArray($hdrStyle);

        foreach ($porAsignacion as $i => $data) {
            $row = $i + 5;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $data['asignatura']);
            $ws->setCellValue("C{$row}", $data['total']);
            $ws->setCellValue("D{$row}", $data['presentes']);
            $ws->setCellValue("E{$row}", $data['ausentes']);
            $ws->setCellValue("F{$row}", $data['tardanzas']);
            $ws->setCellValue("G{$row}", $data['justificado']);
            $ws->setCellValue("H{$row}", $data['pct'] !== null ? $data['pct'] . '%' : '—');
            if ($data['pct'] !== null && $data['pct'] < 75) {
                $ws->getStyle("H{$row}")->getFont()->getColor()->setRGB('dc2626');
                $ws->getStyle("H{$row}")->getFont()->setBold(true);
            }
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'asis_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return response()->download($tmp, "asistencia_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
