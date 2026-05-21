<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticuloInventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventarioController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    //  CRUD ARTÍCULOS
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = ArticuloInventario::query();

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('ubicacion', 'like', "%{$q}%")
                   ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        $articulos = $query->latest()->paginate(25)->withQueryString();

        // Tarjetas resumen
        $totalArticulos    = ArticuloInventario::count();
        $totalCategorias   = ArticuloInventario::distinct('categoria')->count('categoria');
        $enMalEstado       = ArticuloInventario::where('estado', 'malo')->count();
        $totalDisponibles  = ArticuloInventario::sum('cantidad_disponible');
        $valorTotalInventario = (float) ArticuloInventario::whereNotNull('costo_unitario')
            ->sum(DB::raw('costo_unitario * cantidad_total'));

        // Conteo por categoría para chips
        $porCategoria = ArticuloInventario::selectRaw('categoria, count(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        $categorias = ArticuloInventario::CATEGORIAS;
        $estados    = ArticuloInventario::ESTADOS;

        return view('admin.inventario.index', compact(
            'articulos', 'categorias', 'estados',
            'totalArticulos', 'totalCategorias', 'enMalEstado', 'totalDisponibles',
            'valorTotalInventario', 'porCategoria'
        ));
    }

    public function create()
    {
        $categorias = ArticuloInventario::CATEGORIAS;
        $estados    = ArticuloInventario::ESTADOS;
        return view('admin.inventario.create', compact('categorias', 'estados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:200',
            'categoria'           => 'required|in:' . implode(',', array_keys(ArticuloInventario::CATEGORIAS)),
            'cantidad_total'      => 'required|integer|min:0',
            'cantidad_disponible' => 'required|integer|min:0',
            'ubicacion'           => 'nullable|string|max:200',
            'descripcion'         => 'nullable|string|max:1000',
            'costo_unitario'      => 'nullable|numeric|min:0',
            'estado'              => 'required|in:' . implode(',', array_keys(ArticuloInventario::ESTADOS)),
        ]);

        ArticuloInventario::create($data);

        return redirect()->route('admin.inventario.index')
                         ->with('success', 'Artículo registrado correctamente.');
    }

    public function edit(ArticuloInventario $articulo)
    {
        $categorias = ArticuloInventario::CATEGORIAS;
        $estados    = ArticuloInventario::ESTADOS;
        return view('admin.inventario.create', compact('articulo', 'categorias', 'estados'));
    }

    public function update(Request $request, ArticuloInventario $articulo)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:200',
            'categoria'           => 'required|in:' . implode(',', array_keys(ArticuloInventario::CATEGORIAS)),
            'cantidad_total'      => 'required|integer|min:0',
            'cantidad_disponible' => 'required|integer|min:0',
            'ubicacion'           => 'nullable|string|max:200',
            'descripcion'         => 'nullable|string|max:1000',
            'costo_unitario'      => 'nullable|numeric|min:0',
            'estado'              => 'required|in:' . implode(',', array_keys(ArticuloInventario::ESTADOS)),
        ]);

        $articulo->update($data);

        return redirect()->route('admin.inventario.index')
                         ->with('success', 'Artículo actualizado.');
    }

    public function destroy(ArticuloInventario $articulo)
    {
        $articulo->delete();
        return back()->with('success', 'Artículo eliminado.');
    }

    // ══════════════════════════════════════════════════════════════════
    //  MOVIMIENTOS
    // ══════════════════════════════════════════════════════════════════

    public function movimientos(ArticuloInventario $articulo)
    {
        $movimientos = $articulo->movimientos()
            ->with('usuario')
            ->latest()
            ->paginate(20);

        $tipos = MovimientoInventario::TIPOS;

        return view('admin.inventario.movimientos', compact('articulo', 'movimientos', 'tipos'));
    }

    public function registrarMovimiento(Request $request, ArticuloInventario $articulo)
    {
        $data = $request->validate([
            'tipo'     => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|integer|min:1',
            'motivo'   => 'required|string|max:300',
        ]);

        // Actualizar cantidad_disponible según tipo
        $disponible = $articulo->cantidad_disponible;

        if ($data['tipo'] === 'entrada') {
            $nueva = $disponible + $data['cantidad'];
            $articulo->increment('cantidad_disponible', $data['cantidad']);
            // También sube el total si la entrada supera el total registrado
            if ($nueva > $articulo->cantidad_total) {
                $articulo->update(['cantidad_total' => $nueva]);
            }
        } elseif ($data['tipo'] === 'salida') {
            if ($data['cantidad'] > $disponible) {
                return back()->withErrors(['cantidad' => 'No hay suficiente cantidad disponible (disponible: ' . $disponible . ').'])->withInput();
            }
            $articulo->decrement('cantidad_disponible', $data['cantidad']);
        } else {
            // Ajuste: setear valor absoluto
            $articulo->update(['cantidad_disponible' => $data['cantidad']]);
        }

        MovimientoInventario::create([
            'articulo_id' => $articulo->id,
            'tipo'        => $data['tipo'],
            'cantidad'    => $data['cantidad'],
            'motivo'      => $data['motivo'],
            'usuario_id'  => auth()->id(),
        ]);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    // ══════════════════════════════════════════════════════════════════
    //  EXPORTES
    // ══════════════════════════════════════════════════════════════════

    public function inventarioPdf()
    {
        $articulos = ArticuloInventario::orderBy('categoria')->orderBy('nombre')->get();

        $porCategoria = $articulos->groupBy('categoria')->map->count();
        $totalMalo    = $articulos->where('estado', 'malo')->count();
        $inst         = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.inventario.reporte_pdf',
            compact('articulos', 'porCategoria', 'totalMalo', 'inst')
        )->setPaper('legal', 'landscape');

        return $pdf->download('inventario_' . now()->format('Ymd') . '.pdf');
    }

    public function historialGlobal(Request $request)
    {
        $query = MovimientoInventario::with(['articulo', 'usuario'])->latest();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('articulo_id')) {
            $query->where('articulo_id', $request->articulo_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->paginate(30)->withQueryString();

        $tipos     = MovimientoInventario::TIPOS;
        $articulos = ArticuloInventario::orderBy('nombre')->pluck('nombre', 'id');

        $totalEntradas = MovimientoInventario::where('tipo', 'entrada')->sum('cantidad');
        $totalSalidas  = MovimientoInventario::where('tipo', 'salida')->sum('cantidad');
        $totalAjustes  = MovimientoInventario::where('tipo', 'ajuste')->count();

        return view('admin.inventario.historial', compact(
            'movimientos', 'tipos', 'articulos',
            'totalEntradas', 'totalSalidas', 'totalAjustes'
        ));
    }

    public function alertas()
    {
        $sinStock   = ArticuloInventario::where('cantidad_disponible', 0)->orderBy('nombre')->get();
        $stockBajo  = ArticuloInventario::where('cantidad_disponible', '>', 0)
            ->whereRaw('cantidad_disponible <= cantidad_total * 0.25')
            ->orderBy('cantidad_disponible')
            ->get();
        $malEstado  = ArticuloInventario::where('estado', 'malo')->orderBy('nombre')->get();
        $reparacion = ArticuloInventario::where('estado', 'reparacion')->orderBy('nombre')->get();

        return view('admin.inventario.alertas', compact(
            'sinStock', 'stockBajo', 'malEstado', 'reparacion'
        ));
    }

    public function movimientosPdf(ArticuloInventario $articulo)
    {
        $movimientos = $articulo->movimientos()->with('usuario')->latest()->get();
        $inst        = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.inventario.movimientos_pdf',
            compact('articulo', 'movimientos', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('movimientos_' . Str::slug($articulo->nombre) . '_' . now()->format('Ymd') . '.pdf');
    }

    public function inventarioExcel()
    {
        $articulos = ArticuloInventario::with([
            'movimientos' => fn($q) => $q->where('created_at', '>=', now()->subMonth())->with('usuario'),
        ])->orderBy('categoria')->orderBy('nombre')->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Inventario');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['#', 'Artículo', 'Categoría', 'Estado', 'Cant. Total', 'Disponible', 'Costo Unit. (RD$)', 'Valor Total (RD$)', 'Ubicación', 'Descripción', 'Movimientos (último mes)'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '1';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A1:K1')->applyFromArray($hdrStyle);

        $valorTotalXls = 0;
        foreach ($articulos as $i => $art) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $art->nombre);
            $sheet->setCellValue("C{$row}", $art->categoria_info['label']);
            $sheet->setCellValue("D{$row}", $art->estado_info['label']);
            $sheet->setCellValue("E{$row}", $art->cantidad_total);
            $sheet->setCellValue("F{$row}", $art->cantidad_disponible);
            $sheet->setCellValue("G{$row}", $art->costo_unitario ? (float) $art->costo_unitario : '');
            $valorArt = ($art->costo_unitario ?? 0) * $art->cantidad_total;
            $sheet->setCellValue("H{$row}", $valorArt > 0 ? $valorArt : '');
            $valorTotalXls += $valorArt;
            $sheet->setCellValue("I{$row}", $art->ubicacion ?? '');
            $sheet->setCellValue("J{$row}", $art->descripcion ?? '');

            $movResumen = $art->movimientos->map(function ($m) {
                return $m->tipo_info['sign'] . $m->cantidad . ' (' . $m->motivo . ')';
            })->implode(' | ');
            $sheet->setCellValue("K{$row}", $movResumen ?: 'Sin movimientos');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:K{$row}")
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        // Fila de totales
        $totalRow = count($articulos) + 2;
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("H{$totalRow}", $valorTotalXls > 0 ? $valorTotalXls : '');
        $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('dbeafe');

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'inv_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'inventario_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function historialExcel(Request $request)
    {
        $query = MovimientoInventario::with(['articulo', 'usuario'])->latest();

        if ($request->filled('tipo'))        $query->where('tipo', $request->tipo);
        if ($request->filled('articulo_id')) $query->where('articulo_id', $request->articulo_id);
        if ($request->filled('fecha_desde')) $query->whereDate('created_at', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('created_at', '<=', $request->fecha_hasta);

        $movimientos = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Historial Inventario');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Historial de Movimientos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha', 'Artículo', 'Tipo', 'Cantidad', 'Usuario'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($movimientos->values() as $i => $m) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $m->created_at?->format('d/m/Y H:i') ?? '—');
            $ws->setCellValue("C{$row}", $m->articulo?->nombre ?? '—');
            $ws->setCellValue("D{$row}", ucfirst($m->tipo));
            $ws->setCellValue("E{$row}", $m->cantidad);
            $ws->setCellValue("F{$row}", $m->usuario?->name ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'historial_inv_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'historial_inventario_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function movimientosExcel(ArticuloInventario $articulo)
    {
        $movimientos = $articulo->movimientos()->with('usuario')->latest()->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Movimientos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Movimientos — ' . $articulo->nombre . ' — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha', 'Tipo', 'Cantidad', 'Usuario'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:E3')->applyFromArray($hdrStyle);

        foreach ($movimientos->values() as $i => $m) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $m->created_at?->format('d/m/Y H:i') ?? '—');
            $ws->setCellValue("C{$row}", ucfirst($m->tipo));
            $ws->setCellValue("D{$row}", $m->cantidad);
            $ws->setCellValue("E{$row}", $m->usuario?->name ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'E') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'mov_inv_') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'movimientos_' . Str::slug($articulo->nombre) . '_' . now()->format('Ymd') . '.xlsx';

        return response()->download($tmp, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
