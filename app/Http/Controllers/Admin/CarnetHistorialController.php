<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarnetAcceso;
use App\Models\CarnetZona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CarnetHistorialController extends Controller
{
    public function index(Request $request)
    {
        $query = CarnetAcceso::with(['carnet.user', 'carnet.matricula.grupo', 'zona', 'registrador'])
            ->orderByDesc('created_at');

        if ($fecha = $request->fecha) {
            $query->whereDate('created_at', $fecha);
        } else {
            $query->whereDate('created_at', today());
        }

        if ($tipo = $request->tipo_evento) {
            $query->where('tipo_evento', $tipo);
        }

        if ($estado = $request->estado) {
            $query->where('estado', $estado);
        }

        if ($search = $request->search) {
            $query->whereHas('carnet.user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $accesos = $query->paginate(30)->withQueryString();
        $zonas   = CarnetZona::activas()->orderBy('nombre')->get();

        return view('admin.carnet.historial', compact('accesos', 'zonas'));
    }

    public function pdf(Request $request)
    {
        $fecha = $request->fecha ?? today()->toDateString();
        $accesos = CarnetAcceso::with(['carnet.user', 'carnet.matricula.grupo', 'zona'])
            ->whereDate('created_at', $fecha)
            ->orderByDesc('created_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView('admin.carnet.historial_pdf', compact('accesos', 'fecha', 'inst'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("historial-carnet-{$fecha}.pdf");
    }

    public function excel(Request $request)
    {
        $fecha = $request->fecha ?? today()->toDateString();

        $accesos = CarnetAcceso::with(['carnet.user', 'carnet.matricula.grupo', 'zona'])
            ->whereDate('created_at', $fecha)
            ->orderByDesc('created_at')
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Historial Carnet');

        $headers = ['Nombre', 'Carnet', 'Grupo', 'Evento', 'Estado', 'Hora', 'Zona', 'Dispositivo'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$col}1", $h);
        }
        $ws->getStyle('A1:H1')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
        $ws->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1e3a6e');

        foreach ($accesos as $i => $a) {
            $row = $i + 2;
            $ws->setCellValue("A{$row}", $a->carnet?->nombre_completo ?? '—');
            $ws->setCellValue("B{$row}", $a->carnet?->numero_carnet ?? '—');
            $ws->setCellValue("C{$row}", $a->carnet?->matricula?->grupo?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", $a->tipo_evento);
            $ws->setCellValue("E{$row}", $a->estado);
            $ws->setCellValue("F{$row}", $a->hora);
            $ws->setCellValue("G{$row}", $a->zona?->nombre ?? '—');
            $ws->setCellValue("H{$row}", $a->dispositivo ?? '—');
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"historial-carnet-{$fecha}.xlsx\"",
        ]);
    }
}
