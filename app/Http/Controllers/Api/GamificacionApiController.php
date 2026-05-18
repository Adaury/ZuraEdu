<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\PuntoEstudiante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class GamificacionApiController extends Controller
{
    /** GET /api/v1/gamificacion/hijo/{estudiante} */
    public function hijoPuntos(Request $request, \App\Models\Estudiante $estudiante)
    {
        $user = $request->user();
        $rep  = \App\Models\Representante::where('user_id', $user->id)->first();

        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['error' => 'No tienes acceso a este estudiante.'], 403);
        }

        $sy       = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) {
            return response()->json(['totalPuntos' => 0, 'insignias' => [], 'historial' => [], 'puntosCategoria' => [], 'ranking' => [], 'miPosicion' => null]);
        }

        $totalPuntos = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');

        $historial = PuntoEstudiante::where('matricula_id', $matricula->id)
            ->orderByDesc('fecha')->orderByDesc('id')->limit(20)->get()
            ->map(fn($p) => ['concepto' => $p->concepto, 'categoria' => $p->categoria, 'puntos' => $p->puntos, 'fecha' => $p->fecha]);

        $puntosCategoria = PuntoEstudiante::where('matricula_id', $matricula->id)
            ->selectRaw('categoria, SUM(puntos) as total')->groupBy('categoria')->get()
            ->map(fn($r) => ['categoria' => $r->categoria, 'total' => (int) $r->total]);

        $obtenidas = InsigniaEstudiante::where('matricula_id', $matricula->id)->get()->keyBy('tipo');
        $insignias = collect(InsigniaEstudiante::TIPOS)->map(function ($info, $tipo) use ($obtenidas) {
            $item = $obtenidas->get($tipo);
            return ['tipo' => $tipo, 'label' => $info['label'] ?? $tipo, 'obtenida' => (bool) $item, 'fecha_obtencion' => $item?->fecha_obtencion?->toDateString()];
        })->values();

        $grupoIds = Matricula::where('grupo_id', $matricula->grupo_id)->where('estado', 'activa')->with('estudiante')->get();
        $rankingRaw = PuntoEstudiante::whereIn('matricula_id', $grupoIds->pluck('id'))
            ->selectRaw('matricula_id, SUM(puntos) as total')->groupBy('matricula_id')->orderByDesc('total')->get();

        $miPosicion = null;
        $ranking = $rankingRaw->take(10)->map(function ($r, $idx) use ($grupoIds, $matricula, &$miPosicion) {
            if ($r->matricula_id === $matricula->id) $miPosicion = $idx + 1;
            $mat = $grupoIds->firstWhere('id', $r->matricula_id);
            return ['posicion' => $idx + 1, 'nombre' => $mat?->estudiante?->nombre_completo ?? '—', 'total' => (int) $r->total, 'es_hijo' => $r->matricula_id === $matricula->id];
        })->values();

        if ($miPosicion === null && (int) $totalPuntos === 0) $miPosicion = $rankingRaw->count() + 1;

        return response()->json(['totalPuntos' => (int) $totalPuntos, 'insignias' => $insignias, 'historial' => $historial, 'puntosCategoria' => $puntosCategoria, 'ranking' => $ranking, 'miPosicion' => $miPosicion, 'totalEnGrupo' => $grupoIds->count()]);
    }

    /** GET /api/v1/gamificacion/grupo/{asignacion} — ranking para docente */
    public function grupoPuntos(Request $request, Asignacion $asignacion)
    {
        $user    = $request->user();
        $docente = Docente::where('user_id', $user->id)->first();

        if (! $docente || $asignacion->docente_id !== $docente->id) {
            return response()->json(['error' => 'No tienes acceso a esta asignación.'], 403);
        }

        $sy  = SchoolYear::actual();
        $syId = $sy?->id ?? 0;

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $syId))
            ->get();

        $mids = $matriculas->pluck('id');

        $puntosMap = PuntoEstudiante::whereIn('matricula_id', $mids)
            ->selectRaw('matricula_id, SUM(puntos) as total')
            ->groupBy('matricula_id')
            ->get()->keyBy('matricula_id');

        $insigniasMap = InsigniaEstudiante::whereIn('matricula_id', $mids)
            ->selectRaw('matricula_id, COUNT(*) as cnt')
            ->groupBy('matricula_id')
            ->get()->keyBy('matricula_id');

        $ranking = $matriculas->map(function ($m) use ($puntosMap, $insigniasMap) {
            return [
                'matricula_id' => $m->id,
                'nombre'       => $m->estudiante?->nombre_completo ?? '—',
                'puntos'       => (int) ($puntosMap->get($m->id)?->total ?? 0),
                'insignias'    => (int) ($insigniasMap->get($m->id)?->cnt ?? 0),
            ];
        })->sortByDesc('puntos')->values()
          ->map(fn($item, $idx) => array_merge($item, ['posicion' => $idx + 1]));

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        return response()->json([
            'asignacion' => [
                'id'         => $asignacion->id,
                'asignatura' => $asignacion->asignatura?->nombre,
                'grupo'      => $asignacion->grupo?->nombre_completo,
            ],
            'ranking'       => $ranking,
            'totalGrupo'    => $matriculas->count(),
            'totalPuntos'   => $puntosMap->sum('total'),
            'totalInsignias'=> $insigniasMap->sum('cnt'),
        ]);
    }

    /** POST /api/v1/gamificacion/grupo/{asignacion}/asignar — docente asigna puntos */
    public function asignarPuntos(Request $request, Asignacion $asignacion)
    {
        $user    = $request->user();
        $docente = Docente::where('user_id', $user->id)->first();

        if (! $docente || $asignacion->docente_id !== $docente->id) {
            return response()->json(['error' => 'No tienes acceso a esta asignación.'], 403);
        }

        $request->validate([
            'matricula_id' => 'required|integer|exists:matriculas,id',
            'concepto'     => 'required|string|max:150',
            'categoria'    => 'required|in:' . implode(',', array_keys(PuntoEstudiante::CATEGORIAS)),
            'puntos'       => 'required|integer|min:1|max:500',
            'fecha'        => 'required|date',
        ]);

        $sy   = SchoolYear::actual();
        $syId = $sy?->id ?? 0;

        $matricula = Matricula::where('id', $request->matricula_id)
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $syId))
            ->first();

        if (! $matricula) {
            return response()->json(['error' => 'El estudiante no pertenece a este grupo.'], 422);
        }

        $punto = PuntoEstudiante::create([
            'matricula_id' => $matricula->id,
            'concepto'     => $request->concepto,
            'categoria'    => $request->categoria,
            'puntos'       => $request->puntos,
            'fecha'        => $request->fecha,
        ]);

        // Verificar insignias acumuladas (100/500 pts)
        $total = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
        if ($total >= 100) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matricula->id, 'tipo' => 'cien_puntos'],
                ['fecha_obtencion' => today()]
            );
        }
        if ($total >= 500) {
            InsigniaEstudiante::firstOrCreate(
                ['matricula_id' => $matricula->id, 'tipo' => 'quinientos_puntos'],
                ['fecha_obtencion' => today()]
            );
        }

        return response()->json(['ok' => true, 'punto' => $punto, 'totalPuntos' => (int) $total], 201);
    }

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
