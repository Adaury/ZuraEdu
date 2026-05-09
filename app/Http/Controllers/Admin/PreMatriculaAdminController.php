<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PreMatriculaResolucion;
use App\Models\PreMatricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PreMatriculaAdminController extends Controller
{
    /**
     * Listado de todas las solicitudes con filtros.
     */
    public function index(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }

        if ($request->filled('grado')) {
            $query->porGrado($request->grado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $solicitudes  = $query->paginate(20)->withQueryString();
        $grados       = PreMatricula::gradosDisponibles();
        $totalPendientes = PreMatricula::pendientes()->count();

        // Conteos para tarjetas
        $conteos = [
            'total'     => PreMatricula::count(),
            'pendiente' => PreMatricula::where('estado', 'pendiente')->count(),
            'aprobada'  => PreMatricula::where('estado', 'aprobada')->count(),
            'rechazada' => PreMatricula::where('estado', 'rechazada')->count(),
        ];

        return view('admin.pre_matriculas.index', compact(
            'solicitudes', 'grados', 'conteos', 'totalPendientes'
        ));
    }

    /**
     * Detalle de una solicitud.
     */
    public function show(PreMatricula $preMatricula)
    {
        return view('admin.pre_matriculas.show', compact('preMatricula'));
    }

    /**
     * Aprobar solicitud.
     */
    public function aprobar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $preMatricula->update([
            'estado'      => 'aprobada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} aprobada. Se notificó al representante.");
    }

    /**
     * Rechazar solicitud.
     */
    public function rechazar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['required', 'string', 'max:1000'],
        ], [
            'notas_admin.required' => 'Debe indicar el motivo del rechazo.',
        ]);

        $preMatricula->update([
            'estado'      => 'rechazada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} rechazada. Se notificó al representante.");
    }

    /**
     * Eliminar solicitud.
     */
    public function destroy(PreMatricula $preMatricula)
    {
        $nombre = $preMatricula->nombre_completo;
        $preMatricula->delete();

        return redirect()->route('admin.pre-matriculas.index')
            ->with('success', "Solicitud de {$nombre} eliminada.");
    }

    /**
     * Exportar lista de solicitudes a Excel.
     */
    public function listaExcel(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }
        if ($request->filled('grado')) {
            $query->porGrado($request->grado);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $solicitudes = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Pre-matrículas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Solicitudes de Pre-matrículas — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Estudiante', 'Grado Solicitado', 'Representante', 'Cédula Rep.', 'Teléfono', 'Correo', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        foreach ($solicitudes as $i => $s) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $s->nombre_completo);
            $ws->setCellValue("C{$row}", $s->grado_solicitado ?? '—');
            $ws->setCellValue("D{$row}", $s->nombre_representante ?? '—');
            $ws->setCellValue("E{$row}", $s->cedula_representante ?? '—');
            $ws->setCellValue("F{$row}", $s->telefono ?? '—');
            $ws->setCellValue("G{$row}", $s->email ?? '—');
            $ws->setCellValue("H{$row}", ucfirst($s->estado ?? '—'));
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'prm_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'pre_matriculas_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) $query->porEstado($request->estado);
        if ($request->filled('grado'))  $query->porGrado($request->grado);
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(fn($q) =>
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
            );
        }

        $solicitudes = $query->get();
        $inst        = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pre_matriculas.lista_pdf',
            compact('solicitudes', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('pre_matriculas_' . now()->format('Ymd') . '.pdf');
    }
}
