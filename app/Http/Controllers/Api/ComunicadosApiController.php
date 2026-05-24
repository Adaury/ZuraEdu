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
