<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionApiController extends Controller
{
    /** GET /api/v1/notificaciones */
    public function index(Request $request)
    {
        $items = Notificacion::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->limit(50)->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'tipo'       => $n->tipo,
                'titulo'     => $n->titulo,
                'mensaje'    => $n->mensaje,
                'leida'      => $n->leida,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'no_leidas' => Notificacion::where('user_id', $request->user()->id)->noLeidas()->count(),
            'items'     => $items,
        ]);
    }

    /** PATCH /api/v1/notificaciones/{notificacion}/leer */
    public function marcar(Request $request, Notificacion $notificacion)
    {
        if ($notificacion->user_id !== $request->user()->id) return response()->json(['message' => 'Acceso denegado.'], 403);
        $notificacion->update(['leida' => true, 'leida_en' => now()]);
        return response()->json(['ok' => true]);
    }

    /** POST /api/v1/notificaciones/leer-todas */
    public function marcarTodas(Request $request)
    {
        Notificacion::where('user_id', $request->user()->id)->noLeidas()->update(['leida' => true, 'leida_en' => now()]);
        return response()->json(['ok' => true]);
    }
}
