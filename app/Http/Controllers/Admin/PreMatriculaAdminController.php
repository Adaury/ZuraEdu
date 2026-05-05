<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PreMatriculaResolucion;
use App\Models\PreMatricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PreMatriculaAdminController extends Controller
{
    /**
     * Listado de todas las solicitudes con filtros.
     */
    public function index(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }

        if ($request->filled('grado')) {
            $query->porGrado($request->grado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $solicitudes  = $query->paginate(20)->withQueryString();
        $grados       = PreMatricula::gradosDisponibles();
        $totalPendientes = PreMatricula::pendientes()->count();

        // Conteos para tarjetas
        $conteos = [
            'total'     => PreMatricula::count(),
            'pendiente' => PreMatricula::where('estado', 'pendiente')->count(),
            'aprobada'  => PreMatricula::where('estado', 'aprobada')->count(),
            'rechazada' => PreMatricula::where('estado', 'rechazada')->count(),
        ];

        return view('admin.pre_matriculas.index', compact(
            'solicitudes', 'grados', 'conteos', 'totalPendientes'
        ));
    }

    /**
     * Detalle de una solicitud.
     */
    public function show(PreMatricula $preMatricula)
    {
        return view('admin.pre_matriculas.show', compact('preMatricula'));
    }

    /**
     * Aprobar solicitud.
     */
    public function aprobar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $preMatricula->update([
            'estado'      => 'aprobada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} aprobada. Se notificó al representante.");
    }

    /**
     * Rechazar solicitud.
     */
    public function rechazar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['required', 'string', 'max:1000'],
        ], [
            'notas_admin.required' => 'Debe indicar el motivo del rechazo.',
        ]);

        $preMatricula->update([
            'estado'      => 'rechazada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} rechazada. Se notificó al representante.");
    }

    /**
     * Eliminar solicitud.
     */
    public function destroy(PreMatricula $preMatricula)
    {
        $nombre = $preMatricula->nombre_completo;
        $preMatricula->delete();

        return redirect()->route('admin.pre-matriculas.index')
            ->with('success', "Solicitud de {$nombre} eliminada.");
    }
}
