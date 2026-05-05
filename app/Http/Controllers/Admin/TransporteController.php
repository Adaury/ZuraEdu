<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\EstudianteRuta;
use App\Models\ParadaRuta;
use App\Models\RutaTransporte;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    // ── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = RutaTransporte::withCount('estudiantesRuta');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('conductor', 'like', "%{$q}%")
                   ->orWhere('vehiculo', 'like', "%{$q}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $rutas = $query->orderBy('nombre')->paginate(20)->withQueryString();

        return view('admin.transporte.index', compact('rutas'));
    }

    // ── CREATE / STORE ───────────────────────────────────────────────────────

    public function create()
    {
        return view('admin.transporte.create', ['ruta' => new RutaTransporte()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:500',
            'conductor'   => 'nullable|string|max:120',
            'vehiculo'    => 'nullable|string|max:120',
            'capacidad'   => 'required|integer|min:1|max:200',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $ruta = RutaTransporte::create($data);

        return redirect()->route('admin.transporte.show', $ruta)
                         ->with('success', 'Ruta creada correctamente.');
    }

    // ── SHOW ─────────────────────────────────────────────────────────────────

    public function show(Request $request, RutaTransporte $ruta)
    {
        $ruta->load(['paradas', 'estudiantesRuta.estudiante', 'estudiantesRuta.parada']);

        // Buscador de estudiantes para asignar
        $busqueda  = $request->input('buscar_estudiante');
        $candidatos = collect();

        if ($busqueda) {
            $asignados = $ruta->estudiantesRuta->pluck('estudiante_id');
            $candidatos = Estudiante::activos()
                ->where(function ($q) use ($busqueda) {
                    $q->where('nombres', 'like', "%{$busqueda}%")
                      ->orWhere('apellidos', 'like', "%{$busqueda}%")
                      ->orWhere('numero_matricula', 'like', "%{$busqueda}%");
                })
                ->whereNotIn('id', $asignados)
                ->orderBy('apellidos')
                ->limit(15)
                ->get();
        }

        return view('admin.transporte.show', compact('ruta', 'candidatos', 'busqueda'));
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────────────────

    public function edit(RutaTransporte $ruta)
    {
        return view('admin.transporte.create', compact('ruta'));
    }

    public function update(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:500',
            'conductor'   => 'nullable|string|max:120',
            'vehiculo'    => 'nullable|string|max:120',
            'capacidad'   => 'required|integer|min:1|max:200',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $ruta->update($data);

        return redirect()->route('admin.transporte.show', $ruta)
                         ->with('success', 'Ruta actualizada correctamente.');
    }

    // ── DESTROY ──────────────────────────────────────────────────────────────

    public function destroy(RutaTransporte $ruta)
    {
        $ruta->delete();

        return redirect()->route('admin.transporte.index')
                         ->with('success', 'Ruta eliminada.');
    }

    // ── PARADAS (CRUD inline) ────────────────────────────────────────────────

    public function storeParada(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:120',
            'hora_estimada' => 'nullable|date_format:H:i',
        ]);

        $maxOrden = $ruta->paradas()->max('orden') ?? 0;
        $data['ruta_id'] = $ruta->id;
        $data['orden']   = $maxOrden + 1;

        ParadaRuta::create($data);

        return back()->with('success', 'Parada agregada.');
    }

    public function updateParada(Request $request, RutaTransporte $ruta, ParadaRuta $parada)
    {
        abort_if($parada->ruta_id !== $ruta->id, 404);

        $data = $request->validate([
            'nombre'        => 'required|string|max:120',
            'hora_estimada' => 'nullable|date_format:H:i',
            'orden'         => 'required|integer|min:1',
        ]);

        $parada->update($data);

        return back()->with('success', 'Parada actualizada.');
    }

    public function destroyParada(RutaTransporte $ruta, ParadaRuta $parada)
    {
        abort_if($parada->ruta_id !== $ruta->id, 404);
        $parada->delete();

        return back()->with('success', 'Parada eliminada.');
    }

    public function reordenarParadas(Request $request, RutaTransporte $ruta)
    {
        $request->validate(['orden' => 'required|array', 'orden.*' => 'integer']);

        foreach ($request->orden as $pos => $paradaId) {
            ParadaRuta::where('id', $paradaId)
                      ->where('ruta_id', $ruta->id)
                      ->update(['orden' => $pos + 1]);
        }

        return response()->json(['ok' => true]);
    }

    // ── ASIGNAR / DESASIGNAR ESTUDIANTE ──────────────────────────────────────

    public function asignarEstudiante(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo'          => 'required|in:ida,vuelta,ambos',
            'parada_id'     => 'nullable|exists:paradas_ruta,id',
        ]);

        // Verificar que el estudiante no esté ya asignado a esta ruta
        $existe = EstudianteRuta::where('ruta_id', $ruta->id)
                                ->where('estudiante_id', $data['estudiante_id'])
                                ->exists();

        if ($existe) {
            return back()->with('error', 'El estudiante ya está asignado a esta ruta.');
        }

        // Verificar capacidad
        if ($ruta->ocupacion >= $ruta->capacidad) {
            return back()->with('error', 'La ruta está a plena capacidad.');
        }

        EstudianteRuta::create([
            'ruta_id'       => $ruta->id,
            'estudiante_id' => $data['estudiante_id'],
            'tipo'          => $data['tipo'],
            'parada_id'     => $data['parada_id'] ?: null,
        ]);

        return back()->with('success', 'Estudiante asignado a la ruta.');
    }

    public function desasignarEstudiante(RutaTransporte $ruta, EstudianteRuta $asignacion)
    {
        abort_if($asignacion->ruta_id !== $ruta->id, 404);
        $asignacion->delete();

        return back()->with('success', 'Estudiante removido de la ruta.');
    }

    // ── PDF PASAJEROS ────────────────────────────────────────────────────────

    public function pasajerosPdf(RutaTransporte $ruta)
    {
        $ruta->load(['paradas', 'estudiantesRuta.estudiante', 'estudiantesRuta.parada']);

        // Agrupar estudiantes por parada
        $porParada = $ruta->paradas->map(function ($parada) use ($ruta) {
            $parada->pasajeros = $ruta->estudiantesRuta
                ->filter(fn($er) => $er->parada_id === $parada->id)
                ->values();
            return $parada;
        });

        // Sin parada asignada
        $sinParada = $ruta->estudiantesRuta->filter(fn($er) => is_null($er->parada_id))->values();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $logo = \App\Models\ConfigInstitucional::get('logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.transporte.pasajeros_pdf',
            compact('ruta', 'porParada', 'sinParada', 'inst', 'logo')
        )->setPaper('letter', 'portrait');

        $nombre = 'pasajeros_' . \Illuminate\Support\Str::slug($ruta->nombre) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
