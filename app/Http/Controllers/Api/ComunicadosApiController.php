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
        $items = Comunicado::where('publicado', true)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'titulo'      => $c->titulo,
                'contenido'   => $c->contenido,
                'tipo'        => $c->tipo ?? 'general',
                'importante'  => (bool) ($c->importante ?? false),
                'fecha'       => $c->created_at?->toIso8601String(),
                'adjunto_url' => $c->archivo ? asset('storage/'.$c->archivo) : null,
            ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    /** GET /api/v1/comunicados/{comunicado} */
    public function show(Comunicado $comunicado)
    {
        if (! $comunicado->publicado) {
            return response()->json(['message' => 'No encontrado.'], 404);
        }

        return response()->json([
            'id'          => $comunicado->id,
            'titulo'      => $comunicado->titulo,
            'contenido'   => $comunicado->contenido,
            'tipo'        => $comunicado->tipo ?? 'general',
            'importante'  => (bool) ($comunicado->importante ?? false),
            'fecha'       => $comunicado->created_at?->toIso8601String(),
            'adjunto_url' => $comunicado->archivo ? asset('storage/'.$comunicado->archivo) : null,
        ]);
    }
}
