<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\SolicitudDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SolicitudesDocenteAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudDocente::with(['docente', 'respondidoPor'])
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
                  ->orWhereHas('docente', fn($r) =>
                      $r->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
                  );
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        $tid   = tenant_id();
        $stats = Cache::remember("t{$tid}_solicitudes_doc_stats", 60, fn () => [
            'pendientes' => SolicitudDocente::where('estado', 'pendiente')->count(),
            'en_proceso' => SolicitudDocente::where('estado', 'en_proceso')->count(),
            'total_hoy'  => SolicitudDocente::whereDate('created_at', today())->count(),
        ]);

        $tipos   = SolicitudDocente::TIPOS;
        $estados = SolicitudDocente::estados();

        return view('admin.solicitudes_doc.index', compact('solicitudes', 'stats', 'tipos', 'estados'));
    }

    public function show(SolicitudDocente $solicitudDocente)
    {
        $solicitudDocente->load(['docente', 'respondidoPor']);
        $tipos   = SolicitudDocente::TIPOS;
        $estados = SolicitudDocente::estados();
        return view('admin.solicitudes_doc.show', compact('solicitudDocente', 'tipos', 'estados'));
    }

    public function responder(Request $request, SolicitudDocente $solicitudDocente)
    {
        $request->validate([
            'estado'    => ['required', 'in:pendiente,en_proceso,aprobada,rechazada'],
            'respuesta' => ['nullable', 'string', 'max:2000'],
        ]);

        $solicitudDocente->update([
            'estado'         => $request->estado,
            'respuesta'      => $request->respuesta,
            'respondido_por' => Auth::id(),
            'respondido_en'  => now(),
        ]);

        try {
            $user = $solicitudDocente->docente?->user;
            if ($user) {
                Notificacion::enviar(
                    $user->id,
                    'info',
                    'Respuesta a tu solicitud',
                    "Tu solicitud \"{$solicitudDocente->asunto}\" cambió a: "
                        . (SolicitudDocente::estados()[$request->estado]['label'] ?? $request->estado),
                    ['url' => route('portal.docente.solicitudes.show', $solicitudDocente)]
                );
            }
        } catch (\Throwable) {}

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_doc_stats");
        Cache::forget("t{$tid}_sol_doc_pend");
        return back()->with('success', 'Solicitud actualizada correctamente.');
    }
}
