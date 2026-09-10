<?php

namespace App\Traits;

use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\PlanifUnidad;
use Illuminate\Support\Collection;

/**
 * Bloque "Hoy" del docente (horario del día, asistencia pendiente, entregas
 * de ZuraClass sin revisar, planificación vigente) -- extraído de
 * PortalDocenteController para que DashboardApiController (app móvil) lo
 * reutilice sin duplicar la lógica. Comportamiento idéntico al de la web,
 * solo se movió de sitio.
 */
trait HasDocenteHoy
{
    /**
     * Mapea el día actual a las claves usadas por HorarioDetalle
     * (lunes..viernes/sabado, sin tilde). Si hoy no es un día configurado
     * en horario_dias, simplemente no habrá entradas en $gridHorario para
     * esa clave — no requiere manejo especial aquí.
     */
    protected function diaHoyClave(): string
    {
        $mapa = [0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
                 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];

        return $mapa[now()->dayOfWeek] ?? 'lunes';
    }

    protected function cargarHorario(Docente $docente, $schoolYear): array
    {
        $grid    = [];
        $franjas = collect();
        $horario = null;
        $dias    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        if ($schoolYear) {
            $horario = Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')->latest()->first();

            if ($horario) {
                $detalles = HorarioDetalle::with(['asignacion.asignatura', 'asignacion.grupo.grado', 'asignacion.grupo.seccion', 'franja', 'aula'])
                    ->where('horario_id', $horario->id)
                    ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docente->id))
                    ->get();

                $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();

                foreach ($detalles as $d) {
                    $grid[$d->franja_id][$d->dia] = $d;
                }

                $dias = ConfigInstitucional::get('horario_dias', $dias);
            }
        }

        return [$grid, $franjas, $horario, $dias];
    }

    /**
     * Cuenta cuántas de las asignaciones dadas (ya filtradas a las de HOY)
     * no tienen la asistencia completa para la fecha indicada. Misma
     * definición de "completo" que asistenciaRapida() (marcados >= total y
     * total > 0) pero en 2 consultas agregadas en vez de N.
     */
    protected function contarAsistenciaPendiente(Collection $asignacionesHoy, string $fecha, ?int $syId): int
    {
        if ($asignacionesHoy->isEmpty()) return 0;

        $grupoIds = $asignacionesHoy->pluck('grupo_id')->unique();
        $asigIds  = $asignacionesHoy->pluck('id');

        $totalPorGrupo = Matricula::whereIn('grupo_id', $grupoIds)
            ->where('estado', 'activa')
            ->when($syId, fn($q) => $q->where('school_year_id', $syId))
            ->selectRaw('grupo_id, count(*) as total')
            ->groupBy('grupo_id')
            ->pluck('total', 'grupo_id');

        $marcadosPorAsignacion = Asistencia::whereIn('asignacion_id', $asigIds)
            ->whereDate('fecha', $fecha)
            ->selectRaw('asignacion_id, count(*) as marcados')
            ->groupBy('asignacion_id')
            ->pluck('marcados', 'asignacion_id');

        $pendientes = 0;
        foreach ($asignacionesHoy as $asig) {
            $total    = (int) ($totalPorGrupo[$asig->grupo_id] ?? 0);
            $marcados = (int) ($marcadosPorAsignacion[$asig->id] ?? 0);
            if ($total > 0 && $marcados < $total) $pendientes++;
        }

        return $pendientes;
    }

    /**
     * Cuenta entregas de ZuraClass en estado "entregada" (ya enviadas por
     * el estudiante, pendientes de revisión del docente) para cualquiera
     * de las asignaciones dadas.
     */
    protected function contarEntregasPendientes(Collection $asignacionIds): int
    {
        if ($asignacionIds->isEmpty()) return 0;

        return EntregaTarea::where('estado', 'entregada')
            ->whereHas('tarea', fn($q) => $q->whereIn('asignacion_id', $asignacionIds))
            ->count();
    }

    /**
     * PlanifUnidad vigente hoy (fecha_inicio <= hoy <= fecha_fin) por
     * asignación, para las asignaciones de HOY únicamente. No se infiere
     * ninguna fecha para PlanClase ni Planificacion técnica — si no hay una
     * PlanifUnidad vigente, la vista debe mostrar "Sin planificación
     * registrada" (decisión explícita, ver ZURAPLAN_ARCHITECTURE.md).
     */
    protected function planificacionDeHoy(Collection $asignacionIds): Collection
    {
        if ($asignacionIds->isEmpty()) return collect();

        return PlanifUnidad::whereHas('planifAnual', fn($q) => $q->whereIn('asignacion_id', $asignacionIds))
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->with('planifAnual')
            ->get()
            ->keyBy(fn($u) => $u->planifAnual->asignacion_id);
    }

    /**
     * Arma el bloque "Hoy" completo (horario/próxima clase, asistencia
     * pendiente, entregas pendientes, planificación vigente) a partir de
     * las asignaciones activas del docente ya cargadas por el llamador —
     * único punto usado por DashboardApiController (PortalDocenteController
     * arma cada pieza inline porque también las usa por separado en la vista).
     */
    protected function datosHoyDocente(Docente $docente, $schoolYear, Collection $asignaciones): array
    {
        $syId = $schoolYear?->id ?? 0;
        [$gridHorario, $franjasHorario] = $this->cargarHorario($docente, $schoolYear);

        $diaHoy   = $this->diaHoyClave();
        $fechaHoy = today()->toDateString();

        $horarioHoy = collect();
        foreach ($franjasHorario as $franja) {
            $detalle = $gridHorario[$franja->id][$diaHoy] ?? null;
            if ($detalle) {
                $horarioHoy->push(['franja' => $franja, 'detalle' => $detalle]);
            }
        }

        $horaActual   = now()->format('H:i:s');
        $proximaClase = $horarioHoy->first(
            fn($item) => \Carbon\Carbon::parse($item['franja']->hora_inicio)->format('H:i:s') > $horaActual
        );

        $asignacionesHoy = $horarioHoy->map(fn($item) => $item['detalle']->asignacion)->filter()->unique('id')->values();

        $asistenciaPendienteHoy = $this->contarAsistenciaPendiente($asignacionesHoy, $fechaHoy, $syId);
        $entregasPendientes     = $this->contarEntregasPendientes($asignaciones->pluck('id'));
        $planificacionHoy       = $this->planificacionDeHoy($asignacionesHoy->pluck('id'));

        return [
            'horario_hoy' => $horarioHoy->map(fn($item) => [
                'hora_inicio' => \Carbon\Carbon::parse($item['franja']->hora_inicio)->format('H:i'),
                'hora_fin'    => \Carbon\Carbon::parse($item['franja']->hora_fin)->format('H:i'),
                'asignatura'  => $item['detalle']->asignacion?->asignatura?->nombre,
                'grupo'       => $item['detalle']->asignacion?->grupo?->nombre_completo,
                'asignacion_id' => $item['detalle']->asignacion_id,
            ])->values(),
            'proxima_clase' => $proximaClase ? [
                'hora_inicio' => \Carbon\Carbon::parse($proximaClase['franja']->hora_inicio)->format('H:i'),
                'asignatura'  => $proximaClase['detalle']->asignacion?->asignatura?->nombre,
                'grupo'       => $proximaClase['detalle']->asignacion?->grupo?->nombre_completo,
            ] : null,
            'asistencia_pendiente_hoy' => $asistenciaPendienteHoy,
            'entregas_pendientes'      => $entregasPendientes,
            'planificacion_hoy'        => $asignacionesHoy->map(function ($asig) use ($planificacionHoy) {
                $unidad = $planificacionHoy->get($asig->id);
                return [
                    'asignacion_id' => $asig->id,
                    'asignatura'    => $asig->asignatura?->nombre,
                    'unidad'        => $unidad?->nombre,
                ];
            })->values(),
            'al_dia' => $asistenciaPendienteHoy === 0 && $entregasPendientes === 0,
        ];
    }
}
