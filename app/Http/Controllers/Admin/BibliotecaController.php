<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\Notificacion;
use App\Models\PrestamoBiblioteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BibliotecaController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  LIBROS
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = Libro::query();

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('disponibilidad')) {
            if ($request->disponibilidad === 'disponible') {
                $query->where('cantidad_disponible', '>', 0);
            } elseif ($request->disponibilidad === 'agotado') {
                $query->where('cantidad_disponible', 0);
            }
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhere('autor', 'like', "%{$q}%")
                   ->orWhere('isbn', 'like', "%{$q}%");
            });
        }

        $libros     = $query->orderBy('titulo')->paginate(20)->withQueryString();
        $categorias = Libro::CATEGORIAS;

        $totalLibros       = Libro::count();
        $totalDisponibles  = Libro::where('cantidad_disponible', '>', 0)->count();
        $totalAgotados     = Libro::where('cantidad_disponible', 0)->count();
        $prestamosActivos  = PrestamoBiblioteca::activos()->count();

        return view('admin.biblioteca.index', compact(
            'libros', 'categorias',
            'totalLibros', 'totalDisponibles', 'totalAgotados', 'prestamosActivos'
        ));
    }

    public function create()
    {
        $categorias = Libro::CATEGORIAS;
        return view('admin.biblioteca.libro_form', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'             => 'required|string|max:255',
            'autor'              => 'required|string|max:255',
            'isbn'               => 'nullable|string|max:30|unique:libros,isbn',
            'categoria'          => 'required|string|max:100',
            'cantidad_total'     => 'required|integer|min:1',
            'descripcion'        => 'nullable|string|max:1000',
        ]);

        $data['cantidad_disponible'] = $data['cantidad_total'];

        Libro::create($data);

        return redirect()->route('admin.biblioteca.index')
            ->with('success', 'Libro "' . $data['titulo'] . '" registrado correctamente.');
    }

    public function edit(Libro $libro)
    {
        $categorias = Libro::CATEGORIAS;
        return view('admin.biblioteca.libro_form', compact('libro', 'categorias'));
    }

    public function update(Request $request, Libro $libro)
    {
        $data = $request->validate([
            'titulo'         => 'required|string|max:255',
            'autor'          => 'required|string|max:255',
            'isbn'           => 'nullable|string|max:30|unique:libros,isbn,' . $libro->id,
            'categoria'      => 'required|string|max:100',
            'cantidad_total' => 'required|integer|min:1',
            'descripcion'    => 'nullable|string|max:1000',
        ]);

        // Ajustar disponibles si cambió el total
        $diferencia = $data['cantidad_total'] - $libro->cantidad_total;
        $data['cantidad_disponible'] = max(0, $libro->cantidad_disponible + $diferencia);

        $libro->update($data);

        return redirect()->route('admin.biblioteca.index')
            ->with('success', 'Libro actualizado correctamente.');
    }

    public function show(Libro $libro)
    {
        $prestamosActivos   = $libro->prestamos()->with('estudiante')->activos()->orderByDesc('fecha_prestamo')->get();
        $prestamosVencidos  = $libro->prestamos()->with('estudiante')->vencidos()->orderByDesc('fecha_vencimiento')->get();
        $historial          = $libro->prestamos()->with('estudiante')->devueltos()->orderByDesc('fecha_devolucion')->limit(30)->get();

        return view('admin.biblioteca.show', compact('libro', 'prestamosActivos', 'prestamosVencidos', 'historial'));
    }

    public function destroy(Libro $libro)
    {
        if ($libro->prestamos()->activos()->exists()) {
            return back()->with('error', 'No se puede eliminar un libro con préstamos activos.');
        }

        $titulo = $libro->titulo;
        $libro->delete();

        return back()->with('success', "Libro \"{$titulo}\" eliminado.");
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PRÉSTAMOS
    // ══════════════════════════════════════════════════════════════════════

    public function prestamos(Request $request)
    {
        $query = PrestamoBiblioteca::with(['libro', 'estudiante']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('estudiante', fn($s) =>
                    $s->where('nombres', 'like', "%{$q}%")
                      ->orWhere('apellidos', 'like', "%{$q}%")
                )
                ->orWhereHas('libro', fn($s) =>
                    $s->where('titulo', 'like', "%{$q}%")
                );
            });
        }

        $prestamos = $query->orderByDesc('fecha_prestamo')->paginate(25)->withQueryString();

        $totalActivos   = PrestamoBiblioteca::activos()->count();
        $totalVencidos  = PrestamoBiblioteca::vencidos()->count();
        $totalDevueltos = PrestamoBiblioteca::devueltos()->count();

        return view('admin.biblioteca.prestamos', compact(
            'prestamos', 'totalActivos', 'totalVencidos', 'totalDevueltos'
        ));
    }

    public function prestarForm()
    {
        $libros      = Libro::disponibles()->orderBy('titulo')->get();
        $estudiantes = Estudiante::orderBy('apellidos')->get();
        return view('admin.biblioteca.prestar', compact('libros', 'estudiantes'));
    }

    public function prestar(Request $request)
    {
        $data = $request->validate([
            'libro_id'          => 'required|exists:libros,id',
            'estudiante_id'     => 'required|exists:estudiantes,id',
            'fecha_prestamo'    => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_prestamo',
            'notas'             => 'nullable|string|max:500',
        ]);

        $libro = Libro::findOrFail($data['libro_id']);

        if ($libro->cantidad_disponible <= 0) {
            return back()->withErrors(['libro_id' => 'El libro no tiene ejemplares disponibles.'])->withInput();
        }

        DB::transaction(function () use ($data, $libro) {
            PrestamoBiblioteca::create(array_merge($data, ['estado' => 'activo']));
            $libro->decrement('cantidad_disponible');
        });

        return redirect()->route('admin.biblioteca.prestamos')
            ->with('success', 'Préstamo registrado correctamente.');
    }

    public function devolver(PrestamoBiblioteca $prestamo)
    {
        if ($prestamo->estado === 'devuelto') {
            return back()->with('error', 'Este préstamo ya fue devuelto.');
        }

        DB::transaction(function () use ($prestamo) {
            $prestamo->update([
                'estado'           => 'devuelto',
                'fecha_devolucion' => now()->toDateString(),
            ]);
            $prestamo->libro->increment('cantidad_disponible');
        });

        return back()->with('success', 'Devolución registrada correctamente.');
    }

    public function renovar(Request $request, PrestamoBiblioteca $prestamo)
    {
        if ($prestamo->estado === 'devuelto') {
            return back()->with('error', 'No se puede renovar un préstamo ya devuelto.');
        }

        $data = $request->validate([
            'nueva_fecha' => ['required', 'date', 'after:today'],
        ]);

        $prestamo->update([
            'fecha_vencimiento' => $data['nueva_fecha'],
            'estado'            => 'activo',
            'renovaciones'      => ($prestamo->renovaciones ?? 0) + 1,
        ]);

        return back()->with('success', 'Préstamo renovado hasta el ' . \Carbon\Carbon::parse($data['nueva_fecha'])->format('d/m/Y') . '.');
    }

    public function reportePdf(Request $request)
    {
        $estado = $request->input('estado', 'todos');

        $query = PrestamoBiblioteca::with(['libro', 'estudiante']);

        if ($estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $prestamos = $query->orderByDesc('fecha_prestamo')->get();

        $totalActivos   = $prestamos->where('estado', 'activo')->count();
        $totalVencidos  = $prestamos->where('estado', 'vencido')->count();
        $totalDevueltos = $prestamos->where('estado', 'devuelto')->count();

        $config = (object)['nombre_centro' => \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'))];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.biblioteca.reporte_pdf', compact(
            'prestamos', 'estado', 'totalActivos', 'totalVencidos', 'totalDevueltos', 'config'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('reporte_prestamos_biblioteca.pdf');
    }

    public function catalogoExcel()
    {
        $libros = Libro::orderBy('categoria')->orderBy('titulo')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Catálogo');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Catálogo de Biblioteca — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Título', 'Autor', 'ISBN', 'Categoría', 'Total Ejemplares', 'Disponibles'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($libros as $i => $libro) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $libro->titulo);
            $ws->setCellValue("C{$row}", $libro->autor ?? '—');
            $ws->setCellValue("D{$row}", $libro->isbn ?? '—');
            $ws->setCellValue("E{$row}", $libro->categoria ?? '—');
            $ws->setCellValue("F{$row}", $libro->cantidad_total);
            $ws->setCellValue("G{$row}", $libro->cantidad_disponible);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'bib_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'catalogo_biblioteca_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function reporteExcel(Request $request)
    {
        $query = PrestamoBiblioteca::with(['libro', 'estudiante']);

        if ($request->filled('estado')) {
            match ($request->estado) {
                'activo'    => $query->activos(),
                'vencido'   => $query->vencidos(),
                'devuelto'  => $query->devueltos(),
                default     => null,
            };
        }

        $prestamos = $query->orderByDesc('fecha_prestamo')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Préstamos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Reporte de Préstamos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Estudiante', 'Libro', 'Fecha Préstamo', 'Fecha Devolución', 'Estado', 'Renovaciones'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($prestamos as $i => $p) {
            $row = $i + 4;
            $est = $p->estudiante;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", ($est->nombres ?? '') . ' ' . ($est->apellidos ?? ''));
            $ws->setCellValue("C{$row}", $p->libro?->titulo ?? '—');
            $ws->setCellValue("D{$row}", $p->fecha_prestamo?->format('d/m/Y') ?? '—');
            $ws->setCellValue("E{$row}", $p->fecha_devolucion ? $p->fecha_devolucion->format('d/m/Y') : ($p->fecha_vencimiento?->format('d/m/Y') ?? '—'));
            $ws->setCellValue("F{$row}", $p->estado === 'devuelto' ? 'Devuelto' : ($p->fecha_vencimiento?->isPast() ? 'Vencido' : 'Activo'));
            $ws->setCellValue("G{$row}", $p->renovaciones ?? 0);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'prest_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'prestamos_biblioteca_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function catalogoPdf()
    {
        $libros = Libro::orderBy('categoria')->orderBy('titulo')->get();
        $config = (object)['nombre_centro' => \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'))];

        $totalLibros      = $libros->count();
        $totalEjemplares  = $libros->sum('cantidad_total');
        $totalDisponibles = $libros->sum('cantidad_disponible');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.biblioteca.catalogo_pdf', compact(
            'libros', 'totalLibros', 'totalEjemplares', 'totalDisponibles', 'config'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('catalogo_biblioteca.pdf');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ALERTAS DE VENCIMIENTO
    // ══════════════════════════════════════════════════════════════════════

    public function verificarVencidos()
    {
        $hoy = now()->startOfDay();

        $prestamosVencidos = PrestamoBiblioteca::with(['estudiante.representantes', 'libro'])
            ->where('estado', 'activo')
            ->where('fecha_vencimiento', '<', $hoy)
            ->get();

        $count = 0;

        foreach ($prestamosVencidos as $prestamo) {
            $prestamo->update(['estado' => 'vencido']);

            $titulo  = "Préstamo de biblioteca vencido";
            $mensaje = "El libro \"{$prestamo->libro->titulo}\" prestado el "
                . $prestamo->fecha_prestamo->format('d/m/Y')
                . " venció el " . $prestamo->fecha_vencimiento->format('d/m/Y')
                . ". Por favor, proceda a la devolución.";

            // Notificar al estudiante
            if ($prestamo->estudiante?->user_id) {
                try {
                    Notificacion::enviar(
                        $prestamo->estudiante->user_id,
                        'alerta',
                        $titulo,
                        $mensaje,
                        ['prestamo_id' => $prestamo->id]
                    );
                } catch (\Throwable) {}
            }

            // Notificar a representantes
            foreach ($prestamo->estudiante?->representantes ?? [] as $rep) {
                if ($rep->user_id) {
                    try {
                        Notificacion::enviar(
                            $rep->user_id,
                            'alerta',
                            $titulo,
                            $mensaje,
                            ['prestamo_id' => $prestamo->id]
                        );
                    } catch (\Throwable) {}
                }
            }

            $count++;
        }

        $msg = $count > 0
            ? "{$count} préstamo(s) marcados como vencidos y notificaciones enviadas."
            : "No hay préstamos vencidos pendientes.";

        return back()->with('success', $msg);
    }
}
