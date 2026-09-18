<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Notificacion;
use App\Models\Representante;
use App\Models\SolicitudDocente;
use App\Models\SolicitudEstudiante;
use App\Models\SolicitudRepresentante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SolicitudesApiController extends Controller
{
    /** GET /api/v1/solicitudes */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->tieneRolDocente())               return $this->indexDocente($user);
        if ($user->hasRole('Estudiante'))     return $this->indexEstudiante($user);
        if ($user->hasRole('Representante'))  return $this->indexRepresentante($user);

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /** POST /api/v1/solicitudes */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->tieneRolDocente())               return $this->storeDocente($request, $user);
        if ($user->hasRole('Estudiante'))     return $this->storeEstudiante($request, $user);
        if ($user->hasRole('Representante'))  return $this->storeRepresentante($request, $user);

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /** GET /api/v1/solicitudes/{id} */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->tieneRolDocente()) {
            $doc = Docente::where('user_id', $user->id)->first();
            if (! $doc) return response()->json(['message' => 'Perfil no encontrado.'], 404);

            $sol = SolicitudDocente::where('id', $id)->where('docente_id', $doc->id)->first();
            if (! $sol) return response()->json(['message' => 'Solicitud no encontrada.'], 404);

            return response()->json(['solicitud' => $this->formatDocente($sol)]);
        }

        if ($user->hasRole('Estudiante')) {
            $est = Estudiante::where('user_id', $user->id)->first();
            if (! $est) return response()->json(['message' => 'Perfil no encontrado.'], 404);

            $sol = SolicitudEstudiante::where('id', $id)
                ->where('estudiante_id', $est->id)
                ->first();
            if (! $sol) return response()->json(['message' => 'Solicitud no encontrada.'], 404);

            return response()->json(['solicitud' => $this->formatEstudiante($sol)]);
        }

        if ($user->hasRole('Representante')) {
            $rep = Representante::where('user_id', $user->id)->first();
            if (! $rep) return response()->json(['message' => 'Perfil no encontrado.'], 404);

            $sol = SolicitudRepresentante::where('id', $id)
                ->where('representante_id', $rep->id)
                ->with(['estudiante', 'docente'])
                ->first();
            if (! $sol) return response()->json(['message' => 'Solicitud no encontrada.'], 404);

            return response()->json(['solicitud' => $this->formatRepresentante($sol)]);
        }

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    // ── Privados Docente ──────────────────────────────────────────────────────

    private function indexDocente($user)
    {
        $doc = Docente::where('user_id', $user->id)->first();
        if (! $doc) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $solicitudes = SolicitudDocente::where('docente_id', $doc->id)
            ->orderByRaw("FIELD(estado,'pendiente','en_proceso','aprobada','rechazada')")
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'pendientes' => $solicitudes->where('estado', 'pendiente')->count(),
            'en_proceso' => $solicitudes->where('estado', 'en_proceso')->count(),
            'aprobadas'  => $solicitudes->where('estado', 'aprobada')->count(),
            'total'      => $solicitudes->count(),
        ];

        return response()->json([
            'tipos'       => SolicitudDocente::TIPOS,
            'stats'       => $stats,
            'solicitudes' => $solicitudes->map(fn($s) => $this->formatDocente($s)),
        ]);
    }

    private function storeDocente(Request $request, $user)
    {
        $doc = Docente::where('user_id', $user->id)->first();
        if (! $doc) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $validated = $request->validate([
            'tipo'         => ['required', 'in:' . implode(',', array_keys(SolicitudDocente::TIPOS))],
            'asunto'       => ['required', 'string', 'max:200'],
            'descripcion'  => ['required', 'string', 'max:3000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $sol = SolicitudDocente::create([
            'docente_id'  => $doc->id,
            'tipo'        => $validated['tipo'],
            'asunto'      => $validated['asunto'],
            'descripcion' => $validated['descripcion'],
            'fecha_inicio'=> $validated['fecha_inicio'] ?? null,
            'fecha_fin'   => $validated['fecha_fin']    ?? null,
            'estado'      => 'pendiente',
        ]);

        try {
            User::role(['Administrador', 'Director'])->each(function ($admin) use ($doc, $sol) {
                Notificacion::create([
                    'user_id' => $admin->id,
                    'titulo'  => 'Solicitud docente: ' . (SolicitudDocente::TIPOS[$sol->tipo] ?? $sol->tipo),
                    'cuerpo'  => "{$doc->apellidos}, {$doc->nombres} envió: {$sol->asunto}",
                    'tipo'    => 'info',
                    'leida'   => false,
                ]);
            });
        } catch (\Throwable) {}

        return response()->json([
            'message'   => 'Solicitud enviada correctamente.',
            'solicitud' => $this->formatDocente($sol),
        ], 201);
    }

    private function formatDocente(SolicitudDocente $s): array
    {
        $estados = SolicitudDocente::estados();
        $ec = $estados[$s->estado] ?? $estados['pendiente'];

        return [
            'id'           => $s->id,
            'tipo'         => $s->tipo,
            'tipo_label'   => SolicitudDocente::TIPOS[$s->tipo] ?? $s->tipo,
            'asunto'       => $s->asunto,
            'descripcion'  => $s->descripcion,
            'fecha_inicio' => $s->fecha_inicio?->toDateString(),
            'fecha_fin'    => $s->fecha_fin?->toDateString(),
            'estado'       => $s->estado,
            'estado_label' => $ec['label'],
            'estado_color' => $ec['color'],
            'respuesta'    => $s->respuesta,
            'respondido_en'=> $s->respondido_en?->toDateTimeString(),
            'creado_en'    => $s->created_at->toDateTimeString(),
            'creado_hace'  => $s->created_at->diffForHumans(),
        ];
    }

    // ── Privados Estudiante ───────────────────────────────────────────────────

    private function indexEstudiante($user)
    {
        $est = Estudiante::where('user_id', $user->id)->first();
        if (! $est) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $solicitudes = SolicitudEstudiante::where('estudiante_id', $est->id)
            ->orderByRaw("FIELD(estado,'pendiente','en_proceso','aprobada','rechazada')")
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'pendientes'  => $solicitudes->where('estado', 'pendiente')->count(),
            'en_proceso'  => $solicitudes->where('estado', 'en_proceso')->count(),
            'aprobadas'   => $solicitudes->where('estado', 'aprobada')->count(),
            'total'       => $solicitudes->count(),
        ];

        return response()->json([
            'tipos'       => SolicitudEstudiante::TIPOS,
            'stats'       => $stats,
            'solicitudes' => $solicitudes->map(fn($s) => $this->formatEstudiante($s)),
        ]);
    }

    private function storeEstudiante(Request $request, $user)
    {
        $est = Estudiante::where('user_id', $user->id)->first();
        if (! $est) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $validated = $request->validate([
            'tipo'         => ['required', 'in:' . implode(',', array_keys(SolicitudEstudiante::TIPOS))],
            'asunto'       => ['required', 'string', 'max:200'],
            'descripcion'  => ['required', 'string', 'max:2000'],
            'fecha_evento' => ['nullable', 'date'],
        ]);

        $sol = SolicitudEstudiante::create([
            'estudiante_id' => $est->id,
            'tipo'          => $validated['tipo'],
            'asunto'        => $validated['asunto'],
            'descripcion'   => $validated['descripcion'],
            'fecha_evento'  => $validated['fecha_evento'] ?? null,
            'estado'        => 'pendiente',
        ]);

        try {
            foreach (User::role(['Administrador', 'Director'])->get() as $admin) {
                Notificacion::create([
                    'user_id' => $admin->id,
                    'titulo'  => 'Nueva solicitud de estudiante',
                    'cuerpo'  => "{$est->nombre_completo} envió una solicitud: {$sol->asunto}.",
                    'tipo'    => 'info',
                    'url'     => route('admin.solicitudes-est.show', $sol),
                ]);
            }
        } catch (\Throwable) {}

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_est_stats");
        Cache::forget("t{$tid}_sol_est_pend");

        return response()->json(['message' => 'Solicitud enviada correctamente.', 'solicitud' => $this->formatEstudiante($sol)], 201);
    }

    private function formatEstudiante(SolicitudEstudiante $s): array
    {
        $estados = SolicitudEstudiante::estados();
        $ec = $estados[$s->estado] ?? $estados['pendiente'];

        return [
            'id'           => $s->id,
            'tipo'         => $s->tipo,
            'tipo_label'   => SolicitudEstudiante::TIPOS[$s->tipo] ?? $s->tipo,
            'asunto'       => $s->asunto,
            'descripcion'  => $s->descripcion,
            'fecha_evento' => $s->fecha_evento?->toDateString(),
            'estado'       => $s->estado,
            'estado_label' => $ec['label'],
            'estado_color' => $ec['color'],
            'respuesta'    => $s->respuesta,
            'respondido_en'=> $s->respondido_en?->toDateTimeString(),
            'creado_en'    => $s->created_at->toDateTimeString(),
            'creado_hace'  => $s->created_at->diffForHumans(),
        ];
    }

    // ── Privados Representante ────────────────────────────────────────────────

    private function indexRepresentante($user)
    {
        $rep = Representante::where('user_id', $user->id)->first();
        if (! $rep) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $solicitudes = SolicitudRepresentante::where('representante_id', $rep->id)
            ->with(['estudiante', 'docente'])
            ->orderByDesc('created_at')
            ->get();

        $hijosModels = $rep->estudiantes()->get();
        $hijos = $hijosModels->map(fn($e) => [
            'id'     => $e->id,
            'nombre' => $e->nombre_completo,
        ]);

        // Docentes reales por hijo, para picker de cita_docente (mismo criterio
        // que el formulario web, ver Portal\SolicitudesController::create()).
        $schoolYear = \App\Models\SchoolYear::actual();
        $docentesPorHijo = [];
        foreach ($hijosModels as $hijo) {
            $matricula = $hijo->matriculas()
                ->where('estado', 'activa')
                ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
                ->latest()->first();

            $docentesPorHijo[$hijo->id] = $matricula
                ? \App\Models\Asignacion::with(['docente', 'asignatura'])
                    ->where('grupo_id', $matricula->grupo_id)
                    ->where('activo', true)
                    ->whereNotNull('docente_id')
                    ->get()
                    ->unique('docente_id')
                    ->map(fn ($a) => [
                        'id'    => $a->docente_id,
                        'label' => trim(($a->docente?->nombre_completo ?? 'Docente') . ' — ' . ($a->asignatura?->nombre ?? '')),
                    ])
                    ->values()
                : [];
        }

        $stats = [
            'pendientes'  => $solicitudes->where('estado', 'pendiente')->count(),
            'en_proceso'  => $solicitudes->where('estado', 'en_proceso')->count(),
            'aprobadas'   => $solicitudes->where('estado', 'aprobada')->count(),
            'total'       => $solicitudes->count(),
        ];

        return response()->json([
            'tipos'             => SolicitudRepresentante::TIPOS,
            'hijos'             => $hijos,
            'docentes_por_hijo' => $docentesPorHijo,
            'stats'             => $stats,
            'solicitudes'       => $solicitudes->map(fn($s) => $this->formatRepresentante($s)),
        ]);
    }

    private function storeRepresentante(Request $request, $user)
    {
        $rep = Representante::where('user_id', $user->id)->first();
        if (! $rep) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $validated = $request->validate([
            'tipo'          => ['required', 'in:' . implode(',', array_keys(SolicitudRepresentante::TIPOS))],
            'asunto'        => ['required', 'string', 'max:200'],
            'descripcion'   => ['required', 'string', 'max:3000'],
            'fecha_evento'  => ['nullable', 'date'],
            'estudiante_id' => ['nullable', 'integer'],
            'docente_id'    => ['nullable', 'integer'],
        ]);

        $estudianteId = null;
        if (! empty($validated['estudiante_id'])) {
            $ids = $rep->estudiantes()->pluck('estudiantes.id')->toArray();
            if (in_array((int) $validated['estudiante_id'], $ids)) {
                $estudianteId = (int) $validated['estudiante_id'];
            }
        }

        // docente_id solo aplica a cita_docente, y solo si ese docente
        // realmente da clase al hijo elegido -- mismo chequeo que el
        // formulario web (SolicitudesController::store()), nunca confiar en
        // el ID que llega del cliente.
        $docenteId = null;
        if ($validated['tipo'] === 'cita_docente' && $estudianteId && ! empty($validated['docente_id'])) {
            $schoolYear = \App\Models\SchoolYear::actual();
            $matricula  = \App\Models\Matricula::where('estudiante_id', $estudianteId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
                ->latest()->first();

            if ($matricula && \App\Models\Asignacion::where('grupo_id', $matricula->grupo_id)
                    ->where('docente_id', (int) $validated['docente_id'])
                    ->where('activo', true)
                    ->exists()) {
                $docenteId = (int) $validated['docente_id'];
            }
        }

        $sol = SolicitudRepresentante::create([
            'representante_id' => $rep->id,
            'estudiante_id'    => $estudianteId,
            'docente_id'       => $docenteId,
            'tipo'             => $validated['tipo'],
            'asunto'           => $validated['asunto'],
            'descripcion'      => $validated['descripcion'],
            'fecha_evento'     => $validated['fecha_evento'] ?? null,
            'estado'           => 'pendiente',
        ]);

        try {
            User::role(['Administrador', 'Director'])->each(function ($admin) use ($rep, $sol) {
                Notificacion::create([
                    'user_id' => $admin->id,
                    'tipo'    => 'solicitud',
                    'titulo'  => 'Nueva solicitud: ' . (SolicitudRepresentante::TIPOS[$sol->tipo] ?? $sol->tipo),
                    'cuerpo'  => "{$rep->nombre_completo} envió: {$sol->asunto}",
                    'leida'   => false,
                ]);
            });
        } catch (\Throwable) {}

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_rep_stats");
        Cache::forget("t{$tid}_sol_rep_pend");

        return response()->json(['message' => 'Solicitud enviada correctamente.', 'solicitud' => $this->formatRepresentante($sol)], 201);
    }

    private function formatRepresentante(SolicitudRepresentante $s): array
    {
        $estados = SolicitudRepresentante::estados();
        $ec = $estados[$s->estado] ?? $estados['pendiente'];

        return [
            'id'             => $s->id,
            'tipo'           => $s->tipo,
            'tipo_label'     => SolicitudRepresentante::TIPOS[$s->tipo] ?? $s->tipo,
            'asunto'         => $s->asunto,
            'descripcion'    => $s->descripcion,
            'fecha_evento'   => $s->fecha_evento?->toDateString(),
            'estudiante_id'  => $s->estudiante_id,
            'estudiante'     => $s->estudiante?->nombre_completo,
            'docente_id'     => $s->docente_id,
            'docente'        => $s->docente?->nombre_completo,
            'estado'         => $s->estado,
            'estado_label'   => $ec['label'],
            'estado_color'   => $ec['color'],
            'respuesta'      => $s->respuesta,
            'respondido_en'  => $s->respondido_en?->toDateTimeString(),
            'creado_en'      => $s->created_at->toDateTimeString(),
            'creado_hace'    => $s->created_at->diffForHumans(),
        ];
    }
}
