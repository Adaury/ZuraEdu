<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\SolicitudEstudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SolicitudesEstudianteAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudEstudiante::with(['estudiante', 'respondidoPor'])
            ->orderByRaw("FIELD(estado,'pendiente','en_proceso','aprobada','rechazada')")
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($s) use ($q) {
                $s->where('asunto', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%")
                  ->orWhereHas('estudiante', fn($r) =>
                      $r->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
                  );
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        $tid   = tenant_id();
        $stats = Cache::remember("t{$tid}_solicitudes_est_stats", 60, fn () => [
            'pendientes' => SolicitudEstudiante::where('estado', 'pendiente')->count(),
            'en_proceso' => SolicitudEstudiante::where('estado', 'en_proceso')->count(),
            'total_hoy'  => SolicitudEstudiante::whereDate('created_at', today())->count(),
        ]);

        $tipos   = SolicitudEstudiante::TIPOS;
        $estados = SolicitudEstudiante::estados();

        return view('admin.solicitudes_est.index', compact('solicitudes', 'stats', 'tipos', 'estados'));
    }

    public function show(SolicitudEstudiante $solicitud)
    {
        $solicitud->load(['estudiante', 'respondidoPor']);
        $tipos   = SolicitudEstudiante::TIPOS;
        $estados = SolicitudEstudiante::estados();
        return view('admin.solicitudes_est.show', compact('solicitud', 'tipos', 'estados'));
    }

    public function responder(Request $request, SolicitudEstudiante $solicitud)
    {
        $request->validate([
            'estado'    => ['required', 'in:pendiente,en_proceso,aprobada,rechazada'],
            'respuesta' => ['nullable', 'string', 'max:2000'],
        ]);

        $solicitud->update([
            'estado'         => $request->estado,
            'respuesta'      => $request->respuesta,
            'respondido_por' => Auth::id(),
            'respondido_en'  => now(),
        ]);

        try {
            $user = $solicitud->estudiante?->user;
            if ($user) {
                Notificacion::enviar(
                    $user->id,
                    'info',
                    'Respuesta a tu solicitud',
                    "Tu solicitud \"{$solicitud->asunto}\" cambió a: "
                        . (SolicitudEstudiante::estados()[$request->estado]['label'] ?? $request->estado),
                    ['url' => route('portal.estudiante.solicitudes.show', $solicitud)],
                );
            }
        } catch (\Throwable) {}

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_est_stats");
        Cache::forget("t{$tid}_sol_est_pend");
        return back()->with('success', 'Solicitud actualizada correctamente.');
    }
}
