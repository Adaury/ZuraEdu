<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificacionApiController extends Controller
{
    /** GET /api/v1/notificaciones */
    public function index(Request $request)
    {
        $uid   = $request->user()->id;
        $items = Notificacion::where('user_id', $uid)
            ->orderByDesc('created_at')->limit(50)->get();

        $noLeidas = $items->where('leida', false)->count();
        Cache::put("user_{$uid}_notif_unread", $noLeidas, 15);

        return response()->json([
            'no_leidas' => $noLeidas,
            'items'     => $items->map(fn($n) => [
                'id'         => $n->id,
                'tipo'       => $n->tipo,
                'titulo'     => $n->titulo,
                'mensaje'    => $n->mensaje,
                'leida'      => $n->leida,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /** PATCH /api/v1/notificaciones/{notificacion}/leer */
    public function marcar(Request $request, Notificacion $notificacion)
    {
        if ($notificacion->user_id !== $request->user()->id) return response()->json(['message' => 'Acceso denegado.'], 403);
        $notificacion->update(['leida' => true, 'leida_en' => now()]);
        Cache::forget("user_{$notificacion->user_id}_notif_unread");
        return response()->json(['ok' => true]);
    }

    /** POST /api/v1/notificaciones/leer-todas */
    public function marcarTodas(Request $request)
    {
        $uid = $request->user()->id;
        Notificacion::where('user_id', $uid)->noLeidas()->update(['leida' => true, 'leida_en' => now()]);
        Cache::put("user_{$uid}_notif_unread", 0, 15);
        return response()->json(['ok' => true]);
    }
}
