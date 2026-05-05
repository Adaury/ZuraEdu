<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\FamiliaProfesional;
use Illuminate\Http\Request;

class FamiliaProfesionalController extends Controller
{
    public function index()
    {
        $familias = FamiliaProfesional::withCount('asignaturas')
            ->with(['asignaturas' => fn($q) => $q->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        $asignaturasTecnicas = Asignatura::where('area', 'tecnica')
            ->whereNull('familia_id')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.familias.index', compact('familias', 'asignaturasTecnicas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'icono'       => 'nullable|string|max:50',
        ]);

        FamiliaProfesional::create([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'color'       => $request->color ?? '#1e3a6e',
            'icono'       => $request->icono ?? 'bi-briefcase',
            'activo'      => true,
        ]);

        return redirect()->route('admin.familias.index')
            ->with('success', 'Familia profesional creada correctamente.');
    }

    public function update(Request $request, FamiliaProfesional $familia)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'icono'       => 'nullable|string|max:50',
        ]);

        $familia->update([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'color'       => $request->color ?? $familia->color,
            'icono'       => $request->icono ?? $familia->icono,
        ]);

        return redirect()->route('admin.familias.index')
            ->with('success', 'Familia actualizada correctamente.');
    }

    public function destroy(FamiliaProfesional $familia)
    {
        // Desvincular asignaturas antes de borrar
        Asignatura::where('familia_id', $familia->id)->update(['familia_id' => null]);
        $familia->delete();

        return redirect()->route('admin.familias.index')
            ->with('success', 'Familia eliminada. Las asignaturas quedaron sin familia.');
    }

    public function toggleActivo(FamiliaProfesional $familia)
    {
        $familia->update(['activo' => !$familia->activo]);
        $estado = $familia->activo ? 'activada' : 'desactivada';

        return back()->with('success', "Familia {$estado} correctamente.");
    }

    public function asignarAsignatura(Request $request, FamiliaProfesional $familia)
    {
        $request->validate(['asignatura_id' => 'required|exists:asignaturas,id']);

        $asignatura = Asignatura::findOrFail($request->asignatura_id);

        if ($asignatura->area !== 'tecnica') {
            return back()->with('error', 'Solo se pueden asignar materias técnicas a una familia.');
        }

        $asignatura->update(['familia_id' => $familia->id]);

        return back()->with('success', "{$asignatura->nombre} asignada a {$familia->nombre}.");
    }

    public function quitarAsignatura(FamiliaProfesional $familia, Asignatura $asignatura)
    {
        $asignatura->update(['familia_id' => null]);

        return back()->with('success', "{$asignatura->nombre} desvinculada de la familia.");
    }

    // ── Lista PDF ────────────────────────────────────────────────────────
    public function listaPdf()
    {
        $familias = FamiliaProfesional::withCount('asignaturas')
            ->with(['asignaturas' => fn($q) => $q->orderBy('nombre')])
            ->orderBy('nombre')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.familias.lista_pdf',
            compact('familias', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('familias_profesionales_' . now()->format('Ymd') . '.pdf');
    }

    // ── Lista Excel ──────────────────────────────────────────────────────
    public function listaExcel()
    {
        $familias = FamiliaProfesional::withCount('asignaturas')
            ->with(['asignaturas' => fn($q) => $q->orderBy('nombre')])
            ->orderBy('nombre')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Familias');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', $inst);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:E2');
        $ws->setCellValue('A2', 'Familias Profesionales — ' . now()->format('d/m/Y'));
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $ws->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Familia', 'Descripción', 'Asignaturas', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '4', $h);
        }
        $ws->getStyle('A4:E4')->applyFromArray($hdrStyle);

        foreach ($familias as $i => $f) {
            $row = $i + 5;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $f->nombre);
            $ws->setCellValue("C{$row}", $f->descripcion ?? '—');
            $ws->setCellValue("D{$row}", $f->asignaturas_count);
            $ws->setCellValue("E{$row}", $f->activo ? 'Activa' : 'Inactiva');
            if (! $f->activo) {
                $ws->getStyle("E{$row}")->getFont()->getColor()->setRGB('dc2626');
            }
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'E') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'fam_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'familias_profesionales_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
