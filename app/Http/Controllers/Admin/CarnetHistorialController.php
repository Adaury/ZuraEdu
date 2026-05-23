<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarnetAcceso;
use App\Models\CarnetZona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

        $pdf = Pdf::loadView('admin.carnet.historial_pdf', compact('accesos', 'fecha'))
            ->setPaper('letter');

        return $pdf->stream("historial-carnet-{$fecha}.pdf");
    }

    public function excel(Request $request)
    {
        $fecha = $request->fecha ?? today()->toDateString();

        $rows = CarnetAcceso::with(['carnet.user', 'carnet.matricula.grupo'])
            ->whereDate('created_at', $fecha)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($a) => [
                'Nombre'       => $a->carnet?->nombre_completo ?? '—',
                'Carnet'       => $a->carnet?->numero_carnet ?? '—',
                'Grupo'        => $a->carnet?->matricula?->grupo?->nombre_completo ?? '—',
                'Evento'       => $a->tipo_evento,
                'Estado'       => $a->estado,
                'Hora'         => $a->hora,
                'Zona'         => $a->zona?->nombre ?? '—',
                'Dispositivo'  => $a->dispositivo ?? '—',
            ]);

        return Excel::download(
            new \App\Exports\GenericCollectionExport($rows, "Historial {$fecha}"),
            "historial-carnet-{$fecha}.xlsx"
        );
    }
}
