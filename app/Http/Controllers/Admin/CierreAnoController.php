<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertaSistema;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Promocion;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CierreAnoController extends Controller
{
    private function verificarAcceso(): void
    {
        if (! auth()->user()->hasAnyRole(['Administrador', 'Director'])) {
            abort(403, 'No tienes permiso para acceder al cierre de año escolar.');
        }
    }

    // ── Index: pantalla principal del cierre ──────────────────────────────
    public function index(Request $request)
    {
        $this->verificarAcceso();

        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return view('admin.cierre_ano.index', [
                'schoolYear'      => null,
                'grupos'          => collect(),
                'resumen'         => [],
                'totalAprobados'  => 0,
                'totalReprobados' => 0,
                'totalPendientes' => 0,
                'preflight'       => null,
                'nuevoAno'        => null,
                'periodos'        => collect(),
            ]);
        }

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->where('school_year_id', $schoolYear->id)
            ->activos()
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        $matriculasPorGrupo = Matricula::whereIn('grupo_id', $grupos->pluck('id'))
            ->where('school_year_id', $schoolYear->id)
            ->whereIn('estado', ['activa', 'promovida'])
            ->with('promocion')
            ->get()
            ->groupBy('grupo_id');

        $resumen = [];
        $totalAprobados = $totalReprobados = $totalPendientes = 0;

        foreach ($grupos as $grupo) {
            $matriculas = $matriculasPorGrupo->get($grupo->id, collect());
            $ap = $matriculas->filter(fn ($m) => $m->promocion?->estado === 'promovido')->count();
            $rp = $matriculas->filter(fn ($m) => $m->promocion?->estado === 'no_promovido')->count();
            $pd = $matriculas->filter(fn ($m) => ! $m->promocion || $m->promocion->estado === 'pendiente')->count();

            $resumen[$grupo->id] = compact('grupo', 'matriculas') + [
                'total'      => $matriculas->count(),
                'aprobados'  => $ap,
                'reprobados' => $rp,
                'pendientes' => $pd,
            ];

            $totalAprobados  += $ap;
            $totalReprobados += $rp;
            $totalPendientes += $pd;
        }

        $preflight = $this->preflightCheck($schoolYear);
        $periodos  = $this->getPeriodos($schoolYear);

        // Verificar si ya existe un año siguiente creado
        $nuevoAno = SchoolYear::where('activo', false)
            ->orderByDesc('fecha_inicio')
            ->first();

        // Si ese año es el actual (activo), no hay uno nuevo aún
        if ($nuevoAno && $nuevoAno->id === $schoolYear->id) {
            $nuevoAno = null;
        }

        return view('admin.cierre_ano.index', compact(
            'schoolYear', 'grupos', 'resumen',
            'totalAprobados', 'totalReprobados', 'totalPendientes',
            'preflight', 'nuevoAno', 'periodos'
        ));
    }

    // ── Pre-flight: valida condiciones antes del cierre ────────────────────
    private function preflightCheck(SchoolYear $schoolYear): array
    {
        $periodos    = $this->getPeriodos($schoolYear);
        $periodosTotal    = $periodos->count();
        $periodosCerrados = $periodos->where('cerrado', true)->count();

        $grupoIds = Grupo::where('school_year_id', $schoolYear->id)->activos()->pluck('id');
        $matIds   = Matricula::whereIn('grupo_id', $grupoIds)
            ->where('school_year_id', $schoolYear->id)
            ->whereIn('estado', ['activa', 'promovida'])
            ->pluck('id');

        $totalEstudiantes     = $matIds->count();
        $conPromocionDecidida = Promocion::whereIn('matricula_id', $matIds)
            ->whereIn('estado', ['promovido', 'no_promovido', 'condicionado'])
            ->count();

        $advertencias = [];
        if ($periodosCerrados < $periodosTotal) {
            $advertencias[] = "Hay " . ($periodosTotal - $periodosCerrados) . " período(s) sin cerrar.";
        }
        if ($totalEstudiantes > 0 && $conPromocionDecidida < $totalEstudiantes) {
            $pendientes = $totalEstudiantes - $conPromocionDecidida;
            $advertencias[] = "{$pendientes} estudiante(s) sin promoción calculada.";
        }
        if ($schoolYear->fecha_fin && $schoolYear->fecha_fin->isFuture()) {
            $advertencias[] = "El año escolar no ha llegado a su fecha de fin (" . $schoolYear->fecha_fin->format('d/m/Y') . ").";
        }

        return [
            'periodos_total'    => $periodosTotal,
            'periodos_cerrados' => $periodosCerrados,
            'total_estudiantes' => $totalEstudiantes,
            'con_promocion'     => $conPromocionDecidida,
            'advertencias'      => $advertencias,
            'puede_ejecutar'    => empty($advertencias) || count($advertencias) === 0,
        ];
    }

    // ── Ejecutar cierre de año ────────────────────────────────────────────
    public function ejecutar(Request $request)
    {
        $this->verificarAcceso();

        $request->validate(['school_year_id' => 'required|exists:school_years,id']);

        $schoolYear = SchoolYear::findOrFail($request->school_year_id);

        if (! $schoolYear->activo) {
            return back()->with('error', 'El año escolar seleccionado no está activo.');
        }

        DB::beginTransaction();

        try {
            $matriculas = Matricula::where('school_year_id', $schoolYear->id)
                ->whereIn('estado', ['activa', 'promovida'])
                ->with(['estudiante', 'grupo.grado', 'promocion'])
                ->get();

            $matIds = $matriculas->pluck('id');

            $calAcBulk = CalificacionAcademica::whereIn('matricula_id', $matIds)
                ->where('school_year_id', $schoolYear->id)
                ->whereNotNull('nota_final')
                ->get()->groupBy('matricula_id');

            $periodoIds = $this->getPeriodos($schoolYear)->pluck('id');
            $calBulk    = Calificacion::whereIn('matricula_id', $matIds)
                ->whereIn('periodo_id', $periodoIds)
                ->whereNotNull('nota_final')
                ->get()->groupBy('matricula_id');

            $procesados = $promovidos = $noPromovidos = 0;

            foreach ($matriculas as $matricula) {
                $promedio        = $this->calcularPromedioFinal($matricula->id, $calAcBulk, $calBulk);
                $estadoPromocion = $this->determinarEstadoPromocion($promedio);

                Promocion::updateOrCreate(
                    ['matricula_id' => $matricula->id, 'school_year_id' => $schoolYear->id],
                    [
                        'estado'         => $estadoPromocion,
                        'promedio_final' => $promedio,
                        'decidido_por'   => auth()->id(),
                        'fecha_decision' => now()->toDateString(),
                        'observacion'    => 'Generado automáticamente en cierre de año escolar.',
                    ]
                );

                $nuevoEstado = $estadoPromocion === 'promovido' ? 'promovida' : 'no_promovida';
                $matricula->update(['estado' => $nuevoEstado]);

                $estadoPromocion === 'promovido' ? $promovidos++ : $noPromovidos++;
                $procesados++;
            }

            $schoolYear->update(['activo' => false]);

            AlertaSistema::create([
                'tipo'             => 'cierre_ano',
                'titulo'           => 'Cierre de Año Escolar Ejecutado',
                'mensaje'          => "El año {$schoolYear->nombre} fue cerrado. {$promovidos} promovidos, {$noPromovidos} no promovidos.",
                'nivel'            => 'info',
                'destinatario_rol' => 'Administrador',
                'school_year_id'   => $schoolYear->id,
                'creado_por'       => auth()->id(),
            ]);

            DB::commit();

            Log::info('Cierre de año ejecutado', [
                'school_year_id' => $schoolYear->id,
                'procesados'     => $procesados,
                'usuario'        => auth()->id(),
            ]);

            Cache::forget("t" . tenant_id() . "_dashboard_matriculas_{$schoolYear->id}");

            return redirect()->route('admin.cierre-ano.index')
                ->with('success', "Año {$schoolYear->nombre} cerrado. {$promovidos} promovidos · {$noPromovidos} no promovidos.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en cierre de año: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Error al ejecutar el cierre: ' . $e->getMessage());
        }
    }

    // ── Crear nuevo año escolar (clona grupos del actual) ─────────────────
    public function crearNuevoAno(Request $request)
    {
        $this->verificarAcceso();

        $request->validate([
            'nombre'      => 'required|string|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'ano_base_id' => 'required|exists:school_years,id',
        ]);

        $anoBase = SchoolYear::findOrFail($request->ano_base_id);

        if ($anoBase->activo) {
            return back()->with('error', 'Primero debes ejecutar el cierre del año actual antes de crear uno nuevo.');
        }

        DB::beginTransaction();

        try {
            // Crear nuevo año
            $nuevoAno = SchoolYear::create([
                'nombre'      => $request->nombre,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'activo'      => true,
            ]);

            // Clonar grupos del año base (mismos grados+secciones, sin estudiantes)
            $gruposBase = Grupo::with(['grado', 'seccion'])
                ->where('school_year_id', $anoBase->id)
                ->activos()
                ->get();

            foreach ($gruposBase as $grupoBase) {
                Grupo::firstOrCreate(
                    [
                        'school_year_id' => $nuevoAno->id,
                        'grado_id'       => $grupoBase->grado_id,
                        'seccion_id'     => $grupoBase->seccion_id,
                    ],
                    ['activo' => true]
                );
            }

            DB::commit();

            Log::info('Nuevo año escolar creado', [
                'nuevo_id' => $nuevoAno->id,
                'nombre'   => $nuevoAno->nombre,
                'grupos'   => $gruposBase->count(),
            ]);

            return redirect()->route('admin.cierre-ano.trasladar', ['ano_nuevo' => $nuevoAno->id, 'ano_base' => $anoBase->id])
                ->with('success', "Año {$nuevoAno->nombre} creado con {$gruposBase->count()} grupos. Ahora traslada a los estudiantes.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear nuevo año: ' . $e->getMessage());
            return back()->with('error', 'Error al crear el nuevo año: ' . $e->getMessage());
        }
    }

    // ── Traslado de estudiantes al nuevo año ──────────────────────────────
    public function trasladar(Request $request)
    {
        $this->verificarAcceso();

        $anoNuevo = SchoolYear::findOrFail($request->query('ano_nuevo'));
        $anoBase  = SchoolYear::findOrFail($request->query('ano_base'));

        // Estudiantes promovidos del año base con su grado actual
        $promovidos = Matricula::where('school_year_id', $anoBase->id)
            ->where('estado', 'promovida')
            ->with(['estudiante', 'grupo.grado', 'grupo.seccion', 'promocion'])
            ->get();

        // Estudiantes no promovidos (repiten grado)
        $noPromovidos = Matricula::where('school_year_id', $anoBase->id)
            ->where('estado', 'no_promovida')
            ->with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->get();

        // Grupos disponibles en el nuevo año, indexados por grado_id
        $gruposNuevos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $anoNuevo->id)
            ->activos()
            ->get();

        $gruposPorGrado = $gruposNuevos->groupBy('grado_id');

        // Mapa grado.nivel → siguiente grado (por nivel+1)
        $grados = Grado::where('activo', true)->orderBy('nivel')->get();
        $siguienteGradoMap = [];
        foreach ($grados as $g) {
            $sig = $grados->firstWhere('nivel', $g->nivel + 1);
            $siguienteGradoMap[$g->id] = $sig?->id;
        }

        // Ya matriculados en el nuevo año
        $yaMatriculados = Matricula::where('school_year_id', $anoNuevo->id)
            ->pluck('estudiante_id')->toArray();

        return view('admin.cierre_ano.trasladar', compact(
            'anoNuevo', 'anoBase',
            'promovidos', 'noPromovidos',
            'gruposNuevos', 'gruposPorGrado',
            'siguienteGradoMap', 'yaMatriculados'
        ));
    }

    // ── Ejecutar traslado masivo ──────────────────────────────────────────
    public function ejecutarTraslado(Request $request)
    {
        $this->verificarAcceso();

        $request->validate([
            'ano_nuevo_id' => 'required|exists:school_years,id',
            'traslados'    => 'required|array|min:1',
            'traslados.*.estudiante_id' => 'required|exists:estudiantes,id',
            'traslados.*.grupo_id'      => 'required|exists:grupos,id',
        ]);

        $anoNuevo = SchoolYear::findOrFail($request->ano_nuevo_id);

        $yaMatriculados = Matricula::where('school_year_id', $anoNuevo->id)
            ->pluck('estudiante_id')->toArray();

        $creados = $omitidos = 0;
        $hoy = now()->toDateString();

        DB::transaction(function () use ($request, $anoNuevo, $yaMatriculados, $hoy, &$creados, &$omitidos) {
            $ordenPorGrupo = [];

            foreach ($request->traslados as $item) {
                $estId  = (int) $item['estudiante_id'];
                $grupoId = (int) $item['grupo_id'];

                if (in_array($estId, $yaMatriculados)) {
                    $omitidos++;
                    continue;
                }

                $ordenPorGrupo[$grupoId] = ($ordenPorGrupo[$grupoId] ?? 0) + 1;

                Matricula::create([
                    'school_year_id'  => $anoNuevo->id,
                    'estudiante_id'   => $estId,
                    'grupo_id'        => $grupoId,
                    'fecha_matricula' => $hoy,
                    'estado'          => 'activa',
                    'numero_orden'    => $ordenPorGrupo[$grupoId],
                ]);

                $yaMatriculados[] = $estId;
                $creados++;
            }
        });

        $msg = "{$creados} estudiante(s) trasladado(s) al año {$anoNuevo->nombre}.";
        if ($omitidos > 0) $msg .= " ({$omitidos} ya matriculados, omitidos.)";

        return redirect()->route('admin.cierre-ano.index')->with('success', $msg);
    }

    // ── Acta de Promoción PDF por grupo ───────────────────────────────────
    public function actaPdf(Grupo $grupo)
    {
        $this->verificarAcceso();

        $schoolYear = SchoolYear::find($grupo->school_year_id) ?? SchoolYear::actual();
        $grupo->load(['grado', 'seccion', 'tutor', 'schoolYear']);

        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($a) => $a->asignatura?->nombre ?? '');

        $matriculas = Matricula::where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->whereIn('estado', ['activa', 'promovida', 'no_promovida'])
            ->with(['estudiante', 'promocion'])
            ->orderBy('numero_orden')
            ->get();

        $asignacionIds = $asignaciones->pluck('id');
        $matIds        = $matriculas->pluck('id');

        $calAcMap = CalificacionAcademica::whereIn('asignacion_id', $asignacionIds)
            ->whereIn('matricula_id', $matIds)
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->get()->groupBy('matricula_id');

        $periodos  = $this->getPeriodos($schoolYear);
        $calLegMap = Calificacion::whereIn('asignacion_id', $asignacionIds)
            ->whereIn('matricula_id', $matIds)
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->groupBy(fn ($c) => $c->matricula_id . '_' . $c->asignacion_id);

        $filas = [];
        foreach ($matriculas as $idx => $matricula) {
            $notasPorAsignacion = [];

            foreach ($asignaciones as $asi) {
                $nota    = null;
                $calAcs  = ($calAcMap->get($matricula->id) ?? collect())->firstWhere('asignacion_id', $asi->id);

                if ($calAcs && $calAcs->nota_final !== null) {
                    $nota = (float) $calAcs->nota_final;
                } else {
                    $notasP = [];
                    foreach ($periodos as $p) {
                        $key = $matricula->id . '_' . $asi->id;
                        $cal = ($calLegMap->get($key) ?? collect())->firstWhere('periodo_id', $p->id);
                        if ($cal && $cal->nota_final !== null) $notasP[] = (float) $cal->nota_final;
                    }
                    if (count($notasP) > 0) $nota = round(array_sum($notasP) / count($notasP), 2);
                }

                $notasPorAsignacion[$asi->id] = $nota;
            }

            $notasValidas  = array_filter($notasPorAsignacion, fn ($n) => $n !== null);
            $promedioFinal = count($notasValidas) > 0 ? round(array_sum($notasValidas) / count($notasValidas), 2) : null;

            $situacion = match($matricula->estado) {
                'promovida'    => 'A',
                'no_promovida' => 'R',
                default        => ($promedioFinal !== null ? ($promedioFinal >= 60 ? 'A' : 'R') : null),
            };

            $filas[] = [
                'orden'              => $idx + 1,
                'matricula'          => $matricula,
                'notas_asignaciones' => $notasPorAsignacion,
                'promedio_final'     => $promedioFinal,
                'situacion'          => $situacion,
            ];
        }

        $tid       = tenant_id() ?? 0;
        $logoPath  = Cache::remember("t{$tid}_system_logo", 600, fn () => DB::table('system_settings')->where('key', 'system_logo')->value('value'));
        $instNombre = Cache::remember("t{$tid}_system_name", 600, fn () => DB::table('system_settings')->where('key', 'system_name')->value('value') ?? 'Institución');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cierre_ano.acta_pdf', compact(
            'grupo', 'schoolYear', 'asignaciones', 'filas', 'periodos', 'logoPath', 'instNombre'
        ))->setPaper('letter', 'landscape');

        return $pdf->download('acta_' . Str::slug($grupo->nombre_completo ?? $grupo->id) . '.pdf');
    }

    // ── Boletines masivos (ZIP) ────────────────────────────────────────────
    public function boletinesMasivos(Request $request)
    {
        $this->verificarAcceso();

        if ($request->isMethod('post')) {
            $request->validate([
                'grupo_id'   => 'required|exists:grupos,id',
                'periodo_id' => 'required|exists:periodos,id',
            ]);
            return app(BoletinController::class)->zipGrupo($request);
        }

        return redirect()->route('admin.cierre-ano.index')->with('tab', 'boletines');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function calcularPromedioFinal(int $matriculaId, $calAcBulk, $calBulk): ?float
    {
        $calAcs = ($calAcBulk->get($matriculaId) ?? collect())->map(fn ($c) => (float) $c->nota_final);
        if ($calAcs->isNotEmpty()) return round($calAcs->avg(), 2);

        $notas = ($calBulk->get($matriculaId) ?? collect())->map(fn ($c) => (float) $c->nota_final);
        return $notas->isNotEmpty() ? round($notas->avg(), 2) : null;
    }

    private function determinarEstadoPromocion(?float $promedio): string
    {
        if ($promedio === null) return 'pendiente';
        return $promedio >= 60 ? 'promovido' : 'no_promovido';
    }
}
