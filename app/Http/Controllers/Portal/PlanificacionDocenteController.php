<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Traits\NormalizesFileEncoding;
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
    use NormalizesFileEncoding;

    /**
     * Columnas del CSV de planificación por RA. Los campos de la
     * planificación (familia_profesional..fecha_fin) van repetidos en cada
     * fila (formato "ancho", una fila = un RA) porque un archivo crea UNA
     * sola Planificacion con N items -- se toman del primer valor no vacío
     * encontrado entre todas las filas.
     */
    private const COLUMNAS_RA = [
        'familia_profesional', 'denominacion', 'modulo_nombre', 'mf_codigo', 'uc_codigo',
        'sesion', 'nivel', 'horas', 'fecha_inicio', 'fecha_fin',
        'ra_codigo', 'ra_descripcion', 'nivel_taxonomico', 'elementos_capacidad',
        'fecha_ra_desde', 'fecha_ra_hasta', 'actividades', 'instrumentos_evaluacion', 'contenidos',
    ];

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

    // ── Plantilla / Importar / Exportar (por RA) ───────────────────────────
    // Solo para el tipo "ra": tiene una lista de items naturalmente tabular
    // (como las unidades de Planificación Anual). El tipo "actividad" es un
    // único bloque por plan (no una lista), así que no aplica un CSV masivo.

    public function plantilla(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);

        $comun = ['Mecánica Automotriz', 'Mantenimiento de Motores', 'Motores I', 'MF01', 'UC0001', '1ra sesión', 'II', 40, '2025-08-25', '2025-12-19'];

        $filas = [
            array_merge($comun, ['RA1', 'Diagnostica fallas del sistema de encendido.', 'Aplicar', 'Identifica componentes;Interpreta manuales técnicos', '2025-08-25', '2025-09-19', 'Práctica de taller guiada.', 'Lista de cotejo', 'Sistema de encendido convencional y electrónico.']),
            array_merge($comun, ['RA2', 'Repara fallas del sistema de encendido.', 'Aplicar', 'Ejecuta reparación siguiendo protocolo', '2025-09-22', '2025-10-17', 'Práctica de taller supervisada.', 'Rúbrica de desempeño', 'Procedimientos de reparación y normas de seguridad.']),
        ];

        return $this->generarCsvResponse(self::COLUMNAS_RA, $filas, 'plantilla_planificacion_ra');
    }

    public function exportarRa(Asignacion $asignacion, Planificacion $planificacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);
        if ($planificacion->asignacion_id !== $asignacion->id) abort(404);
        abort_unless($planificacion->tipo === 'ra', 422, 'Solo las planificaciones por RA se pueden exportar.');

        $comun = [
            $planificacion->familia_profesional, $planificacion->denominacion, $planificacion->modulo_nombre,
            $planificacion->mf_codigo, $planificacion->uc_codigo, $planificacion->sesion, $planificacion->nivel,
            $planificacion->horas, optional($planificacion->fecha_inicio)->format('Y-m-d'), optional($planificacion->fecha_fin)->format('Y-m-d'),
        ];

        $filas = $planificacion->raItems->map(function (PlanificacionRaItem $item) use ($comun) {
            $fechas = $item->fechas[0] ?? [];
            $elementos = collect($item->elementos_capacidad ?? [])->pluck('descripcion')->implode(';');
            return array_merge($comun, [
                $item->ra_codigo, $item->ra_descripcion, $item->nivel_taxonomico, $elementos,
                $fechas['desde'] ?? '', $fechas['hasta'] ?? '',
                $item->actividades, $item->instrumentos_evaluacion, $item->contenidos,
            ]);
        })->all();

        $slug = \Illuminate\Support\Str::slug($planificacion->modulo_nombre ?: $planificacion->denominacion ?: 'planificacion-ra');
        return $this->generarCsvResponse(self::COLUMNAS_RA, $filas, "planificacion_ra_{$slug}");
    }

    /**
     * Sube un CSV/Excel con el formato de plantilla() y crea UNA
     * Planificacion nueva (tipo=ra) con todos sus PlanificacionRaItem --
     * equivalente a llenar storeRa() a mano fila por fila, pero de una vez.
     */
    public function importarRa(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        $this->verificarAsignacion($asignacion, $docente);

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $rows = $this->leerArchivoImportGenerico($request->file('archivo'));
        if (empty($rows)) {
            return back()->with('error', 'El archivo no tiene filas para importar.');
        }

        // Metadata del plan: primer valor no vacío encontrado entre todas las filas.
        $camposComunes = ['familia_profesional', 'denominacion', 'modulo_nombre', 'mf_codigo', 'uc_codigo', 'sesion', 'nivel', 'horas', 'fecha_inicio', 'fecha_fin'];
        $meta = [];
        foreach ($camposComunes as $campo) {
            foreach ($rows as $row) {
                if (trim($row[$campo] ?? '') !== '') { $meta[$campo] = trim($row[$campo]); break; }
            }
        }

        $schoolYear = $this->schoolYear();

        $plan = Planificacion::create([
            'asignacion_id'       => $asignacion->id,
            'school_year_id'      => $schoolYear->id,
            'tipo'                => 'ra',
            'familia_profesional' => $meta['familia_profesional'] ?? null,
            'denominacion'        => $meta['denominacion'] ?? null,
            'modulo_nombre'       => $meta['modulo_nombre'] ?? null,
            'mf_codigo'           => $meta['mf_codigo'] ?? null,
            'uc_codigo'           => $meta['uc_codigo'] ?? null,
            'sesion'              => $meta['sesion'] ?? null,
            'nivel'               => $meta['nivel'] ?? null,
            'horas'               => $meta['horas'] ?? null,
            'fecha_inicio'        => $meta['fecha_inicio'] ?? null,
            'fecha_fin'           => $meta['fecha_fin'] ?? null,
            'publicado'           => false,
            'creado_por'          => auth()->id(),
        ]);

        $orden = 0;
        foreach ($rows as $row) {
            if (trim($row['ra_descripcion'] ?? '') === '' && trim($row['ra_codigo'] ?? '') === '') continue;

            $elementos = [];
            foreach (array_filter(explode(';', $row['elementos_capacidad'] ?? '')) as $ec) {
                $ec = trim($ec);
                if ($ec !== '') $elementos[] = ['descripcion' => $ec];
            }
            $fechas = [];
            if (trim($row['fecha_ra_desde'] ?? '') !== '' || trim($row['fecha_ra_hasta'] ?? '') !== '') {
                $fechas[] = ['desde' => $row['fecha_ra_desde'] ?? null, 'hasta' => $row['fecha_ra_hasta'] ?? null];
            }

            PlanificacionRaItem::create([
                'planificacion_id'        => $plan->id,
                'orden'                   => ++$orden,
                'ra_codigo'               => $row['ra_codigo'] ?: null,
                'ra_descripcion'          => $row['ra_descripcion'] ?: null,
                'nivel_taxonomico'        => $row['nivel_taxonomico'] ?: null,
                'elementos_capacidad'     => $elementos ?: null,
                'fechas'                  => $fechas ?: null,
                'actividades'             => $row['actividades'] ?: null,
                'instrumentos_evaluacion' => $row['instrumentos_evaluacion'] ?: null,
                'contenidos'              => $row['contenidos'] ?: null,
            ]);
        }

        if ($orden === 0) {
            $plan->delete();
            return back()->with('error', 'Ninguna fila tenía código o descripción de RA -- no se creó la planificación.');
        }

        return redirect()->route('portal.docente.planificacion.show', [$asignacion, $plan])
            ->with('success', "Planificación creada con {$orden} resultado(s) de aprendizaje importado(s).");
    }

    private function generarCsvResponse(array $headers, array $rows, string $nombre): \Illuminate\Http\Response
    {
        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '.csv"',
        ]);
    }

    private function leerArchivoImportGenerico($archivo): array
    {
        $ext  = strtolower($archivo->getClientOriginalExtension());
        $rows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $sheet  = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname())
                        ->getActiveSheet()->toArray(null, true, true, false);
            $header = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), ''));
            }
            return $rows;
        }

        $raw    = $this->normalizeToUtf8(file_get_contents($archivo->getPathname()));
        $lines  = array_values(array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")))));
        $delim  = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $header = array_map('strtolower', array_map('trim', str_getcsv($lines[0] ?? '', $delim)));

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') continue;
            $cols   = str_getcsv($line, $delim);
            $rows[] = array_combine($header, array_pad($cols, count($header), ''));
        }

        return $rows;
    }
}
