<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticuloInventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;

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

        // Conteo por categoría para chips
        $porCategoria = ArticuloInventario::selectRaw('categoria, count(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        $categorias = ArticuloInventario::CATEGORIAS;
        $estados    = ArticuloInventario::ESTADOS;

        return view('admin.inventario.index', compact(
            'articulos', 'categorias', 'estados',
            'totalArticulos', 'totalCategorias', 'enMalEstado', 'totalDisponibles',
            'porCategoria'
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
        )->setPaper('letter', 'landscape');

        return $pdf->download('inventario_' . now()->format('Ymd') . '.pdf');
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

        $headers = ['#', 'Artículo', 'Categoría', 'Estado', 'Cant. Total', 'Disponible', 'Ubicación', 'Descripción', 'Movimientos (último mes)'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '1';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A1:I1')->applyFromArray($hdrStyle);

        foreach ($articulos as $i => $art) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $art->nombre);
            $sheet->setCellValue("C{$row}", $art->categoria_info['label']);
            $sheet->setCellValue("D{$row}", $art->estado_info['label']);
            $sheet->setCellValue("E{$row}", $art->cantidad_total);
            $sheet->setCellValue("F{$row}", $art->cantidad_disponible);
            $sheet->setCellValue("G{$row}", $art->ubicacion ?? '');
            $sheet->setCellValue("H{$row}", $art->descripcion ?? '');

            // Resumen de movimientos del último mes
            $movResumen = $art->movimientos->map(function ($m) {
                return $m->tipo_info['sign'] . $m->cantidad . ' (' . $m->motivo . ')';
            })->implode(' | ');
            $sheet->setCellValue("I{$row}", $movResumen ?: 'Sin movimientos');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:I{$row}")
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'inv_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'inventario_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
