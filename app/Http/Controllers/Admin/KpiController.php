<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\AlertaSistema;
use App\Models\CalificacionAcademica;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Vista principal
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $kpis = $this->calcularKpis();

        return view('admin.kpis.index', compact('kpis'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Endpoint JSON para actualización sin reload (Alpine.js fetch)
    // ─────────────────────────────────────────────────────────────────────────

    public function data(): JsonResponse
    {
        return response()->json($this->calcularKpis());
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Cálculo central de KPIs (tiempo real, sin caché)
    // ─────────────────────────────────────────────────────────────────────────

    private function calcularKpis(): array
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

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 1: Asistencia del día (HOY)
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiAsistenciaHoy(): array
    {
        $hoy = now()->toDateString();

        $counts = Asistencia::whereDate('fecha', $hoy)
            ->selectRaw("estado, COUNT(*) as total")
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $presentes   = (int) ($counts['presente']    ?? 0);
        $ausentes    = (int) ($counts['ausente']      ?? 0);
        $tardanzas   = (int) ($counts['tardanza']     ?? 0);
        $justificados= (int) ($counts['justificado']  ?? 0);
        $total       = $presentes + $ausentes + $tardanzas + $justificados;

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

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 2: Notas pendientes por docente en el período actual
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiNotasPendientes(int $syId): array
    {
        if (!$syId) {
            return ['total' => 0, 'docentes' => []];
        }

        // Período activo (abierto) del año escolar
        $periodo = Periodo::where('school_year_id', $syId)
            ->where('activo', true)
            ->where('cerrado', false)
            ->orderByDesc('numero')
            ->first();

        if (!$periodo) {
            return ['total' => 0, 'periodo' => null, 'docentes' => []];
        }

        // Asignaciones activas del año
        $asignaciones = Asignacion::where('school_year_id', $syId)
            ->where('activo', true)
            ->with('docente:id,nombres,apellidos')
            ->get();

        // IDs de asignaciones que YA tienen calificación publicada en el período
        $periodoNum = $periodo->numero; // 1..4
        $campoP     = "comp1_p{$periodoNum}";  // bastante con verificar que comp1 tiene nota

        $conNotas = CalificacionAcademica::where('school_year_id', $syId)
            ->whereNotNull($campoP)
            ->distinct()
            ->pluck('asignacion_id')
            ->toArray();

        // Asignaciones sin notas ingresadas
        $pendientes = $asignaciones->whereNotIn('id', $conNotas);

        // Agrupar por docente
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
            'total'   => $pendientes->count(),
            'periodo' => $periodo->nombre ?? "Período {$periodo->numero}",
            'docentes'=> $porDocente,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 3: Alertas activas (no leídas, vigentes) por tipo
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiAlertasActivas(int $syId): array
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
                    'total'  => $items->sum('total'),
                    'niveles'=> $items->pluck('total', 'nivel')->toArray(),
                ];
            })
            ->toArray();

        $labels = AlertaSistema::tiposLabels();

        // Enriquecer con etiqueta legible
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

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 4: Pagos del mes actual
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiPagosMes(int $syId): array
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

        $cobrado  = (clone $base)->where('estado', 'pagado')->sum('monto');
        $pendiente= (clone $base)->where('estado', 'pendiente')->sum('monto');
        $vencido  = (clone $base)->where('estado', 'vencido')->sum('monto');
        $total    = $cobrado + $pendiente + $vencido;

        return [
            'activo'       => true,
            'cobrado'      => (float) $cobrado,
            'pendiente'    => (float) $pendiente,
            'vencido'      => (float) $vencido,
            'total'        => (float) $total,
            'pct_cobrado'  => $total > 0 ? round($cobrado / $total * 100, 1) : 0,
            'mes_label'    => now()->translatedFormat('F Y'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 5: Estudiantes por situación (A / R / sin evaluar) del año actual
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiSituacionEstudiantes(int $syId): array
    {
        if (!$syId) {
            return ['aprobados' => 0, 'reprobados' => 0, 'sin_nota' => 0, 'total' => 0];
        }

        $matriculasActivas = Matricula::where('school_year_id', $syId)
            ->where('estado', 'activa')
            ->count();

        // Situación según calificaciones académicas (tomamos la última por matrícula)
        $situaciones = DB::table('calificaciones_academicas')
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

    // ─────────────────────────────────────────────────────────────────────────
    //  KPI 6: Ranking de grupos (top 3 / bottom 3) por promedio nota_final
    // ─────────────────────────────────────────────────────────────────────────

    private function kpiGruposRanking(int $syId): array
    {
        if (!$syId) {
            return ['top' => [], 'bottom' => []];
        }

        $promedios = DB::table('calificaciones_academicas as ca')
            ->join('asignaciones as asi', 'ca.asignacion_id', '=', 'asi.id')
            ->join('grupos as g', 'asi.grupo_id', '=', 'g.id')
            ->join('grados as gr', 'g.grado_id', '=', 'gr.id')
            ->leftJoin('secciones as s', 'g.seccion_id', '=', 's.id')
            ->where('ca.school_year_id', $syId)
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
