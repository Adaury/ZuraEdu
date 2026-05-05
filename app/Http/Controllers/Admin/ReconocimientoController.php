<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\Reconocimiento;
use App\Models\TipoReconocimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReconocimientoController extends Controller
{
    // ── Index / listado ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Reconocimiento::with(['estudiante', 'tipo', 'emitidoPor']);

        if ($request->filled('tipo_id')) {
            $query->where('tipo_id', $request->tipo_id);
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->filled('entregado')) {
            $query->where('entregado', $request->entregado === '1');
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) =>
                       $s->where('nombres', 'like', "%{$q}%")
                         ->orWhere('apellidos', 'like', "%{$q}%")
                   );
            });
        }

        $reconocimientos = $query->latest('fecha')->paginate(25)->withQueryString();

        $tipos      = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        // Totales rápidos
        $totalGeneral  = Reconocimiento::count();
        $totalEntregados = Reconocimiento::entregados()->count();
        $totalPendientes = Reconocimiento::pendientes()->count();

        return view('admin.reconocimientos.index', compact(
            'reconocimientos', 'tipos', 'estudiantes',
            'totalGeneral', 'totalEntregados', 'totalPendientes'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create()
    {
        $tipos       = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        return view('admin.reconocimientos.create', compact('tipos', 'estudiantes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_id'       => 'required|exists:tipos_reconocimiento,id',
            'titulo'        => 'required|string|max:160',
            'descripcion'   => 'nullable|string|max:1000',
            'fecha'         => 'required|date',
        ]);

        $data['emitido_por_id'] = auth()->id();
        $data['entregado']      = false;

        Reconocimiento::create($data);

        return redirect()->route('reconocimientos.index')
                         ->with('success', 'Reconocimiento registrado correctamente.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(Reconocimiento $reconocimiento)
    {
        $tipos       = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        return view('admin.reconocimientos.create', compact('reconocimiento', 'tipos', 'estudiantes'));
    }

    public function update(Request $request, Reconocimiento $reconocimiento)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_id'       => 'required|exists:tipos_reconocimiento,id',
            'titulo'        => 'required|string|max:160',
            'descripcion'   => 'nullable|string|max:1000',
            'fecha'         => 'required|date',
        ]);

        $reconocimiento->update($data);

        return redirect()->route('reconocimientos.index')
                         ->with('success', 'Reconocimiento actualizado.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Reconocimiento $reconocimiento)
    {
        $reconocimiento->delete();

        return back()->with('success', 'Reconocimiento eliminado.');
    }

    // ── Marcar como entregado (PATCH) ─────────────────────────────────────

    public function marcarEntregado(Reconocimiento $reconocimiento)
    {
        $reconocimiento->update([
            'entregado'     => true,
            'fecha_entrega' => now()->toDateString(),
        ]);

        return back()->with('success', 'Reconocimiento marcado como entregado.');
    }

    // ── Diploma PDF ───────────────────────────────────────────────────────

    public function diplomaPdf(Reconocimiento $reconocimiento)
    {
        $reconocimiento->load(['estudiante', 'tipo', 'emitidoPor']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('director_nombre', '');
        $cod  = ConfigInstitucional::get('codigo_centro', '');
        $logo = ConfigInstitucional::get('logo', null);

        $logoUrl = $logo ? public_path('storage/' . $logo) : null;
        if ($logoUrl && !file_exists($logoUrl)) {
            $logoUrl = null;
        }

        $pdf = Pdf::loadView('admin.reconocimientos.diploma_pdf', compact(
            'reconocimiento', 'inst', 'dir', 'cod', 'logoUrl'
        ))->setPaper('letter', 'landscape');

        $nombre = 'diploma_' . str($reconocimiento->estudiante->apellidos)->slug() . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }

    // ── Historial por Estudiante ──────────────────────────────────────────

    public function historialEstudiante(Estudiante $estudiante)
    {
        $reconocimientos = Reconocimiento::with(['tipo', 'emitidoPor'])
            ->where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get();

        $tipos = TipoReconocimiento::withCount(['reconocimientos as total' => function ($q) use ($estudiante) {
            $q->where('estudiante_id', $estudiante->id);
        }])->having('total', '>', 0)->get();

        return view('admin.reconocimientos.historial_estudiante', compact(
            'estudiante', 'reconocimientos', 'tipos'
        ));
    }
}
