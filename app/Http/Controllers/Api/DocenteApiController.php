<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\MaterialClase;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Observacion;
use App\Models\SchoolYear;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocenteApiController extends Controller
{
    /** GET /api/v1/docente/grupos
     * Lista las asignaciones del docente con alumnos por grupo.
     */
    public function grupos(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $sy = SchoolYear::actual();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->get()
            ->map(function ($a) use ($sy) {
                $alumnos = Matricula::where('grupo_id', $a->grupo_id)
                    ->where('estado', 'activa')
                    ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                    ->with('estudiante')
                    ->get()
                    ->map(fn($m) => [
                        'matricula_id' => $m->id,
                        'id'           => $m->id,
                        'estudiante_id'=> $m->estudiante_id,
                        'nombre'       => $m->estudiante
                            ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                            : '—',
                    ])
                    ->sortBy('nombre')->values();

                return [
                    'id'               => $a->id,
                    'asignacion_id'    => $a->id,
                    'asignatura'       => $a->asignatura?->nombre,
                    'color'            => $a->asignatura?->color ?? '#64748b',
                    'grupo'            => $a->grupo?->nombre_completo,
                    'grado'            => $a->grupo?->grado?->nombre,
                    'seccion'          => $a->grupo?->seccion?->nombre,
                    'grupo_id'         => $a->grupo_id,
                    'total_estudiantes'=> $alumnos->count(),
                    'alumnos'          => $alumnos,
                ];
            });

        return response()->json([
            'data'         => $asignaciones,
            'docente'      => "{$docente->apellidos}, {$docente->nombres}",
            'school_year'  => $sy?->nombre,
            'asignaciones' => $asignaciones,
        ]);
    }

    /** POST /api/v1/docente/asistencia
     * Registra/actualiza la asistencia de un grupo para una fecha.
     *
     * Body: {
     *   asignacion_id: int,
     *   fecha: "YYYY-MM-DD",
     *   registros: [{ matricula_id: int, estado: "presente|ausente|tardanza|justificado" }]
     * }
     */
    public function registrarAsistencia(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $data = $request->validate([
            'asignacion_id'           => ['required', 'integer', 'exists:asignaciones,id'],
            'fecha'                   => ['required', 'date', 'before_or_equal:today'],
            'registros'               => ['required', 'array', 'min:1'],
            'registros.*.matricula_id'=> ['required', 'integer', 'exists:matriculas,id'],
            'registros.*.estado'      => ['required', 'in:presente,ausente,tardanza,justificado'],
        ]);

        // Verificar que la asignacion pertenece al docente
        $asignacion = Asignacion::where('id', $data['asignacion_id'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'Asignación no encontrada o no autorizada.'], 403);
        }

        $guardados = 0;
        DB::transaction(function () use ($data, $asignacion, &$guardados) {
            foreach ($data['registros'] as $reg) {
                Asistencia::updateOrCreate(
                    [
                        'matricula_id'  => $reg['matricula_id'],
                        'asignacion_id' => $asignacion->id,
                        'fecha'         => $data['fecha'],
                    ],
                    ['estado' => $reg['estado']]
                );
                $guardados++;
            }
        });

        return response()->json([
            'ok'       => true,
            'guardados'=> $guardados,
            'fecha'    => $data['fecha'],
        ]);
    }

    /** GET /api/v1/docente/asistencia/{asignacion}?fecha=YYYY-MM-DD
     * Consulta el registro de asistencia de una asignación en una fecha.
     */
    public function consultarAsistencia(Request $request, int $asignacionId)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::where('id', $asignacionId)
            ->where('docente_id', $docente->id)
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'Asignación no encontrada.'], 404);
        }

        $fecha = $request->query('fecha', today()->toDateString());
        $sy    = SchoolYear::actual();

        $matriculas = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with('estudiante')
            ->get();

        $registros = Asistencia::where('asignacion_id', $asignacion->id)
            ->where('fecha', $fecha)
            ->pluck('estado', 'matricula_id');

        $lista = $matriculas->map(fn($m) => [
            'matricula_id' => $m->id,
            'nombre'       => $m->estudiante
                ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                : '—',
            'estado'       => $registros[$m->id] ?? null,
        ])->sortBy('nombre')->values();

        return response()->json([
            'asignacion_id' => $asignacion->id,
            'asignatura'    => $asignacion->asignatura?->nombre,
            'grupo'         => $asignacion->grupo?->nombre_completo,
            'fecha'         => $fecha,
            'registrado'    => $registros->isNotEmpty(),
            'alumnos'       => $lista,
        ]);
    }

    /** GET /api/v1/docente/calificaciones/{asignacion}
     * Notas de todos los estudiantes del grupo para la asignación del docente.
     */
    public function calificaciones(Request $request, int $asignacionId)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('id', $asignacionId)
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->first();

        if (! $asignacion) return response()->json(['message' => 'No autorizado.'], 403);

        $sy = SchoolYear::actual();

        $matriculas = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with([
                'estudiante',
                'calificaciones' => fn($q) => $q->where('asignacion_id', $asignacionId)->with('periodo'),
            ])
            ->get()
            ->map(fn($m) => [
                'matricula_id' => $m->id,
                'nombre'       => $m->estudiante
                    ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                    : '—',
                'notas'        => $m->calificaciones->map(fn($c) => [
                    'periodo'    => $c->periodo?->nombre ?? "P{$c->periodo_id}",
                    'nota_final' => $c->nota_final,
                    'indicador'  => $c->indicador,
                ])->sortBy('periodo')->values(),
            ])
            ->sortBy('nombre')->values();

        return response()->json([
            'asignacion_id' => $asignacion->id,
            'asignatura'    => $asignacion->asignatura?->nombre,
            'color'         => $asignacion->asignatura?->color ?? '#64748b',
            'grupo'         => $asignacion->grupo?->nombre_completo,
            'estudiantes'   => $matriculas,
        ]);
    }

    /** GET /api/v1/docente/observaciones?asignacion_id=X */
    public function observaciones(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('id', (int) $request->query('asignacion_id', 0))
            ->where('docente_id', $docente->id)
            ->first();
        if (! $asignacion) return response()->json(['message' => 'Asignación no encontrada.'], 404);

        $obs = Observacion::where('docente_id', $docente->id)
            ->where('asignacion_id', $asignacion->id)
            ->with('estudiante')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get()
            ->map(fn($o) => $this->formatObservacion($o));

        return response()->json([
            'asignacion_id' => $asignacion->id,
            'asignatura'    => $asignacion->asignatura?->nombre,
            'grupo'         => $asignacion->grupo?->nombre_completo,
            'tipos'         => collect(Observacion::TIPOS)->map(fn($t) => ['label' => $t['label'], 'color' => $t['color']]),
            'observaciones' => $obs,
        ]);
    }

    /** POST /api/v1/docente/observaciones */
    public function storeObservacion(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $data = $request->validate([
            'asignacion_id' => 'required|integer|exists:asignaciones,id',
            'estudiante_id' => 'required|integer|exists:estudiantes,id',
            'tipo'          => 'required|in:academica,conductual,positiva,general',
            'texto'         => 'required|string|max:1000',
            'privada'       => 'boolean',
        ]);

        $asignacion = Asignacion::where('id', $data['asignacion_id'])
            ->where('docente_id', $docente->id)->first();
        if (! $asignacion) return response()->json(['message' => 'Asignación no autorizada.'], 403);

        $obs = Observacion::create([
            'docente_id'    => $docente->id,
            'asignacion_id' => $data['asignacion_id'],
            'estudiante_id' => $data['estudiante_id'],
            'tipo'          => $data['tipo'],
            'texto'         => $data['texto'],
            'privada'       => $data['privada'] ?? false,
        ]);
        $obs->load('estudiante');

        return response()->json(['ok' => true, 'observacion' => $this->formatObservacion($obs)], 201);
    }

    /** GET /api/v1/docente/tareas?asignacion_id=X */
    public function tareasDocente(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('id', (int) $request->query('asignacion_id', 0))
            ->where('docente_id', $docente->id)
            ->first();
        if (! $asignacion) return response()->json(['message' => 'Asignación no encontrada.'], 404);

        $sy = SchoolYear::actual();
        $totalEst = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->count();

        $tareas = Tarea::where('asignacion_id', $asignacion->id)
            ->orderByDesc('fecha_limite')->get();

        $counts = EntregaTarea::whereIn('tarea_id', $tareas->pluck('id'))
            ->selectRaw('tarea_id, estado, count(*) as total')
            ->groupBy('tarea_id', 'estado')
            ->get()->groupBy('tarea_id');

        $items = $tareas->map(function ($t) use ($counts, $totalEst) {
            $ec         = $counts->get($t->id, collect());
            $entregadas = (int) ($ec->firstWhere('estado', 'entregada')?->total ?? 0);
            $revisadas  = (int) ($ec->firstWhere('estado', 'revisada')?->total ?? 0);
            return [
                'id'               => $t->id,
                'titulo'           => $t->titulo,
                'tipo'             => $t->tipo,
                'tipo_label'       => Tarea::TIPOS[$t->tipo]       ?? ucfirst($t->tipo),
                'tipo_color'       => Tarea::COLORES_TIPO[$t->tipo] ?? '#6b7280',
                'descripcion'      => $t->descripcion,
                'fecha_limite'     => $t->fecha_limite?->toDateString(),
                'puntos_valor'     => $t->puntos_valor,
                'activo'           => $t->activo,
                'esta_vencida'     => $t->esta_vencida,
                'total_estudiantes'=> $totalEst,
                'pendientes'       => max(0, $totalEst - $entregadas - $revisadas),
                'entregadas'       => $entregadas,
                'revisadas'        => $revisadas,
            ];
        });

        return response()->json([
            'asignacion_id'    => $asignacion->id,
            'asignatura'       => $asignacion->asignatura?->nombre,
            'grupo'            => $asignacion->grupo?->nombre_completo,
            'total_estudiantes'=> $totalEst,
            'tipos'            => Tarea::TIPOS,
            'tareas'           => $items,
        ]);
    }

    /** POST /api/v1/docente/tareas */
    public function storeTarea(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $data = $request->validate([
            'asignacion_id' => 'required|integer|exists:asignaciones,id',
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:tarea,actividad,proyecto,evaluacion',
            'fecha_limite'  => 'required|date',
            'descripcion'   => 'nullable|string|max:5000',
            'puntos_valor'  => 'nullable|integer|min:1|max:100',
        ]);

        $asignacion = Asignacion::where('id', $data['asignacion_id'])
            ->where('docente_id', $docente->id)->first();
        if (! $asignacion) return response()->json(['message' => 'Asignación no autorizada.'], 403);

        $tarea = Tarea::create($data);

        try {
            $sy   = SchoolYear::actual();
            $mats = Matricula::where('grupo_id', $asignacion->grupo_id)
                ->where('estado', 'activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->with('estudiante.user')->get();

            foreach ($mats as $m) {
                if ($m->estudiante?->user_id) {
                    Notificacion::create([
                        'user_id' => $m->estudiante->user_id,
                        'tipo'    => 'info',
                        'titulo'  => 'Nueva tarea: ' . $tarea->titulo,
                        'cuerpo'  => ($asignacion->asignatura?->nombre ?? '') . ' · vence ' . $tarea->fecha_limite?->toDateString(),
                        'leida'   => false,
                    ]);
                }
            }
        } catch (\Throwable) {}

        return response()->json([
            'ok'    => true,
            'tarea' => [
                'id'          => $tarea->id,
                'titulo'      => $tarea->titulo,
                'tipo'        => $tarea->tipo,
                'tipo_label'  => Tarea::TIPOS[$tarea->tipo]       ?? ucfirst($tarea->tipo),
                'tipo_color'  => Tarea::COLORES_TIPO[$tarea->tipo] ?? '#6b7280',
                'fecha_limite'=> $tarea->fecha_limite?->toDateString(),
                'puntos_valor'=> $tarea->puntos_valor,
            ],
        ], 201);
    }

    /** GET /api/v1/docente/tareas/{tarea}/entregas */
    public function entregasTarea(Request $request, Tarea $tarea)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::where('id', $tarea->asignacion_id)
            ->where('docente_id', $docente->id)->first();
        if (! $asignacion) return response()->json(['message' => 'No autorizado.'], 403);

        $sy       = SchoolYear::actual();
        $mats     = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->get();

        $entMap = EntregaTarea::where('tarea_id', $tarea->id)
            ->get()->keyBy('estudiante_id');

        $lista = $mats->map(function ($m) use ($entMap) {
            $e = $entMap->get($m->estudiante_id);
            return [
                'estudiante_id' => $m->estudiante_id,
                'estudiante'    => $m->estudiante
                    ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                    : '—',
                'estado'        => $e?->estado ?? 'pendiente',
                'estado_label'  => EntregaTarea::ESTADOS[$e?->estado ?? 'pendiente']       ?? 'Pendiente',
                'estado_color'  => EntregaTarea::COLORES_ESTADO[$e?->estado ?? 'pendiente'] ?? '#f59e0b',
                'calificacion'  => $e?->calificacion,
                'notas_docente' => $e?->notas_docente,
                'fecha_entrega' => $e?->fecha_entrega?->toDateTimeString(),
            ];
        })->sortBy('estudiante')->values();

        return response()->json([
            'tarea_id'    => $tarea->id,
            'titulo'      => $tarea->titulo,
            'tipo_label'  => Tarea::TIPOS[$tarea->tipo] ?? $tarea->tipo,
            'tipo_color'  => Tarea::COLORES_TIPO[$tarea->tipo] ?? '#6b7280',
            'fecha_limite'=> $tarea->fecha_limite?->toDateString(),
            'puntos_valor'=> $tarea->puntos_valor,
            'estados'     => EntregaTarea::ESTADOS,
            'entregas'    => $lista,
        ]);
    }

    /** PATCH /api/v1/docente/tareas/{tarea}/calificar */
    public function calificarEntrega(Request $request, Tarea $tarea)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::where('id', $tarea->asignacion_id)
            ->where('docente_id', $docente->id)->first();
        if (! $asignacion) return response()->json(['message' => 'No autorizado.'], 403);

        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'estado'        => 'required|in:pendiente,entregada,revisada',
            'calificacion'  => 'nullable|numeric|min:0|max:100',
            'notas_docente' => 'nullable|string|max:1000',
        ]);

        $entrega = EntregaTarea::updateOrCreate(
            ['tarea_id' => $tarea->id, 'estudiante_id' => $data['estudiante_id']],
            [
                'estado'        => $data['estado'],
                'calificacion'  => $data['calificacion']  ?? null,
                'notas_docente' => $data['notas_docente'] ?? null,
                'fecha_entrega' => $data['estado'] !== 'pendiente' ? now() : null,
            ]
        );

        return response()->json([
            'ok'          => true,
            'estado'      => $entrega->estado,
            'estado_label'=> EntregaTarea::ESTADOS[$entrega->estado]       ?? $entrega->estado,
            'estado_color'=> EntregaTarea::COLORES_ESTADO[$entrega->estado] ?? '#6b7280',
            'calificacion'=> $entrega->calificacion,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatObservacion(Observacion $o): array
    {
        return [
            'id'           => $o->id,
            'tipo'         => $o->tipo,
            'tipo_label'   => Observacion::TIPOS[$o->tipo]['label'] ?? $o->tipo,
            'tipo_color'   => Observacion::TIPOS[$o->tipo]['color'] ?? '#6b7280',
            'texto'        => $o->texto,
            'privada'      => $o->privada,
            'estudiante_id'=> $o->estudiante_id,
            'estudiante'   => $o->estudiante
                ? "{$o->estudiante->apellidos}, {$o->estudiante->nombres}"
                : '—',
            'creado_en'    => $o->created_at?->toDateTimeString(),
            'creado_hace'  => $o->created_at?->diffForHumans(),
        ];
    }

    private function docenteOFail(Request $request): Docente|\Illuminate\Http\JsonResponse
    {
        if (! $request->user()->hasRole('Docente')) {
            return response()->json(['message' => 'Solo para docentes.'], 403);
        }

        $docente = Docente::where('user_id', $request->user()->id)->first();
        if (! $docente) {
            return response()->json(['message' => 'Perfil de docente no encontrado.'], 404);
        }

        return $docente;
    }
}
