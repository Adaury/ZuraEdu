<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\PrestamoBiblioteca;
use Illuminate\Http\Request;

class BibliotecaApiController extends Controller
{
    public function misPrestamos(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->firstOrFail();

        $mapPrestamo = fn(PrestamoBiblioteca $p) => [
            'id'               => $p->id,
            'libro_titulo'     => $p->libro?->titulo,
            'libro_autor'      => $p->libro?->autor,
            'libro_categoria'  => $p->libro?->categoria,
            'fecha_prestamo'   => $p->fecha_prestamo?->format('Y-m-d'),
            'fecha_vencimiento'=> $p->fecha_vencimiento?->format('Y-m-d'),
            'fecha_devolucion' => $p->fecha_devolucion?->format('Y-m-d'),
            'estado'           => $p->estado,
            'esta_vencido'     => $p->esta_vencido,
            'dias_restantes'   => $p->fecha_vencimiento
                ? now()->startOfDay()->diffInDays($p->fecha_vencimiento->startOfDay(), false)
                : null,
        ];

        $activos = PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('estado', ['activo', 'vencido'])
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map($mapPrestamo);

        $historial = PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', 'devuelto')
            ->orderByDesc('fecha_devolucion')
            ->limit(20)
            ->get()
            ->map($mapPrestamo);

        return response()->json([
            'activos'  => $activos,
            'historial'=> $historial,
        ]);
    }

    public function hijoPrestamos(Request $request, Estudiante $estudiante)
    {
        $user          = $request->user();
        $representante = \App\Models\Representante::where('user_id', $user->id)->firstOrFail();

        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $mapPrestamo = fn(PrestamoBiblioteca $p) => [
            'id'               => $p->id,
            'libro_titulo'     => $p->libro?->titulo,
            'libro_autor'      => $p->libro?->autor,
            'libro_categoria'  => $p->libro?->categoria,
            'fecha_prestamo'   => $p->fecha_prestamo?->format('Y-m-d'),
            'fecha_vencimiento'=> $p->fecha_vencimiento?->format('Y-m-d'),
            'fecha_devolucion' => $p->fecha_devolucion?->format('Y-m-d'),
            'estado'           => $p->estado,
            'esta_vencido'     => $p->esta_vencido,
            'dias_restantes'   => $p->fecha_vencimiento
                ? now()->startOfDay()->diffInDays($p->fecha_vencimiento->startOfDay(), false)
                : null,
        ];

        $activos = PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('estado', ['activo', 'vencido'])
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map($mapPrestamo);

        $historial = PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', 'devuelto')
            ->orderByDesc('fecha_devolucion')
            ->limit(20)
            ->get()
            ->map($mapPrestamo);

        return response()->json([
            'estudiante' => ['id' => $estudiante->id, 'nombre' => $estudiante->nombre_completo],
            'activos'    => $activos,
            'historial'  => $historial,
        ]);
    }
}
