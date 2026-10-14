<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use Illuminate\Http\Request;

class ComunicadosApiController extends Controller
{
    /** GET /api/v1/comunicados */
    public function index(Request $request)
    {
        $user  = $request->user();
        $items = Comunicado::publicados()
            ->when($user, fn($q) => $q->paraUsuario($user))
            ->orderByDesc('published_at')
            ->limit(30)
            ->get()
            ->map(fn($c) => $this->format($c));

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    /** GET /api/v1/comunicados/{comunicado} */
    public function show(Comunicado $comunicado)
    {
        if (! $comunicado->es_publicado) {
            return response()->json(['message' => 'No encontrado.'], 404);
        }

        return response()->json($this->format($comunicado));
    }

    /**
     * GET /api/v1/docente/comint — comunicados internos del personal (misma regla que el portal web:
     * PortalDocenteController::comintIndex), con el acuse de lectura del usuario.
     */
    public function comint(Request $request)
    {
        $user = $request->user();

        $items = Comunicado::internos()
            ->publicados()
            ->paraStaff($user)
            ->with(['autor:id,name', 'lecturas' => fn ($q) => $q->where('user_id', $user->id)])
            ->latest('published_at')
            ->limit(50)
            ->get()
            ->map(fn (Comunicado $c) => [
                'id'                => $c->id,
                'titulo'            => $c->titulo,
                'contenido'         => $c->cuerpo,
                'fecha_publicacion' => $c->published_at?->toDateString(),
                'autor'             => $c->autor?->name,
                'es_interno'        => true,
                'prioridad'         => 'normal',
                'leido'             => $c->lecturas->isNotEmpty(),
            ]);

        return response()->json(['total' => $items->count(), 'data' => $items]);
    }

    /** POST /api/v1/docente/comint/{id}/leer — acuse de recibo (solo de un comunicado que el usuario puede ver). */
    public function comintLeer(Request $request, int $id)
    {
        $user = $request->user();

        $comunicado = Comunicado::internos()->publicados()->paraStaff($user)->findOrFail($id);
        $comunicado->marcarLeido($user->id);

        \Illuminate\Support\Facades\Cache::forget('t' . (tenant_id() ?? 0) . '_user_' . $user->id . '_comint_unread');

        return response()->json(['ok' => true]);
    }

    private function format(Comunicado $c): array
    {
        return [
            'id'          => $c->id,
            'titulo'      => $c->titulo,
            'contenido'   => $c->cuerpo,
            'tipo'        => $c->tipo_destinatarios !== 'todos' ? $c->tipo_destinatarios : 'general',
            'importante'  => false,
            'fecha'       => $c->published_at?->toIso8601String() ?? $c->created_at?->toIso8601String(),
            'adjunto_url' => null,
        ];
    }
}
