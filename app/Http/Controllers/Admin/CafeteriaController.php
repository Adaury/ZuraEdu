<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\ProductoCafeteria;
use App\Models\VentaCafeteria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CafeteriaController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  PRODUCTOS
    // ══════════════════════════════════════════════════════════════════════

    public function indexProductos(Request $request)
    {
        $query = ProductoCafeteria::query();

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }
        if ($request->filled('q')) {
            $query->where('nombre', 'like', '%' . $request->q . '%');
        }

        $productos   = $query->orderBy('nombre')->paginate(25)->withQueryString();
        $categorias  = ProductoCafeteria::CATEGORIAS;

        return view('admin.cafeteria.productos', compact('productos', 'categorias'));
    }

    public function createProducto()
    {
        $categorias = ProductoCafeteria::CATEGORIAS;
        return view('admin.cafeteria.producto_form', compact('categorias'));
    }

    public function storeProducto(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:120',
            'precio'    => 'required|numeric|min:0',
            'categoria' => ['required', Rule::in(array_keys(ProductoCafeteria::CATEGORIAS))],
            'activo'    => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        ProductoCafeteria::create($data);

        return redirect()->route('cafeteria.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function editProducto(ProductoCafeteria $producto)
    {
        $categorias = ProductoCafeteria::CATEGORIAS;
        return view('admin.cafeteria.producto_form', compact('producto', 'categorias'));
    }

    public function updateProducto(Request $request, ProductoCafeteria $producto)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:120',
            'precio'    => 'required|numeric|min:0',
            'categoria' => ['required', Rule::in(array_keys(ProductoCafeteria::CATEGORIAS))],
            'activo'    => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $producto->update($data);

        return redirect()->route('cafeteria.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroyProducto(ProductoCafeteria $producto)
    {
        if ($producto->ventas()->exists()) {
            return back()->with('error', 'No se puede eliminar: el producto tiene ventas registradas.');
        }

        $producto->delete();

        return back()->with('success', 'Producto eliminado.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VENTAS
    // ══════════════════════════════════════════════════════════════════════

    public function ventas(Request $request)
    {
        $query = VentaCafeteria::with(['estudiante', 'producto', 'creadoPor']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('estudiante', fn($s) =>
                    $s->where('nombres', 'like', "%{$q}%")
                      ->orWhere('apellidos', 'like', "%{$q}%")
                )->orWhereHas('producto', fn($s) =>
                    $s->where('nombre', 'like', "%{$q}%")
                )->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        $movimientos = $query->latest()->paginate(30)->withQueryString();

        // Totales del día
        $hoy           = today();
        $totalVentasHoy = VentaCafeteria::whereDate('created_at', $hoy)->where('tipo', 'venta')->sum('monto');
        $totalRecargasHoy = VentaCafeteria::whereDate('created_at', $hoy)->where('tipo', 'recarga')->sum('monto');

        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $productos   = ProductoCafeteria::activos()->orderBy('nombre')->get();

        return view('admin.cafeteria.ventas', compact(
            'movimientos', 'estudiantes', 'productos',
            'totalVentasHoy', 'totalRecargasHoy'
        ));
    }

    public function registrarVenta(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'producto_id'   => 'nullable|exists:productos_cafeteria,id',
            'descripcion'   => 'nullable|string|max:200',
            'monto'         => 'required|numeric|min:0.01',
        ]);

        $saldoAnterior = VentaCafeteria::saldoEstudiante((int) $data['estudiante_id']);

        if ($saldoAnterior < (float) $data['monto']) {
            return back()->withInput()
                ->with('error', 'Saldo insuficiente. Saldo actual: ' . number_format($saldoAnterior, 2));
        }

        // Si viene producto_id, usar su nombre como descripción si no se puso una
        if (empty($data['descripcion']) && !empty($data['producto_id'])) {
            $producto = ProductoCafeteria::find($data['producto_id']);
            $data['descripcion'] = $producto?->nombre;
        }

        VentaCafeteria::create([
            'estudiante_id'  => $data['estudiante_id'],
            'producto_id'    => $data['producto_id'] ?? null,
            'descripcion'    => $data['descripcion'],
            'tipo'           => 'venta',
            'monto'          => $data['monto'],
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo'    => $saldoAnterior - $data['monto'],
            'created_by_id'  => auth()->id(),
        ]);

        return back()->with('success', 'Venta registrada. Nuevo saldo: ' . number_format($saldoAnterior - $data['monto'], 2));
    }

    public function registrarRecarga(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'monto'         => 'required|numeric|min:0.01',
            'descripcion'   => 'nullable|string|max:200',
        ]);

        $saldoAnterior = VentaCafeteria::saldoEstudiante((int) $data['estudiante_id']);
        $saldoNuevo    = $saldoAnterior + (float) $data['monto'];

        VentaCafeteria::create([
            'estudiante_id'  => $data['estudiante_id'],
            'producto_id'    => null,
            'descripcion'    => $data['descripcion'] ?? 'Recarga de saldo',
            'tipo'           => 'recarga',
            'monto'          => $data['monto'],
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo'    => $saldoNuevo,
            'created_by_id'  => auth()->id(),
        ]);

        return back()->with('success', 'Recarga aplicada. Nuevo saldo: ' . number_format($saldoNuevo, 2));
    }

    // ── Balance por estudiante ─────────────────────────────────────────────

    public function balanceEstudiante(Request $request, Estudiante $estudiante)
    {
        $historial = VentaCafeteria::with(['producto', 'creadoPor'])
            ->where('estudiante_id', $estudiante->id)
            ->latest()
            ->paginate(30);

        $saldo       = VentaCafeteria::saldoEstudiante($estudiante->id);
        $totalGastado = VentaCafeteria::where('estudiante_id', $estudiante->id)->where('tipo', 'venta')->sum('monto');
        $totalRecargado = VentaCafeteria::where('estudiante_id', $estudiante->id)->where('tipo', 'recarga')->sum('monto');

        $productos = ProductoCafeteria::activos()->orderBy('nombre')->get();

        return view('admin.cafeteria.balance', compact(
            'estudiante', 'historial', 'saldo',
            'totalGastado', 'totalRecargado', 'productos'
        ));
    }

    // ══════════════════════════════════════════════════════════════════════
    //  REPORTES
    // ══════════════════════════════════════════════════════════════════════

    public function reportePdf(Request $request)
    {
        $fecha  = $request->filled('fecha') ? $request->fecha : today()->toDateString();
        $ventas = VentaCafeteria::with(['estudiante', 'producto', 'creadoPor'])
            ->whereDate('created_at', $fecha)
            ->latest()
            ->get();

        // Totales por categoría de producto
        $porCategoria = $ventas->filter(fn($v) => $v->tipo === 'venta' && $v->producto)
            ->groupBy(fn($v) => $v->producto->categoria)
            ->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('monto')]);

        $totalVentas   = $ventas->where('tipo', 'venta')->sum('monto');
        $totalRecargas = $ventas->where('tipo', 'recarga')->sum('monto');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.cafeteria.reporte_pdf',
            compact('ventas', 'porCategoria', 'totalVentas', 'totalRecargas', 'fecha', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('cafeteria_' . $fecha . '.pdf');
    }

    public function reporteCsv(Request $request)
    {
        $fecha  = $request->filled('fecha') ? $request->fecha : today()->toDateString();
        $ventas = VentaCafeteria::with(['estudiante', 'producto', 'creadoPor'])
            ->whereDate('created_at', $fecha)
            ->latest()
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cafeteria_' . $fecha . '.csv"',
        ];

        $callback = function () use ($ventas) {
            $handle = fopen('php://output', 'w');
            // BOM para Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['#', 'Fecha/Hora', 'Estudiante', 'Tipo', 'Producto/Descripcion', 'Monto', 'Saldo Anterior', 'Saldo Nuevo', 'Registrado por']);

            foreach ($ventas as $i => $v) {
                fputcsv($handle, [
                    $i + 1,
                    $v->created_at->format('d/m/Y H:i'),
                    $v->estudiante?->nombre_completo ?? '—',
                    ucfirst($v->tipo),
                    $v->producto?->nombre ?? $v->descripcion ?? '—',
                    number_format($v->monto, 2),
                    number_format($v->saldo_anterior, 2),
                    number_format($v->saldo_nuevo, 2),
                    $v->creadoPor?->name ?? '—',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
