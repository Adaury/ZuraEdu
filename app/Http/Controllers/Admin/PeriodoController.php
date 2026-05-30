<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PeriodoController extends Controller
{
    public function index()
    {
        $schoolYears = SchoolYear::with(['periodos' => fn($q) => $q->orderBy('numero')])
            ->orderByDesc('activo')
            ->orderByDesc('id')
            ->get();

        return view('admin.periodos.index', compact('schoolYears'));
    }

    public function create()
    {
        $schoolYears = SchoolYear::orderByDesc('activo')->orderByDesc('id')->get();
        $schoolYear  = SchoolYear::actual();
        return view('admin.periodos.create', compact('schoolYears', 'schoolYear'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'numero'         => 'required|integer|min:1|max:12',
            'nombre'         => 'required|string|max:100',
            'fecha_inicio'   => 'nullable|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_inicio',
            'activo'         => 'boolean',
            'cerrado'        => 'boolean',
        ]);

        $exists = Periodo::where('school_year_id', $data['school_year_id'])
            ->where('numero', $data['numero'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['numero' => 'Ya existe un período con ese número en este año escolar.']);
        }

        $data['activo']  = $request->boolean('activo');
        $data['cerrado'] = $request->boolean('cerrado');

        Periodo::create($data);

        return redirect()->route('admin.periodos.index')
            ->with('success', 'Período creado exitosamente.');
    }

    public function edit(Periodo $periodo)
    {
        $schoolYears = SchoolYear::orderByDesc('activo')->orderByDesc('id')->get();
        return view('admin.periodos.edit', compact('periodo', 'schoolYears'));
    }

    public function update(Request $request, Periodo $periodo)
    {
        $data = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'numero'         => 'required|integer|min:1|max:12',
            'nombre'         => 'required|string|max:100',
            'fecha_inicio'   => 'nullable|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_inicio',
            'activo'         => 'boolean',
            'cerrado'        => 'boolean',
        ]);

        $exists = Periodo::where('school_year_id', $data['school_year_id'])
            ->where('numero', $data['numero'])
            ->where('id', '!=', $periodo->id)
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['numero' => 'Ya existe un período con ese número en este año escolar.']);
        }

        $data['activo']  = $request->boolean('activo');
        $data['cerrado'] = $request->boolean('cerrado');

        $periodo->update($data);

        return redirect()->route('admin.periodos.index')
            ->with('success', 'Período actualizado.');
    }

    // ── Checklist de cierre de período ───────────────────────────────────
    public function checklist(Periodo $periodo)
    {
        $periodo->load('schoolYear');
        $sy = $periodo->schoolYear;

        // Asignaciones activas del año
        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente'])
            ->where('school_year_id', $sy?->id)
            ->where('activo', true)
            ->get();

        // Matrícula activas
        $totalMatriculas = Matricula::where('school_year_id', $sy?->id)
            ->where('estado', 'activa')->count();

        // Calificaciones técnicas publicadas en este período
        $califTec = Calificacion::where('periodo_id', $periodo->id)
            ->selectRaw('asignacion_id, COUNT(*) as total, SUM(publicado) as publicadas')
            ->groupBy('asignacion_id')
            ->get()->keyBy('asignacion_id');

        // Calificaciones académicas publicadas
        $califAcad = CalificacionAcademica::where('school_year_id', $sy?->id)
            ->selectRaw('asignacion_id, COUNT(*) as total, SUM(publicado) as publicadas')
            ->groupBy('asignacion_id')
            ->get()->keyBy('asignacion_id');

        // Construir checklist por asignación
        $items = $asignaciones->map(function ($asig) use ($califTec, $califAcad, $periodo, $totalMatriculas) {
            $esTec    = $asig->area === 'tecnica';
            $calif    = $esTec ? ($califTec[$asig->id] ?? null) : ($califAcad[$asig->id] ?? null);
            $total    = $calif?->total ?? 0;
            $publi    = $calif?->publicadas ?? 0;
            return [
                'asignacion' => $asig,
                'total_cal'  => $total,
                'publicadas' => $publi,
                'ok_ingreso' => $total >= max(1, round($totalMatriculas * 0.5)),
                'ok_publi'   => $publi > 0 && $publi >= $total,
            ];
        });

        $resumen = [
            'total'      => $items->count(),
            'completas'  => $items->filter(fn($i) => $i['ok_ingreso'] && $i['ok_publi'])->count(),
            'sin_notas'  => $items->filter(fn($i) => $i['total_cal'] === 0)->count(),
            'sin_publi'  => $items->filter(fn($i) => $i['total_cal'] > 0 && !$i['ok_publi'])->count(),
        ];

        return view('admin.periodos.checklist', compact('periodo', 'sy', 'items', 'resumen', 'totalMatriculas'));
    }

    // ── Cerrar período (con validación de notas mínimas) ─────────────────
    public function cerrar(Periodo $periodo)
    {
        $forzar = request()->boolean('forzar');

        if (! $forzar) {
            $asignacionIds = Asignacion::where('school_year_id', $periodo->school_year_id)
                ->where('activo', true)
                ->pluck('id');

            if ($asignacionIds->isNotEmpty()) {
                // Asignaciones con notas técnicas en este período
                $conTrad = Calificacion::where('periodo_id', $periodo->id)
                    ->whereIn('asignacion_id', $asignacionIds)
                    ->distinct()->pluck('asignacion_id');

                // Asignaciones con notas académicas registradas en el año
                $conAcad = CalificacionAcademica::where('school_year_id', $periodo->school_year_id)
                    ->whereIn('asignacion_id', $asignacionIds)
                    ->whereNotNull('nota_final')
                    ->distinct()->pluck('asignacion_id');

                // Asignaciones con evaluaciones MINERD en este período
                $conMinerd = \App\Models\EvaluacionRegistro::where('periodo_id', $periodo->id)
                    ->whereIn('asignacion_id', $asignacionIds)
                    ->distinct()->pluck('asignacion_id');

                $sinNota = $asignacionIds
                    ->diff($conTrad->merge($conAcad)->merge($conMinerd)->unique())
                    ->count();

                if ($sinNota > 0) {
                    return redirect()
                        ->route('admin.periodos.checklist', $periodo)
                        ->with('error_cierre',
                            "{$sinNota} asignación(es) no tienen notas registradas en ningún sistema. " .
                            "Revisa el detalle abajo. Puedes forzar el cierre marcando la opción correspondiente.");
                }
            }
        }

        $periodo->update(['cerrado' => true, 'activo' => false]);

        $siguiente = Periodo::where('school_year_id', $periodo->school_year_id)
            ->where('numero', $periodo->numero + 1)
            ->first();
        if ($siguiente) {
            $siguiente->update(['activo' => true]);
        }

        return redirect()->route('admin.registro.index')
            ->with('success', "Período {$periodo->nombre} cerrado correctamente." .
                ($siguiente ? " Se activó automáticamente {$siguiente->nombre}." : ''));
    }

    // ── Reabrir período cerrado ───────────────────────────────────────────
    public function reabrir(Periodo $periodo)
    {
        if (! $periodo->cerrado) {
            return back()->with('error', 'Este período no está cerrado.');
        }

        // Desactivar cualquier período activo del mismo año escolar
        Periodo::where('school_year_id', $periodo->school_year_id)
            ->where('activo', true)
            ->update(['activo' => false]);

        $periodo->update(['cerrado' => false, 'activo' => true]);

        return redirect()->route('admin.registro.index')
            ->with('success', "Período {$periodo->nombre} reabierto y marcado como activo.");
    }

    public function listaExcel()
    {
        $schoolYears = SchoolYear::with(['periodos' => fn($q) => $q->orderBy('numero')])
            ->orderByDesc('activo')
            ->orderByDesc('id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Períodos');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Lista de Períodos Académicos');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Año Escolar', 'Período', 'N°', 'Inicio', 'Fin', 'Estado'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        $row = 4; $idx = 0;
        foreach ($schoolYears as $sy) {
            foreach ($sy->periodos as $periodo) {
                $row++; $idx++;
                $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
                $estado = $periodo->cerrado ? 'Cerrado' : ($periodo->activo ? 'Activo' : 'Inactivo');
                $sheet->setCellValue('A' . $row, $idx);
                $sheet->setCellValue('B' . $row, $sy->nombre);
                $sheet->setCellValue('C' . $row, $periodo->nombre);
                $sheet->setCellValue('D' . $row, $periodo->numero);
                $sheet->setCellValue('E' . $row, $periodo->fecha_inicio?->format('d/m/Y') ?? '—');
                $sheet->setCellValue('F' . $row, $periodo->fecha_fin?->format('d/m/Y') ?? '—');
                $sheet->setCellValue('G' . $row, $estado);
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($bg);
                if ($periodo->cerrado) {
                    $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('fee2e2');
                } elseif ($periodo->activo) {
                    $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('dcfce7');
                }
            }
        }

        foreach (['A'=>5,'B'=>22,'C'=>20,'D'=>5,'E'=>14,'F'=>14,'G'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'periodos.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function listaPdf()
    {
        $schoolYears = SchoolYear::with(['periodos' => fn($q) => $q->orderBy('numero')])
            ->orderByDesc('activo')
            ->orderByDesc('id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.periodos.lista_pdf', compact('schoolYears', 'inst'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('periodos.pdf');
    }

    public function destroy(Periodo $periodo)
    {
        $periodo->loadCount('calificaciones');
        if ($periodo->calificaciones_count > 0) {
            return back()->with('error', 'No se puede eliminar: tiene calificaciones registradas.');
        }

        $periodo->delete();

        return redirect()->route('admin.periodos.index')
            ->with('success', 'Período eliminado.');
    }
}
