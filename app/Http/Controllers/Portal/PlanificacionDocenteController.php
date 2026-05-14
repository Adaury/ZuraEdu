<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Planificacion;
use App\Models\PlanificacionActividad;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\PlanificacionRaItem;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PlanificacionDocenteController extends Controller
{
    use HasDocenteContext;

    private function schoolYear(): SchoolYear
    {
        return SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
    }

    private function verificarAsignacion(Asignacion $asignacion, Docente $docente): void
    {
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($asignacion->area !== 'tecnica') abort(403, 'Solo disponible para área técnica.');
    }

    // ── Listado de planificaciones de una asignación ──────────────────────

    public function index(Asignacion $asignacion)
    {
        $docente    = $this->getDocente();
        $schoolYear = $this->schoolYear();
        $this->verificarAsignacion($asignacion, $docente);

        $planificaciones = Planificacion::where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->latest()
            ->get();

        return view('portal.docente.planificacion.index', compact(
            'asignacion', 'planificaciones', 'schoolYear'
        ));
    }

    // ── Ver planificación ─────────────────────────────────────────────────

    public function show(Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);

        $planificacion->load(['raItems', 'actividades', 'asignacion.docente', 'schoolYear']);

        return view('portal.docente.planificacion.show', compact('asignacion', 'planificacion'));
    }

    // ── Crear planificación por RA ─────────────────────────────────────────

    public function createRa(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        $asignacion->load(['asignatura', 'grupo']);

        return view('portal.docente.planificacion.crear_ra', compact('asignacion'));
    }

    public function storeRa(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);

        $data = $request->validate([
            'familia_profesional' => 'nullable|string|max:255',
            'denominacion'        => 'nullable|string|max:255',
            'modulo_nombre'       => 'nullable|string|max:255',
            'mf_codigo'           => 'nullable|string|max:50',
            'uc_codigo'           => 'nullable|string',
            'sesion'              => 'nullable|string|max:100',
            'nivel'               => 'nullable|string|max:10',
            'horas'               => 'nullable|numeric|min:0',
            'fecha_inicio'        => 'nullable|date',
            'fecha_fin'           => 'nullable|date',
            'ra'                  => 'nullable|array',
            'ra.*.ra_codigo'               => 'nullable|string|max:30',
            'ra.*.ra_descripcion'          => 'nullable|string',
            'ra.*.nivel_taxonomico'        => 'nullable|string|max:100',
            'ra.*.elementos_capacidad'     => 'nullable|string',
            'ra.*.fechas_desde'            => 'nullable|array',
            'ra.*.fechas_hasta'            => 'nullable|array',
            'ra.*.actividades'             => 'nullable|string',
            'ra.*.instrumentos_evaluacion' => 'nullable|string',
            'ra.*.contenidos'              => 'nullable|string',
        ]);

        $schoolYear = $this->schoolYear();

        $plan = Planificacion::create([
            'asignacion_id'       => $asignacion->id,
            'school_year_id'      => $schoolYear->id,
            'tipo'                => 'ra',
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => false,
            'creado_por'          => auth()->id(),
        ]);

        foreach ($data['ra'] ?? [] as $orden => $item) {
            if (empty($item['ra_descripcion']) && empty($item['ra_codigo'])) continue;

            $fechas = [];
            foreach ($item['fechas_desde'] ?? [] as $i => $d) {
                if ($d || ($item['fechas_hasta'][$i] ?? null)) {
                    $fechas[] = ['desde' => $d, 'hasta' => $item['fechas_hasta'][$i] ?? null];
                }
            }
            $elementos = [];
            foreach (array_filter(explode("\n", $item['elementos_capacidad'] ?? '')) as $ec) {
                $ec = trim($ec);
                if ($ec) $elementos[] = ['descripcion' => $ec];
            }
            PlanificacionRaItem::create([
                'planificacion_id'        => $plan->id,
                'orden'                   => $orden + 1,
                'ra_codigo'               => $item['ra_codigo'] ?? null,
                'ra_descripcion'          => $item['ra_descripcion'] ?? null,
                'nivel_taxonomico'        => $item['nivel_taxonomico'] ?? null,
                'elementos_capacidad'     => $elementos ?: null,
                'fechas'                  => $fechas ?: null,
                'actividades'             => $item['actividades'] ?? null,
                'instrumentos_evaluacion' => $item['instrumentos_evaluacion'] ?? null,
                'contenidos'              => $item['contenidos'] ?? null,
            ]);
        }

        return redirect()->route('portal.docente.planificacion.show', [$asignacion, $plan])
            ->with('success', 'Planificación por RA guardada correctamente.');
    }

    // ── Crear planificación por Actividad ─────────────────────────────────

    public function createActividad(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        $asignacion->load(['asignatura', 'grupo']);

        return view('portal.docente.planificacion.crear_actividad', compact('asignacion'));
    }

    public function storeActividad(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);

        $data = $request->validate([
            'familia_profesional'    => 'nullable|string|max:255',
            'denominacion'           => 'nullable|string|max:255',
            'modulo_nombre'          => 'nullable|string|max:255',
            'mf_codigo'              => 'nullable|string|max:50',
            'uc_codigo'              => 'nullable|string',
            'sesion'                 => 'nullable|string|max:100',
            'nivel'                  => 'nullable|string|max:10',
            'horas'                  => 'nullable|numeric|min:0',
            'fecha_inicio'           => 'nullable|date',
            'fecha_fin'              => 'nullable|date',
            'ra_codigo'              => 'nullable|string|max:30',
            'ra_descripcion'         => 'nullable|string',
            'actividad_numero'       => 'nullable|integer|min:1',
            'objetivo'               => 'nullable|string',
            'act_inicio'             => 'nullable|string',
            'act_desarrollo'         => 'nullable|string',
            'act_cierre'             => 'nullable|string',
            'estrategias'            => 'nullable|string',
            'recursos'               => 'nullable|string',
            'instrumentos_evaluacion'=> 'nullable|string',
        ]);

        $schoolYear = $this->schoolYear();

        $plan = Planificacion::create([
            'asignacion_id'       => $asignacion->id,
            'school_year_id'      => $schoolYear->id,
            'tipo'                => 'actividad',
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
            'publicado'           => false,
            'creado_por'          => auth()->id(),
        ]);

        PlanificacionActividad::create([
            'planificacion_id'        => $plan->id,
            'ra_codigo'               => $data['ra_codigo'] ?? null,
            'ra_descripcion'          => $data['ra_descripcion'] ?? null,
            'actividad_numero'        => $data['actividad_numero'] ?? null,
            'objetivo'                => $data['objetivo'] ?? null,
            'act_inicio'              => $data['act_inicio'] ?? null,
            'act_desarrollo'          => $data['act_desarrollo'] ?? null,
            'act_cierre'              => $data['act_cierre'] ?? null,
            'estrategias'             => $data['estrategias'] ?? null,
            'recursos'                => $data['recursos'] ?? null,
            'instrumentos_evaluacion' => $data['instrumentos_evaluacion'] ?? null,
        ]);

        return redirect()->route('portal.docente.planificacion.show', [$asignacion, $plan])
            ->with('success', 'Planificación por Actividad guardada correctamente.');
    }

    // ── Editar planificación ──────────────────────────────────────────────

    public function edit(Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);

        $planificacion->load(['raItems', 'actividades']);
        $asignacion->load(['asignatura', 'grupo']);

        $view = $planificacion->tipo === 'ra'
            ? 'portal.docente.planificacion.crear_ra'
            : 'portal.docente.planificacion.crear_actividad';

        return view($view, compact('asignacion', 'planificacion'));
    }

    public function update(Request $request, Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);

        if ($planificacion->tipo === 'ra') {
            return $this->updateRa($request, $asignacion, $planificacion);
        }
        return $this->updateActividad($request, $asignacion, $planificacion);
    }

    private function updateRa(Request $request, Asignacion $asignacion, Planificacion $planificacion)
    {
        $data = $request->validate([
            'familia_profesional' => 'nullable|string|max:255',
            'denominacion'        => 'nullable|string|max:255',
            'modulo_nombre'       => 'nullable|string|max:255',
            'mf_codigo'           => 'nullable|string|max:50',
            'uc_codigo'           => 'nullable|string',
            'sesion'              => 'nullable|string|max:100',
            'nivel'               => 'nullable|string|max:10',
            'horas'               => 'nullable|numeric|min:0',
            'fecha_inicio'        => 'nullable|date',
            'fecha_fin'           => 'nullable|date',
            'ra'                  => 'nullable|array',
            'ra.*.ra_codigo'               => 'nullable|string|max:30',
            'ra.*.ra_descripcion'          => 'nullable|string',
            'ra.*.nivel_taxonomico'        => 'nullable|string|max:100',
            'ra.*.elementos_capacidad'     => 'nullable|string',
            'ra.*.fechas_desde'            => 'nullable|array',
            'ra.*.fechas_hasta'            => 'nullable|array',
            'ra.*.actividades'             => 'nullable|string',
            'ra.*.instrumentos_evaluacion' => 'nullable|string',
            'ra.*.contenidos'              => 'nullable|string',
        ]);

        $planificacion->update([
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
        ]);

        $planificacion->raItems()->delete();

        foreach ($data['ra'] ?? [] as $orden => $item) {
            if (empty($item['ra_descripcion']) && empty($item['ra_codigo'])) continue;
            $fechas = [];
            foreach ($item['fechas_desde'] ?? [] as $i => $d) {
                if ($d || ($item['fechas_hasta'][$i] ?? null)) {
                    $fechas[] = ['desde' => $d, 'hasta' => $item['fechas_hasta'][$i] ?? null];
                }
            }
            $elementos = [];
            foreach (array_filter(explode("\n", $item['elementos_capacidad'] ?? '')) as $ec) {
                $ec = trim($ec);
                if ($ec) $elementos[] = ['descripcion' => $ec];
            }
            PlanificacionRaItem::create([
                'planificacion_id'        => $planificacion->id,
                'orden'                   => $orden + 1,
                'ra_codigo'               => $item['ra_codigo'] ?? null,
                'ra_descripcion'          => $item['ra_descripcion'] ?? null,
                'nivel_taxonomico'        => $item['nivel_taxonomico'] ?? null,
                'elementos_capacidad'     => $elementos ?: null,
                'fechas'                  => $fechas ?: null,
                'actividades'             => $item['actividades'] ?? null,
                'instrumentos_evaluacion' => $item['instrumentos_evaluacion'] ?? null,
                'contenidos'              => $item['contenidos'] ?? null,
            ]);
        }

        return redirect()->route('portal.docente.planificacion.show', [$asignacion, $planificacion])
            ->with('success', 'Planificación actualizada correctamente.');
    }

    private function updateActividad(Request $request, Asignacion $asignacion, Planificacion $planificacion)
    {
        $data = $request->validate([
            'familia_profesional'    => 'nullable|string|max:255',
            'denominacion'           => 'nullable|string|max:255',
            'modulo_nombre'          => 'nullable|string|max:255',
            'mf_codigo'              => 'nullable|string|max:50',
            'uc_codigo'              => 'nullable|string',
            'sesion'                 => 'nullable|string|max:100',
            'nivel'                  => 'nullable|string|max:10',
            'horas'                  => 'nullable|numeric|min:0',
            'fecha_inicio'           => 'nullable|date',
            'fecha_fin'              => 'nullable|date',
            'ra_codigo'              => 'nullable|string|max:30',
            'ra_descripcion'         => 'nullable|string',
            'actividad_numero'       => 'nullable|integer|min:1',
            'objetivo'               => 'nullable|string',
            'act_inicio'             => 'nullable|string',
            'act_desarrollo'         => 'nullable|string',
            'act_cierre'             => 'nullable|string',
            'estrategias'            => 'nullable|string',
            'recursos'               => 'nullable|string',
            'instrumentos_evaluacion'=> 'nullable|string',
        ]);

        $planificacion->update([
            'familia_profesional' => $data['familia_profesional'] ?? null,
            'denominacion'        => $data['denominacion'] ?? null,
            'modulo_nombre'       => $data['modulo_nombre'] ?? null,
            'mf_codigo'           => $data['mf_codigo'] ?? null,
            'uc_codigo'           => $data['uc_codigo'] ?? null,
            'sesion'              => $data['sesion'] ?? null,
            'nivel'               => $data['nivel'] ?? null,
            'horas'               => $data['horas'] ?? null,
            'fecha_inicio'        => $data['fecha_inicio'] ?? null,
            'fecha_fin'           => $data['fecha_fin'] ?? null,
        ]);

        $act = $planificacion->actividades()->first()
            ?? new PlanificacionActividad(['planificacion_id' => $planificacion->id]);
        $act->fill([
            'ra_codigo'               => $data['ra_codigo'] ?? null,
            'ra_descripcion'          => $data['ra_descripcion'] ?? null,
            'actividad_numero'        => $data['actividad_numero'] ?? null,
            'objetivo'                => $data['objetivo'] ?? null,
            'act_inicio'              => $data['act_inicio'] ?? null,
            'act_desarrollo'          => $data['act_desarrollo'] ?? null,
            'act_cierre'              => $data['act_cierre'] ?? null,
            'estrategias'             => $data['estrategias'] ?? null,
            'recursos'                => $data['recursos'] ?? null,
            'instrumentos_evaluacion' => $data['instrumentos_evaluacion'] ?? null,
        ])->save();

        return redirect()->route('portal.docente.planificacion.show', [$asignacion, $planificacion])
            ->with('success', 'Planificación actualizada correctamente.');
    }

    // ── Toggle publicado ──────────────────────────────────────────────────

    public function togglePublicado(Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);

        $eraInactivo = !$planificacion->publicado;
        $planificacion->update(['publicado' => !$planificacion->publicado]);

        // Si acaba de publicarse, notificar a los estudiantes del grupo
        if ($eraInactivo && $planificacion->publicado) {
            $this->notificarEstudiantes($planificacion, $asignacion);
        }

        return back()->with('success', $planificacion->publicado ? 'Planificación publicada.' : 'Planificación guardada como borrador.');
    }

    private function notificarEstudiantes(Planificacion $planificacion, Asignacion $asignacion): void
    {
        try {
            $schoolYear = $this->schoolYear();
            $matriculas = Matricula::with('estudiante.user')
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('school_year_id', $schoolYear->id)
                ->where('estado', 'activa')
                ->get();

            $userIds = $matriculas
                ->filter(fn($m) => $m->estudiante?->user_id)
                ->pluck('estudiante.user_id')
                ->unique()
                ->values()
                ->toArray();

            if (!empty($userIds)) {
                $modulo = $planificacion->modulo_nombre ?? $asignacion->asignatura?->nombre ?? 'planificación';
                Notificacion::enviarA(
                    $userIds,
                    'planificacion',
                    'Nueva planificación publicada',
                    "Tu docente publicó la planificación: {$modulo} ({$asignacion->asignatura?->nombre})."
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error al notificar planificacion: ' . $e->getMessage());
        }
    }

    // ── Eliminar ──────────────────────────────────────────────────────────

    public function destroy(Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);

        $planificacion->delete();
        return redirect()->route('portal.docente.planificacion.index', $asignacion)
            ->with('success', 'Planificación eliminada.');
    }
}
