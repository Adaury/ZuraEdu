<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\PuntoEstudiante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class GamificacionApiController extends Controller
{
    /** GET /api/v1/gamificacion/mis-puntos */
    public function misPuntos(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->first();

        if (! $estudiante) {
            return response()->json(['error' => 'Sin perfil de estudiante.'], 403);
        }

        $sy       = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) {
            return response()->json([
                'totalPuntos'    => 0,
                'insignias'      => [],
                'historial'      => [],
                'puntosCategoria'=> [],
                'ranking'        => [],
                'miPosicion'     => null,
            ]);
        }

        $totalPuntos = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');

        // Historial últimos 20
        $historial = PuntoEstudiante::where('matricula_id', $matricula->id)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->limit(20)->get()
            ->map(fn($p) => [
                'concepto'  => $p->concepto,
                'categoria' => $p->categoria,
                'puntos'    => $p->puntos,
                'fecha'     => $p->fecha,
            ]);

        // Desglose por categoría
        $puntosCategoria = PuntoEstudiante::where('matricula_id', $matricula->id)
            ->selectRaw('categoria, SUM(puntos) as total')
            ->groupBy('categoria')
            ->get()
            ->map(fn($r) => ['categoria' => $r->categoria, 'total' => (int) $r->total]);

        // Insignias — todas con estado obtenido/no obtenido
        $obtenidas = InsigniaEstudiante::where('matricula_id', $matricula->id)
            ->get()->keyBy('tipo');

        $insignias = collect(InsigniaEstudiante::TIPOS)->map(function ($info, $tipo) use ($obtenidas) {
            $item = $obtenidas->get($tipo);
            return [
                'tipo'           => $tipo,
                'label'          => $info['label'] ?? $tipo,
                'obtenida'       => (bool) $item,
                'fecha_obtencion'=> $item?->fecha_obtencion?->toDateString(),
            ];
        })->values();

        // Ranking del grupo (top 10)
        $grupoIds = Matricula::where('grupo_id', $matricula->grupo_id)
            ->where('estado', 'activa')
            ->with('estudiante')
            ->get();

        $rankingRaw = PuntoEstudiante::whereIn('matricula_id', $grupoIds->pluck('id'))
            ->selectRaw('matricula_id, SUM(puntos) as total')
            ->groupBy('matricula_id')
            ->orderByDesc('total')
            ->get();

        $miPosicion = null;
        $ranking = $rankingRaw->take(10)->map(function ($r, $idx) use ($grupoIds, $matricula, &$miPosicion) {
            if ($r->matricula_id === $matricula->id) {
                $miPosicion = $idx + 1;
            }
            $mat = $grupoIds->firstWhere('id', $r->matricula_id);
            return [
                'posicion'     => $idx + 1,
                'nombre'       => $mat?->estudiante?->nombre_completo ?? '—',
                'total'        => (int) $r->total,
                'es_yo'        => $r->matricula_id === $matricula->id,
            ];
        })->values();

        if ($miPosicion === null && (int) $totalPuntos === 0) {
            $miPosicion = $rankingRaw->count() + 1;
        }

        return response()->json([
            'totalPuntos'    => (int) $totalPuntos,
            'insignias'      => $insignias,
            'historial'      => $historial,
            'puntosCategoria'=> $puntosCategoria,
            'ranking'        => $ranking,
            'miPosicion'     => $miPosicion,
            'totalEnGrupo'   => $grupoIds->count(),
        ]);
    }
}
