<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\SolicitudRepresentante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudesAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudRepresentante::with(['representante', 'estudiante', 'respondidoPor'])
            ->orderByRaw("FIELD(estado, 'pendiente', 'en_proceso', 'aprobada', 'rechazada')")
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
                  ->orWhereHas('representante', fn($r) =>
                      $r->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
                  );
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        $stats = [
            'pendientes'  => SolicitudRepresentante::where('estado', 'pendiente')->count(),
            'en_proceso'  => SolicitudRepresentante::where('estado', 'en_proceso')->count(),
            'total_hoy'   => SolicitudRepresentante::whereDate('created_at', today())->count(),
        ];

        $tipos   = SolicitudRepresentante::TIPOS;
        $estados = SolicitudRepresentante::ESTADOS;

        return view('admin.solicitudes.index', compact('solicitudes', 'stats', 'tipos', 'estados'));
    }

    public function show(SolicitudRepresentante $solicitud)
    {
        $solicitud->load(['representante', 'estudiante', 'respondidoPor']);
        $tipos   = SolicitudRepresentante::TIPOS;
        $estados = SolicitudRepresentante::ESTADOS;
        return view('admin.solicitudes.show', compact('solicitud', 'tipos', 'estados'));
    }

    public function responder(Request $request, SolicitudRepresentante $solicitud)
    {
        $data = $request->validate([
            'estado'    => 'required|in:en_proceso,aprobada,rechazada',
            'respuesta' => 'required|string|max:2000',
        ]);

        $solicitud->update([
            'estado'        => $data['estado'],
            'respuesta'     => $data['respuesta'],
            'respondido_por'=> Auth::id(),
            'respondido_en' => now(),
        ]);

        // Notificar al representante si tiene cuenta de usuario
        $userId = $solicitud->representante?->user_id;
        if ($userId) {
            $estadoLabel = SolicitudRepresentante::ESTADOS[$data['estado']]['label'] ?? $data['estado'];
            Notificacion::create([
                'user_id' => $userId,
                'tipo'    => 'solicitud',
                'titulo'  => "Solicitud {$estadoLabel}: {$solicitud->asunto}",
                'mensaje' => $data['respuesta'],
                'leida'   => false,
            ]);
        }

        return back()->with('success', 'Respuesta enviada correctamente.');
    }
}
