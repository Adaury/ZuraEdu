<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarioAcademico;
use App\Models\Evento;
use Illuminate\Http\Request;

class CalendarioApiController extends Controller
{
    /** GET /api/v1/calendario */
    public function index(Request $request)
    {
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->addMonths(2)->endOfMonth()->toDateString());

        // Eventos institucionales
        $eventos = Evento::where('activo', true)
            ->where(fn($q) => $q
                ->whereBetween('fecha_inicio', [$desde, $hasta])
                ->orWhereBetween('fecha_fin', [$desde, $hasta])
            )
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'tipo'        => 'evento',
                'titulo'      => $e->nombre ?? $e->titulo ?? '—',
                'descripcion' => $e->descripcion ?? null,
                'fecha_inicio'=> $e->fecha_inicio instanceof \Carbon\Carbon ? $e->fecha_inicio->toDateString() : $e->fecha_inicio,
                'fecha_fin'   => $e->fecha_fin   instanceof \Carbon\Carbon ? $e->fecha_fin->toDateString()   : $e->fecha_fin,
                'color'       => $e->color ?? '#3b82f6',
                'lugar'       => $e->lugar ?? null,
            ]);

        // Fechas del calendario académico
        $academico = CalendarioAcademico::where(fn($q) => $q
                ->whereBetween('fecha_inicio', [$desde, $hasta])
                ->orWhereBetween('fecha_fin', [$desde, $hasta])
            )
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'tipo'        => 'academico',
                'titulo'      => $c->titulo ?? '—',
                'descripcion' => $c->descripcion ?? null,
                'fecha_inicio'=> $c->fecha_inicio instanceof \Carbon\Carbon ? $c->fecha_inicio->toDateString() : $c->fecha_inicio,
                'fecha_fin'   => $c->fecha_fin   instanceof \Carbon\Carbon ? $c->fecha_fin->toDateString()    : ($c->fecha_inicio instanceof \Carbon\Carbon ? $c->fecha_inicio->toDateString() : $c->fecha_inicio),
                'color'       => $c->color ?? '#f59e0b',
                'lugar'       => null,
            ]);

        $todos = $eventos->concat($academico)->sortBy('fecha_inicio')->values();

        return response()->json([
            'desde'  => $desde,
            'hasta'  => $hasta,
            'total'  => $todos->count(),
            'items'  => $todos,
        ]);
    }
}
