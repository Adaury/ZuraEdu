<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\ProductoCafeteria;
use App\Models\VentaCafeteria;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CafeteriaController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  DASHBOARD
    // ══════════════════════════════════════════════════════════════════════

    public function dashboard()
    {
        $hoy = today();

        $totalVentasHoy    = VentaCafeteria::whereDate('created_at', $hoy)->where('tipo', 'venta')->sum('monto');
        $totalRecargasHoy  = VentaCafeteria::whereDate('created_at', $hoy)->where('tipo', 'recarga')->sum('monto');
        $totalVentasMes    = VentaCafeteria::whereMonth('created_at', now()->month)
                                           ->whereYear('created_at', now()->year)
                                           ->where('tipo', 'venta')->sum('monto');
        $productosActivos  = ProductoCafeteria::where('activo', true)->count();

        // Estudiantes con saldo bajo (< 50 RD$) — se obtiene del saldo_nuevo de la última transacción por estudiante
        $saldosBajos = VentaCafeteria::selectRaw('estudiante_id, saldo_nuevo')
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')->from('ventas_cafeteria')->groupBy('estudiante_id');
            })
            ->where('saldo_nuevo', '<', 50)
            ->where('saldo_nuevo', '>=', 0)
            ->count();

        $ultimosMovimientos = VentaCafeteria::with(['estudiante', 'producto'])
            ->latest()->take(10)->get();

        $categorias = ProductoCafeteria::CATEGORIAS;

        return view('admin.cafeteria.dashboard', compact(
            'totalVentasHoy', 'totalRecargasHoy', 'totalVentasMes',
            'productosActivos', 'saldosBajos', 'ultimosMovimientos', 'categorias'
        ));
    }

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

        return redirect()->route('admin.cafeteria.productos.index')
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

        return redirect()->route('admin.cafeteria.productos.index')
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

    /** Colegio de la petición (el que resuelve ResolveTenant); nunca un ID que mande el navegador. */
    private function tenantId(): int
    {
        $tenant = tenant();
        abort_unless($tenant, 403, 'No hay un colegio asociado a su sesión.');

        return (int) $tenant->id;
    }

    /** Tope por operación: evita un error de columna (decimal 10,2) con un monto absurdo y un desliz de ceros en la ventanilla. */
    private const MONTO_MAXIMO = 50000;

    /** Regla «existe en MI colegio»: `exists:` a secas ve las filas de todos los colegios (y filtra si un ID existe). */
    private function existeEnMiColegio(string $tabla): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists($tabla, 'id')->where('tenant_id', $this->tenantId())->whereNull('deleted_at');
    }

    /**
     * Anti doble envío: cada formulario lleva un token único; si llega por segunda vez (doble clic, recargar la página,
     * reintento del navegador) la operación NO se repite. Sin token (API o pruebas) no se aplica.
     */
    private function yaRegistrada(Request $request): bool
    {
        $token = (string) $request->input('token_operacion');
        if ($token === '') {
            return false;
        }

        return ! Cache::add('cafeteria_op:' . $this->tenantId() . ':' . auth()->id() . ':' . $token, 1, now()->addMinutes(30));
    }

    public function registrarVenta(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => ['required', $this->existeEnMiColegio('estudiantes')],
            'producto_id'   => ['nullable', Rule::exists('productos_cafeteria', 'id')->where('tenant_id', $this->tenantId())],
            'descripcion'   => 'nullable|string|max:200',
            'monto'         => 'required|numeric|between:0.01,' . self::MONTO_MAXIMO,
        ]);

        if ($this->yaRegistrada($request)) {
            return back()->with('success', 'Esta venta ya estaba registrada: no se repitió.');
        }

        // Si viene producto_id, usar su nombre como descripción si no se puso una
        if (empty($data['descripcion']) && !empty($data['producto_id'])) {
            $producto = ProductoCafeteria::find($data['producto_id']);
            $data['descripcion'] = $producto?->nombre;
        }

        // Atómico: comprueba el saldo y descuenta bajo el bloqueo del estudiante (ventas simultáneas no se aprueban todas).
        $venta = VentaCafeteria::registrarMovimiento(
            (int) $data['estudiante_id'], 'venta', (float) $data['monto'], $data['descripcion'] ?? null,
            isset($data['producto_id']) ? (int) $data['producto_id'] : null, auth()->id()
        );

        if (! $venta) {
            return back()->withInput()
                ->with('error', 'Saldo insuficiente. Saldo actual: ' . number_format(VentaCafeteria::saldoEstudiante((int) $data['estudiante_id']), 2));
        }

        return back()->with('success', 'Venta registrada. Nuevo saldo: ' . number_format((float) $venta->saldo_nuevo, 2));
    }

    public function registrarRecarga(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => ['required', $this->existeEnMiColegio('estudiantes')],
            'monto'         => 'required|numeric|between:0.01,' . self::MONTO_MAXIMO,
            'descripcion'   => 'nullable|string|max:200',
        ]);

        if ($this->yaRegistrada($request)) {
            return back()->with('success', 'Esta recarga ya estaba registrada: no se repitió.');
        }

        // Atómico: las recargas simultáneas se serializan por estudiante y el saldo visible es la suma real (antes quedaba en el de la última).
        $recarga = VentaCafeteria::registrarMovimiento(
            (int) $data['estudiante_id'], 'recarga', (float) $data['monto'], $data['descripcion'] ?? 'Recarga de saldo', null, auth()->id()
        );

        // Es dinero que entra: queda en el log de actividad además del propio movimiento (que guarda quién lo hizo)
        $est = Estudiante::find($data['estudiante_id']);
        ActivityLog::registrar('cafeteria_recarga', 'VentaCafeteria', $recarga->id,
            sprintf('Recarga de RD$%s a %s (saldo %s → %s)', number_format((float) $recarga->monto, 2), $est?->nombre_completo ?? "#{$data['estudiante_id']}",
                number_format((float) $recarga->saldo_anterior, 2), number_format((float) $recarga->saldo_nuevo, 2)));

        return back()->with('success', 'Recarga aplicada. Nuevo saldo: ' . number_format((float) $recarga->saldo_nuevo, 2));
    }

    public function registrarAjuste(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => ['required', $this->existeEnMiColegio('estudiantes')],
            'monto'         => ['required', 'numeric', 'between:-' . self::MONTO_MAXIMO . ',' . self::MONTO_MAXIMO, 'not_in:0'],
            'descripcion'   => 'required|string|max:200',
        ]);

        if ($this->yaRegistrada($request)) {
            return back()->with('success', 'Este ajuste ya estaba registrado: no se repitió.');
        }

        $ajuste = VentaCafeteria::registrarMovimiento(
            (int) $data['estudiante_id'], 'ajuste', (float) $data['monto'], $data['descripcion'], null, auth()->id()
        );

        if (! $ajuste) {
            return back()->withInput()->with('error', 'El ajuste dejaría el saldo en negativo. Saldo actual: RD$' . number_format(VentaCafeteria::saldoEstudiante((int) $data['estudiante_id']), 2));
        }

        $est = Estudiante::find($data['estudiante_id']);
        ActivityLog::registrar('cafeteria_ajuste', 'VentaCafeteria', $ajuste->id,
            sprintf('Ajuste de saldo de %sRD$%s a %s (saldo %s → %s). Motivo: %s', (float) $data['monto'] < 0 ? '-' : '+', number_format(abs((float) $data['monto']), 2),
                $est?->nombre_completo ?? "#{$data['estudiante_id']}", number_format((float) $ajuste->saldo_anterior, 2), number_format((float) $ajuste->saldo_nuevo, 2), $data['descripcion']));

        return back()->with('success', 'Ajuste registrado. Nuevo saldo: RD$' . number_format((float) $ajuste->saldo_nuevo, 2));
    }

    // ── Balance por estudiante ─────────────────────────────────────────────

    public function balanceEstudiante(Request $request, Estudiante $estudiante)
    {
        $historial = VentaCafeteria::with(['producto', 'creadoPor'])
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('id')   // por id: created_at tiene resolución de segundos y empata
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

    public function reporteExcel(Request $request)
    {
        $fecha  = $request->filled('fecha') ? $request->fecha : today()->toDateString();
        $ventas = VentaCafeteria::with(['estudiante', 'producto', 'creadoPor'])
            ->whereDate('created_at', $fecha)
            ->latest()
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Cafetería');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '065f46']],
        ];

        $ws->mergeCells('A1:I1');
        $ws->setCellValue('A1', 'Reporte Cafetería — ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha/Hora', 'Estudiante', 'Tipo', 'Producto/Descripción', 'Monto', 'Saldo Anterior', 'Saldo Nuevo', 'Registrado por'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:I3')->applyFromArray($hdrStyle);

        $totalVentas = $totalRecargas = 0;
        foreach ($ventas as $i => $v) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $v->created_at->format('d/m/Y H:i'));
            $ws->setCellValue("C{$row}", $v->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", ucfirst($v->tipo));
            $ws->setCellValue("E{$row}", $v->producto?->nombre ?? $v->descripcion ?? '—');
            $ws->setCellValue("F{$row}", $v->monto);
            $ws->setCellValue("G{$row}", $v->saldo_anterior);
            $ws->setCellValue("H{$row}", $v->saldo_nuevo);
            $ws->setCellValue("I{$row}", $v->creadoPor?->name ?? '—');
            if ($v->tipo === 'venta') {
                $totalVentas += $v->monto;
                $bg = 'fff7ed';
            } else {
                $totalRecargas += $v->monto;
                $bg = 'f0fdf4';
            }
            $ws->getStyle("A{$row}:I{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        }

        // Fila de totales
        $totRow = $ventas->count() + 4;
        $ws->setCellValue("D{$totRow}", 'TOTAL VENTAS');
        $ws->setCellValue("F{$totRow}", $totalVentas);
        $ws->setCellValue("D" . ($totRow + 1), 'TOTAL RECARGAS');
        $ws->setCellValue("F" . ($totRow + 1), $totalRecargas);
        $ws->getStyle("D{$totRow}:F" . ($totRow + 1))->getFont()->setBold(true);

        foreach (range('A', 'I') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'caf_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'cafeteria_' . $fecha . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
