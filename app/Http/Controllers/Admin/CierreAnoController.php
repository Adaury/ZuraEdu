<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertaSistema;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Promocion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CierreAnoController extends Controller
{
    // ── Acceso: solo Admin y Director ─────────────────────────────────────
    private function verificarAcceso(): void
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['Administrador', 'Director'])) {
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
                'schoolYear'    => null,
                'grupos'        => collect(),
                'resumen'       => [],
                'totalAprobados'  => 0,
                'totalReprobados' => 0,
                'totalPendientes' => 0,
            ]);
        }

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->where('school_year_id', $schoolYear->id)
            ->activos()
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        // Bulk-load matriculas + promociones: 1 query en lugar de N (una por grupo)
        $matriculasPorGrupo = Matricula::whereIn('grupo_id', $grupos->pluck('id'))
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->with('promocion')
            ->get()
            ->groupBy('grupo_id');

        $resumen = [];
        $totalAprobados  = 0;
        $totalReprobados = 0;
        $totalPendientes = 0;

        foreach ($grupos as $grupo) {
            $matriculas = $matriculasPorGrupo->get($grupo->id, collect());

            $aprobados  = $matriculas->filter(fn ($m) => $m->promocion?->estado === 'promovido')->count();
            $reprobados = $matriculas->filter(fn ($m) => $m->promocion?->estado === 'no_promovido')->count();
            $pendientes = $matriculas->filter(fn ($m) => ! $m->promocion || $m->promocion->estado === 'pendiente')->count();

            $resumen[$grupo->id] = [
                'grupo'      => $grupo,
                'total'      => $matriculas->count(),
                'aprobados'  => $aprobados,
                'reprobados' => $reprobados,
                'pendientes' => $pendientes,
            ];

            $totalAprobados  += $aprobados;
            $totalReprobados += $reprobados;
            $totalPendientes += $pendientes;
        }

        return view('admin.cierre_ano.index', compact(
            'schoolYear', 'grupos', 'resumen',
            'totalAprobados', 'totalReprobados', 'totalPendientes'
        ));
    }

    // ── Ejecutar cierre de año ────────────────────────────────────────────
    public function ejecutar(Request $request)
    {
        $this->verificarAcceso();

        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        $schoolYear = SchoolYear::findOrFail($request->school_year_id);

        if (! $schoolYear->activo) {
            return back()->with('error', 'El año escolar seleccionado no está activo.');
        }

        DB::beginTransaction();

        try {
            // Obtener todas las matrículas activas del año
            $matriculas = Matricula::where('school_year_id', $schoolYear->id)
                ->where('estado', 'activa')
                ->with(['estudiante', 'grupo.grado', 'promocion'])
                ->get();

            $matIds = $matriculas->pluck('id');

            // Bulk-load para calcularPromedioFinal: 2 queries fijas en lugar de 2×N
            $calAcBulk = CalificacionAcademica::whereIn('matricula_id', $matIds)
                ->where('school_year_id', $schoolYear->id)
                ->whereNotNull('nota_final')
                ->get()->groupBy('matricula_id');

            $periodoIds = Periodo::where('school_year_id', $schoolYear->id)->pluck('id');
            $calBulk    = Calificacion::whereIn('matricula_id', $matIds)
                ->whereIn('periodo_id', $periodoIds)
                ->whereNotNull('nota_final')
                ->get()->groupBy('matricula_id');

            $procesados = 0;

            foreach ($matriculas as $matricula) {
                $promedio = $this->calcularPromedioFinalDesdeMap($matricula->id, $calAcBulk, $calBulk);

                // Determinar estado de promoción
                $estadoPromocion = $this->determinarEstadoPromocion($matricula, $promedio);

                // Crear o actualizar registro de promoción
                Promocion::updateOrCreate(
                    [
                        'matricula_id'  => $matricula->id,
                        'school_year_id' => $schoolYear->id,
                    ],
                    [
                        'estado'         => $estadoPromocion,
                        'promedio_final' => $promedio,
                        'decidido_por'   => auth()->id(),
                        'fecha_decision' => now()->toDateString(),
                        'observacion'    => 'Generado automáticamente en cierre de año escolar.',
                    ]
                );

                // Actualizar estado de matrícula
                $nuevoEstado = $estadoPromocion === 'promovido' ? 'promovida' : 'activa';
                $matricula->update(['estado' => $nuevoEstado]);

                $procesados++;
            }

            // Marcar el año como cerrado (inactivo)
            $schoolYear->update(['activo' => false]);

            // Registrar alerta de sistema
            AlertaSistema::create([
                'tipo'            => 'cierre_ano',
                'titulo'          => 'Cierre de Año Escolar Ejecutado',
                'mensaje'         => "El año escolar {$schoolYear->nombre} fue cerrado. Se procesaron {$procesados} estudiantes.",
                'nivel'           => 'info',
                'destinatario_rol' => 'Administrador',
                'school_year_id'  => $schoolYear->id,
                'creado_por'      => auth()->id(),
            ]);

            DB::commit();

            Log::info('Cierre de año ejecutado', [
                'school_year_id' => $schoolYear->id,
                'procesados'     => $procesados,
                'usuario'        => auth()->id(),
            ]);

            return redirect()->route('admin.cierre-ano.index')
                ->with('success', "Cierre de año ejecutado correctamente. Se procesaron {$procesados} estudiantes.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en cierre de año: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Ocurrió un error al ejecutar el cierre: ' . $e->getMessage());
        }
    }

    // ── Acta de Promoción PDF por grupo ───────────────────────────────────
    public function actaPdf(Grupo $grupo)
    {
        $this->verificarAcceso();

        $schoolYear = SchoolYear::where('id', $grupo->school_year_id)->first()
            ?? SchoolYear::actual();

        $grupo->load(['grado', 'seccion', 'tutor', 'schoolYear']);

        // Asignaturas del grupo (asignaciones activas)
        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($a) => $a->asignatura?->nombre ?? '');

        // Matriculas activas o promovidas del grupo
        $matriculas = Matricula::where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->whereIn('estado', ['activa', 'promovida'])
            ->with(['estudiante', 'promocion'])
            ->orderBy('numero_orden')
            ->get();

        $asignacionIds = $asignaciones->pluck('id');

        // Notas finales desde calificaciones_academicas
        $calAcMap = CalificacionAcademica::whereIn('asignacion_id', $asignacionIds)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->where('school_year_id', $schoolYear?->id ?? $grupo->school_year_id)
            ->get()
            ->groupBy('matricula_id');

        // Fallback: calificaciones promediadas por asignacion
        $periodos = $this->getPeriodos($schoolYear);

        $calLegMap = Calificacion::whereIn('asignacion_id', $asignacionIds)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->groupBy(fn ($c) => $c->matricula_id . '_' . $c->asignacion_id);

        // Construir filas del acta
        $filas = [];
        foreach ($matriculas as $idx => $matricula) {
            $notasPorAsignacion = [];

            foreach ($asignaciones as $asi) {
                $nota = null;

                // Intentar desde calificaciones_academicas
                $calAcRows = $calAcMap->get($matricula->id) ?? collect();
                $calAc = $calAcRows->firstWhere('asignacion_id', $asi->id);

                if ($calAc && $calAc->nota_final !== null) {
                    $nota = (float) $calAc->nota_final;
                } else {
                    // Fallback: promedio de periodos
                    $notasP = [];
                    foreach ($periodos as $p) {
                        $key = $matricula->id . '_' . $asi->id;
                        $cal = $calLegMap->get($key)?->firstWhere('periodo_id', $p->id);
                        if ($cal && $cal->nota_final !== null) {
                            $notasP[] = (float) $cal->nota_final;
                        }
                    }
                    if (count($notasP) > 0) {
                        $nota = round(array_sum($notasP) / count($notasP), 2);
                    }
                }

                $notasPorAsignacion[$asi->id] = $nota;
            }

            $notasValidas = array_filter($notasPorAsignacion, fn ($n) => $n !== null);
            $promedioFinal = count($notasValidas) > 0
                ? round(array_sum($notasValidas) / count($notasValidas), 2)
                : null;

            // Situación: 'A' = Aprobado, 'R' = Reprobado
            $situacion = null;
            if ($matricula->promocion) {
                $situacion = $matricula->promocion->estado === 'promovido' ? 'A' : 'R';
            } elseif ($promedioFinal !== null) {
                $situacion = $promedioFinal >= 70 ? 'A' : 'R';
            }

            $filas[] = [
                'orden'              => $idx + 1,
                'matricula'          => $matricula,
                'notas_asignaciones' => $notasPorAsignacion,
                'promedio_final'     => $promedioFinal,
                'situacion'          => $situacion,
            ];
        }

        // Datos institucionales
        $tid = tenant_id() ?? 0;
        $logoPath = Cache::remember(
            "t{$tid}_system_logo",
            600,
            fn () => DB::table('system_settings')
                ->where('key', 'system_logo')->value('value')
        );
        $instNombre = Cache::remember(
            "t{$tid}_system_name",
            600,
            fn () => DB::table('system_settings')
                ->where('key', 'system_name')->value('value') ?? 'PSAC'
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cierre_ano.acta_pdf', compact(
            'grupo', 'schoolYear', 'asignaciones', 'filas', 'periodos',
            'logoPath', 'instNombre'
        ))->setPaper('letter', 'landscape');

        $nombre = 'acta_' . Str::slug($grupo->nombre_completo ?? $grupo->id) . '.pdf';

        return $pdf->download($nombre);
    }

    // ── Generación masiva de boletines (ZIP) ──────────────────────────────
    // Redirige al método zipGrupo del BoletinController existente.
    public function boletinesMasivos(Request $request)
    {
        $this->verificarAcceso();

        if ($request->isMethod('post')) {
            $request->validate([
                'grupo_id'   => 'required|exists:grupos,id',
                'periodo_id' => 'required|exists:periodos,id',
            ]);

            // Delegar al BoletinController que ya implementa la generación ZIP
            return app(BoletinController::class)->zipGrupo($request);
        }

        // GET: mostrar la vista con el tab de boletines activo
        return redirect()->route('admin.cierre-ano.index')->with('tab', 'boletines');
    }

    // ── Helpers privados ─────────────────────────────────────────────────

    private function calcularPromedioFinalDesdeMap(int $matriculaId, $calAcBulk, $calBulk): ?float
    {
        $calAcs = $calAcBulk->get($matriculaId, collect())
            ->map(fn ($c) => (float) $c->nota_final);

        if ($calAcs->isNotEmpty()) {
            return round($calAcs->avg(), 2);
        }

        $notas = $calBulk->get($matriculaId, collect())
            ->map(fn ($c) => (float) $c->nota_final);

        return $notas->isNotEmpty() ? round($notas->avg(), 2) : null;
    }

    private function determinarEstadoPromocion(Matricula $matricula, ?float $promedio): string
    {
        if ($promedio === null) {
            return 'pendiente';
        }

        // Umbral de aprobación: 60 puntos
        return $promedio >= 60 ? 'promovido' : 'no_promovido';
    }
}
