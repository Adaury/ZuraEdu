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
