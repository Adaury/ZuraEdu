<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\AlertaSistema;
use App\Models\CalificacionAcademica;
use App\Models\ConfigInstitucional;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de KPIs institucionales (tiempo real, sin caché), usada tanto por
 * KpiController (/admin/kpis) como por DashboardController (fusión en el
 * dashboard principal para Administrador/Director — Roadmap de producto,
 * docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md, punto 3). Movida aquí sin cambiar
 * ninguna consulta ni comportamiento — antes vivía como métodos privados
 * de KpiController.
 */
class KpiDashboardService
{
    public function calcularKpis(): array
    {
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        return [
            'asistencia_hoy'        => $this->kpiAsistenciaHoy(),
            'notas_pendientes'      => $this->kpiNotasPendientes($syId),
            'alertas_activas'       => $this->kpiAlertasActivas($syId),
            'pagos_mes'             => $this->kpiPagosMes($syId),
            'situacion_estudiantes' => $this->kpiSituacionEstudiantes($syId),
            'grupos_ranking'        => $this->kpiGruposRanking($syId),
            'updated_at'            => now()->format('d/m/Y H:i:s'),
        ];
    }

    // ── KPI 1: Asistencia del día (HOY) ─────────────────────────────────

    public function kpiAsistenciaHoy(): array
    {
        $hoy = now()->toDateString();

        $counts = Asistencia::whereDate('fecha', $hoy)
            ->selectRaw("estado, COUNT(*) as total")
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $presentes    = (int) ($counts['presente']    ?? 0);
        $ausentes     = (int) ($counts['ausente']      ?? 0);
        $tardanzas    = (int) ($counts['tardanza']     ?? 0);
        $justificados = (int) ($counts['justificado']  ?? 0);
        $total        = $presentes + $ausentes + $tardanzas + $justificados;

        return [
            'presentes'    => $presentes,
            'ausentes'     => $ausentes,
            'tardanzas'    => $tardanzas,
            'justificados' => $justificados,
            'total'        => $total,
            'pct_asistencia' => $total > 0
                ? round(($presentes + $tardanzas + $justificados) / $total * 100, 1)
                : 0,
        ];
    }

    // ── KPI 2: Notas pendientes por docente en el período actual ────────

    public function kpiNotasPendientes(int $syId): array
    {
        if (!$syId) {
            return ['total' => 0, 'docentes' => []];
        }

        $periodo = Periodo::where('school_year_id', $syId)
            ->where('activo', true)
            ->where('cerrado', false)
            ->orderByDesc('numero')
            ->first();

        if (!$periodo) {
            return ['total' => 0, 'periodo' => null, 'docentes' => []];
        }

        $asignaciones = Asignacion::where('school_year_id', $syId)
            ->where('activo', true)
            ->with('docente:id,nombres,apellidos')
            ->get();

        $periodoNum = $periodo->numero;
        $campoP     = "comp1_p{$periodoNum}";

        $conNotas = CalificacionAcademica::where('school_year_id', $syId)
            ->whereNotNull($campoP)
            ->distinct()
            ->pluck('asignacion_id')
            ->toArray();

        $pendientes = $asignaciones->whereNotIn('id', $conNotas);

        $porDocente = $pendientes
            ->groupBy('docente_id')
            ->map(function ($items) {
                $docente = $items->first()?->docente;
                return [
                    'nombre'       => $docente?->nombre_completo ?? 'Sin docente',
                    'pendientes'   => $items->count(),
                    'asignaciones' => $items->map(fn($a) => $a->id)->toArray(),
                ];
            })
            ->values()
            ->sortByDesc('pendientes')
            ->take(10)
            ->values()
            ->toArray();

        return [
            'total'    => $pendientes->count(),
            'periodo'  => $periodo->nombre ?? "Período {$periodo->numero}",
            'docentes' => $porDocente,
        ];
    }

    // ── KPI 3: Alertas activas (no leídas, vigentes) por tipo ───────────

    public function kpiAlertasActivas(int $syId): array
    {
        $query = AlertaSistema::noLeidas()->vigentes();

        if ($syId) {
            $query->where(fn($q) =>
                $q->where('school_year_id', $syId)->orWhereNull('school_year_id')
            );
        }

        $total = $query->count();

        $porTipo = (clone $query)
            ->selectRaw("tipo, nivel, COUNT(*) as total")
            ->groupBy('tipo', 'nivel')
            ->get()
            ->groupBy('tipo')
            ->map(function ($items) {
                return [
                    'total'   => $items->sum('total'),
                    'niveles' => $items->pluck('total', 'nivel')->toArray(),
                ];
            })
            ->toArray();

        $labels = AlertaSistema::tiposLabels();

        $porTipoConLabel = [];
        foreach ($porTipo as $tipo => $data) {
            $porTipoConLabel[$tipo] = array_merge($data, [
                'label' => $labels[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo)),
            ]);
        }

        return [
            'total'    => $total,
            'por_tipo' => $porTipoConLabel,
        ];
    }

    // ── KPI 4: Pagos del mes actual ──────────────────────────────────────

    public function kpiPagosMes(int $syId): array
    {
        if (!ConfigInstitucional::moduloActivo('pagos')) {
            return ['activo' => false];
        }

        $mes  = now()->month;
        $anio = now()->year;

        $base = Pago::whereMonth('created_at', $mes)
            ->whereYear('created_at', $anio);

        if ($syId) {
            $base->whereHas('matricula', fn($m) => $m->where('school_year_id', $syId));
        }

        $cobrado   = (clone $base)->where('estado', 'pagado')->sum('monto');
        $pendiente = (clone $base)->where('estado', 'pendiente')->sum('monto');
        $vencido   = (clone $base)->where('estado', 'vencido')->sum('monto');
        $total     = $cobrado + $pendiente + $vencido;

        return [
            'activo'      => true,
            'cobrado'     => (float) $cobrado,
            'pendiente'   => (float) $pendiente,
            'vencido'     => (float) $vencido,
            'total'       => (float) $total,
            'pct_cobrado' => $total > 0 ? round($cobrado / $total * 100, 1) : 0,
            'mes_label'   => now()->translatedFormat('F Y'),
        ];
    }

    // ── KPI 5: Estudiantes por situación (A / R / sin evaluar) ──────────

    public function kpiSituacionEstudiantes(int $syId): array
    {
        if (!$syId) {
            return ['aprobados' => 0, 'reprobados' => 0, 'sin_nota' => 0, 'total' => 0];
        }

        $matriculasActivas = Matricula::where('school_year_id', $syId)
            ->where('estado', 'activa')
            ->count();

        $situaciones = DB::table('calificaciones_academicas')
            ->where('tenant_id', tenant_id())
            ->where('school_year_id', $syId)
            ->selectRaw("matricula_id, MAX(CASE WHEN situacion = 'A' THEN 1 ELSE 0 END) as aprobado")
            ->groupBy('matricula_id')
            ->get();

        $aprobados  = $situaciones->where('aprobado', 1)->count();
        $reprobados = $situaciones->where('aprobado', 0)->count();
        $sinNota    = max(0, $matriculasActivas - $situaciones->count());

        return [
            'aprobados'  => $aprobados,
            'reprobados' => $reprobados,
            'sin_nota'   => $sinNota,
            'total'      => $matriculasActivas,
        ];
    }

    // ── KPI 6: Ranking de grupos (top 3 / bottom 3) por promedio ────────

    public function kpiGruposRanking(int $syId): array
    {
        if (!$syId) {
            return ['top' => [], 'bottom' => []];
        }

        $tid = tenant_id();

        $promedios = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as asi', 'ca.asignacion_id', '=', 'asi.id')
            ->join('grupos as g', 'asi.grupo_id', '=', 'g.id')
            ->join('grados as gr', 'g.grado_id', '=', 'gr.id')
            ->leftJoin('secciones as s', 'g.seccion_id', '=', 's.id')
            ->where('ca.school_year_id', $syId)
            ->where('ca.tenant_id', $tid)
            ->where('g.tenant_id', $tid)
            ->whereNotNull('ca.nota_final')
            ->selectRaw('g.id, gr.nombre as grado, s.nombre as seccion, AVG(ca.nota_final) as promedio, COUNT(DISTINCT ca.matricula_id) as estudiantes')
            ->groupBy('g.id', 'gr.nombre', 's.nombre')
            ->orderByDesc('promedio')
            ->get()
            ->map(fn($row) => [
                'grupo_id'    => $row->id,
                'nombre'      => trim("{$row->grado} {$row->seccion}"),
                'promedio'    => round($row->promedio, 2),
                'estudiantes' => $row->estudiantes,
            ]);

        return [
            'top'    => $promedios->take(3)->values()->toArray(),
            'bottom' => $promedios->sortBy('promedio')->take(3)->values()->toArray(),
        ];
    }
}
