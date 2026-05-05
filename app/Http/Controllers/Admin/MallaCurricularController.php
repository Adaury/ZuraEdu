<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\EspecialidadTecnica;
use App\Models\Grado;
use App\Models\MallaCurricular;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MallaCurricularController extends Controller
{
    public function index(Request $request)
    {
        $grados         = Grado::orderBy('nivel')->get();
        $especialidades = EspecialidadTecnica::activas()->orderBy('orden')->get();

        $query = MallaCurricular::with(['grado', 'asignatura', 'especialidad'])
            ->activas();

        if ($request->filled('area')) {
            $query->porArea($request->area);
        }
        if ($request->filled('grado_id')) {
            $query->porGrado($request->grado_id);
        }
        if ($request->filled('especialidad_id')) {
            $query->where('especialidad_id', $request->especialidad_id);
        }

        $malla = $query->orderBy('grado_id')->orderBy('area')->orderBy('orden_display')->get();

        return view('admin.malla.index', compact('malla', 'grados', 'especialidades'));
    }

    public function matriz()
    {
        $grados      = Grado::orderBy('nivel')->get();
        $asignaturas = Asignatura::activas()->orderBy('nombre')->get();

        $mallaMap = MallaCurricular::activas()
            ->get()
            ->keyBy(fn($m) => $m->grado_id . '_' . $m->asignatura_id);

        return view('admin.malla.matriz', compact('grados', 'asignaturas', 'mallaMap'));
    }

    public function create()
    {
        $grados         = Grado::orderBy('nivel')->get();
        $asignaturas    = Asignatura::activas()->orderBy('nombre')->get();
        $especialidades = EspecialidadTecnica::activas()->orderBy('orden')->get();

        return view('admin.malla.create', compact('grados', 'asignaturas', 'especialidades'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grado_id'        => 'required|exists:grados,id',
            'asignatura_id'   => 'required|exists:asignaturas,id',
            'area'            => 'required|in:academica,tecnica',
            'especialidad_id' => 'nullable|exists:especialidades_tecnicas,id',
            'horas_semanales' => 'required|integer|min:0|max:40',
            'horas_anuales'   => 'nullable|integer|min:0',
            'es_obligatoria'  => 'boolean',
            'orden_display'   => 'nullable|integer|min:0',
            'notas_curriculo' => 'nullable|string',
        ], [
            'unique' => 'Esta asignatura ya está registrada en la malla para ese grado.',
        ]);

        MallaCurricular::create($data + ['activo' => true]);

        return redirect()->route('admin.malla.index')
            ->with('success', 'Asignatura agregada a la malla curricular.');
    }

    public function edit(MallaCurricular $malla)
    {
        $grados         = Grado::orderBy('nivel')->get();
        $asignaturas    = Asignatura::activas()->orderBy('nombre')->get();
        $especialidades = EspecialidadTecnica::activas()->orderBy('orden')->get();

        return view('admin.malla.edit', compact('malla', 'grados', 'asignaturas', 'especialidades'));
    }

    public function update(Request $request, MallaCurricular $malla)
    {
        $data = $request->validate([
            'grado_id'        => 'required|exists:grados,id',
            'asignatura_id'   => 'required|exists:asignaturas,id',
            'area'            => 'required|in:academica,tecnica',
            'especialidad_id' => 'nullable|exists:especialidades_tecnicas,id',
            'horas_semanales' => 'required|integer|min:0|max:40',
            'horas_anuales'   => 'nullable|integer|min:0',
            'es_obligatoria'  => 'boolean',
            'orden_display'   => 'nullable|integer|min:0',
            'notas_curriculo' => 'nullable|string',
            'activo'          => 'boolean',
        ]);

        $malla->update($data);

        return redirect()->route('admin.malla.index')
            ->with('success', 'Entrada de malla actualizada.');
    }

    public function destroy(MallaCurricular $malla)
    {
        $malla->delete();
        return redirect()->route('admin.malla.index')
            ->with('success', 'Entrada eliminada de la malla.');
    }

    // ── Malla Curricular PDF ──────────────────────────────────────────────
    public function matrizPdf()
    {
        $grados      = Grado::orderBy('nivel')->get();
        $asignaturas = Asignatura::activas()->orderBy('nombre')->get();

        $mallaMap = MallaCurricular::activas()
            ->with(['grado', 'asignatura', 'especialidad'])
            ->get()
            ->keyBy(fn($m) => $m->grado_id . '_' . $m->asignatura_id);

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = \App\Models\SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.malla.malla_pdf',
            compact('grados', 'asignaturas', 'mallaMap', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        return $pdf->download('malla_curricular_' . now()->format('Ymd') . '.pdf');
    }

    public function matrizExcel()
    {
        $grados      = Grado::orderBy('nivel')->get();
        $asignaturas = Asignatura::activas()->orderBy('nombre')->get();

        $mallaMap = MallaCurricular::activas()->get()
            ->keyBy(fn($m) => $m->grado_id . '_' . $m->asignatura_id);

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Malla Curricular');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $totalCols = 1 + $grados->count();
        $lastCol   = chr(64 + min($totalCols, 26));

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'MALLA CURRICULAR — ' . now()->format('Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Asignatura');
        $col = 2;
        foreach ($grados as $grado) {
            $sheet->setCellValue(chr(64 + $col) . '2', $grado->nombre);
            $col++;
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(28);

        foreach ($asignaturas as $i => $asig) {
            $r = $i + 3;
            $sheet->setCellValue("A{$r}", $asig->nombre);
            $col = 2;
            foreach ($grados as $grado) {
                $key  = $grado->id . '_' . $asig->id;
                $malla = $mallaMap->get($key);
                $cell  = chr(64 + $col) . $r;
                if ($malla) {
                    $sheet->setCellValue($cell, '✓');
                    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d1fae5');
                    $sheet->getStyle($cell)->getFont()->getColor()->setRGB('065f46');
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $sheet->setCellValue($cell, '');
                }
                $col++;
            }
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
        }

        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->freezePane('B3');

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'malla_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'Malla_Curricular_' . now()->format('Y') . '.xlsx')->deleteFileAfterSend();
    }
}
