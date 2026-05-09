<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\Notificacion;
use App\Models\PrestamoEquipo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  EQUIPOS
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = Equipo::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        $equipos = $query->orderBy('nombre')->paginate(20)->withQueryString();
        $tipos   = Equipo::TIPOS;
        $estados = Equipo::ESTADOS;

        $totalEquipos       = Equipo::count();
        $totalDisponibles   = Equipo::where('estado', 'disponible')->count();
        $totalPrestados     = Equipo::where('estado', 'prestado')->count();
        $totalMantenimiento = Equipo::where('estado', 'mantenimiento')->count();
        $prestamosActivos   = PrestamoEquipo::activos()->count();

        return view('admin.equipos.index', compact(
            'equipos', 'tipos', 'estados',
            'totalEquipos', 'totalDisponibles', 'totalPrestados',
            'totalMantenimiento', 'prestamosActivos'
        ));
    }

    public function create()
    {
        $tipos   = Equipo::TIPOS;
        $estados = Equipo::ESTADOS;
        return view('admin.equipos.equipo_form', compact('tipos', 'estados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:200',
            'tipo'        => 'required|in:laptop,tablet,proyector,camara,otro',
            'codigo'      => 'nullable|string|max:60|unique:equipos,codigo',
            'estado'      => 'required|in:disponible,prestado,mantenimiento,baja',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        Equipo::create($data);

        return redirect()->route('admin.equipos.index')
            ->with('success', 'Equipo "' . $data['nombre'] . '" registrado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        $tipos   = Equipo::TIPOS;
        $estados = Equipo::ESTADOS;
        return view('admin.equipos.equipo_form', compact('equipo', 'tipos', 'estados'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:200',
            'tipo'        => 'required|in:laptop,tablet,proyector,camara,otro',
            'codigo'      => 'nullable|string|max:60|unique:equipos,codigo,' . $equipo->id,
            'estado'      => 'required|in:disponible,prestado,mantenimiento,baja',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $equipo->update($data);

        return redirect()->route('admin.equipos.index')
            ->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo)
    {
        if ($equipo->prestamos()->activos()->exists()) {
            return back()->with('error', 'No se puede eliminar un equipo con préstamos activos.');
        }

        $nombre = $equipo->nombre;
        $equipo->delete();

        return back()->with('success', "Equipo \"{$nombre}\" eliminado.");
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PRÉSTAMOS
    // ══════════════════════════════════════════════════════════════════════

    public function prestamos(Request $request)
    {
        $query = PrestamoEquipo::with(['equipo', 'usuario']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('usuario', fn($s) =>
                    $s->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                )
                ->orWhereHas('equipo', fn($s) =>
                    $s->where('nombre', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%")
                );
            });
        }

        $prestamos = $query->orderByDesc('fecha_prestamo')->paginate(25)->withQueryString();

        $totalActivos   = PrestamoEquipo::activos()->count();
        $totalVencidos  = PrestamoEquipo::vencidos()->count();
        $totalDevueltos = PrestamoEquipo::devueltos()->count();

        return view('admin.equipos.prestamos', compact(
            'prestamos', 'totalActivos', 'totalVencidos', 'totalDevueltos'
        ));
    }

    public function prestarForm()
    {
        $equipos  = Equipo::disponibles()->orderBy('nombre')->get();
        $usuarios = User::orderBy('name')->get();
        return view('admin.equipos.prestar', compact('equipos', 'usuarios'));
    }

    public function prestar(Request $request)
    {
        $data = $request->validate([
            'equipo_id'         => 'required|exists:equipos,id',
            'usuario_id'        => 'required|exists:users,id',
            'fecha_prestamo'    => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_prestamo',
            'motivo'            => 'nullable|string|max:500',
        ]);

        $equipo = Equipo::findOrFail($data['equipo_id']);

        if ($equipo->estado !== 'disponible') {
            return back()->withErrors(['equipo_id' => 'El equipo no está disponible para préstamo.'])->withInput();
        }

        DB::transaction(function () use ($data, $equipo) {
            PrestamoEquipo::create(array_merge($data, ['estado' => 'activo']));
            $equipo->update(['estado' => 'prestado']);
        });

        return redirect()->route('admin.equipos.prestamos.index')
            ->with('success', 'Préstamo de equipo registrado correctamente.');
    }

    public function devolver(PrestamoEquipo $prestamo)
    {
        if ($prestamo->estado === 'devuelto') {
            return back()->with('error', 'Este préstamo ya fue devuelto.');
        }

        DB::transaction(function () use ($prestamo) {
            $prestamo->update([
                'estado'           => 'devuelto',
                'fecha_devolucion' => now()->toDateString(),
            ]);
            $prestamo->equipo->update(['estado' => 'disponible']);
        });

        return back()->with('success', 'Devolución registrada correctamente.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXCEL LISTA INVENTARIO
    // ══════════════════════════════════════════════════════════════════════

    public function listaExcel(Request $request)
    {
        $query = Equipo::query();

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"));
        }

        $equipos = $query->orderBy('nombre')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Inventario Equipos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a8a']],
        ];

        $estadoColors = ['disponible' => 'd1fae5', 'prestado' => 'fef9c3', 'mantenimiento' => 'ffedd5', 'baja' => 'f3f4f6'];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Inventario de Equipos Tecnológicos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Código', 'Tipo', 'Estado', 'Descripción'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($equipos->values() as $i => $eq) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $eq->nombre);
            $ws->setCellValue("C{$row}", $eq->codigo ?? '—');
            $ws->setCellValue("D{$row}", Equipo::TIPOS[$eq->tipo] ?? $eq->tipo);
            $ws->setCellValue("E{$row}", Equipo::ESTADOS[$eq->estado] ?? $eq->estado);
            $ws->setCellValue("F{$row}", $eq->descripcion ?? '—');
            $color = $estadoColors[$eq->estado] ?? 'f9fafb';
            $ws->getStyle("A{$row}:F{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'equipos_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'inventario_equipos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PDF LISTA INVENTARIO
    // ══════════════════════════════════════════════════════════════════════

    public function listaPdf(Request $request)
    {
        $query = Equipo::query();

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"));
        }

        $equipos = $query->orderBy('nombre')->get();
        $inst    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.equipos.lista_pdf',
            compact('equipos', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('inventario_equipos_' . now()->format('Ymd') . '.pdf');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXCEL PRÉSTAMOS
    // ══════════════════════════════════════════════════════════════════════

    public function prestamosExcel(Request $request)
    {
        $query = PrestamoEquipo::with(['equipo', 'usuario']);

        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) =>
                $sq->whereHas('usuario', fn($s) => $s->where('name', 'like', "%{$q}%"))
                   ->orWhereHas('equipo', fn($s) => $s->where('nombre', 'like', "%{$q}%"))
            );
        }

        $prestamos = $query->orderByDesc('fecha_prestamo')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Préstamos Equipos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1e3a8a']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Préstamos de Equipos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Equipo', 'Código', 'Usuario', 'F. Préstamo', 'F. Vencimiento', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($prestamos->values() as $i => $p) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $p->equipo?->nombre ?? '—');
            $ws->setCellValue("C{$row}", $p->equipo?->codigo ?? '—');
            $ws->setCellValue("D{$row}", $p->usuario?->name ?? '—');
            $ws->setCellValue("E{$row}", $p->fecha_prestamo?->format('d/m/Y') ?? '—');
            $ws->setCellValue("F{$row}", $p->fecha_vencimiento?->format('d/m/Y') ?? '—');
            $ws->setCellValue("G{$row}", ucfirst($p->estado));
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'prestamos_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'prestamos_equipos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  COMPROBANTE PDF
    // ══════════════════════════════════════════════════════════════════════

    public function comprobantePdf(PrestamoEquipo $prestamo)
    {
        $prestamo->load(['equipo', 'usuario']);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $logo = \App\Models\ConfigInstitucional::get('logo_path');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.equipos.comprobante_pdf',
            compact('prestamo', 'inst', 'logo')
        )->setPaper('letter', 'portrait');

        $filename = 'comprobante_prestamo_equipo_' . $prestamo->id . '.pdf';

        return $pdf->stream($filename);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ALERTAS DE VENCIMIENTO
    // ══════════════════════════════════════════════════════════════════════

    public function verificarVencidos()
    {
        $hoy = now()->startOfDay();

        $prestamosVencidos = PrestamoEquipo::with(['equipo', 'usuario'])
            ->where('estado', 'activo')
            ->where('fecha_vencimiento', '<', $hoy)
            ->get();

        $count = 0;

        foreach ($prestamosVencidos as $prestamo) {
            $prestamo->update(['estado' => 'vencido']);

            $titulo  = "Préstamo de equipo vencido";
            $mensaje = "El equipo \"{$prestamo->equipo->nombre}\" prestado el "
                . $prestamo->fecha_prestamo->format('d/m/Y')
                . " venció el " . $prestamo->fecha_vencimiento->format('d/m/Y')
                . ". Por favor, proceda a la devolución.";

            if ($prestamo->usuario_id) {
                try {
                    Notificacion::enviar(
                        $prestamo->usuario_id,
                        'alerta',
                        $titulo,
                        $mensaje,
                        ['prestamo_equipo_id' => $prestamo->id]
                    );
                } catch (\Throwable) {}
            }

            $count++;
        }

        $msg = $count > 0
            ? "{$count} préstamo(s) marcados como vencidos y notificaciones enviadas."
            : "No hay préstamos vencidos pendientes.";

        return back()->with('success', $msg);
    }
}
