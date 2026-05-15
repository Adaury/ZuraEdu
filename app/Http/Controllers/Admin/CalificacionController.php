<?php

namespace App\Http\Controllers\Admin;

use App\Events\CalificacionesPublicadas;
use App\Events\GradePublished;
use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\CalificacionAudit;
use App\Models\ConfigCalificacion;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\IndicadorAprendizaje;
use App\Models\ResultadoAprendizaje;
use App\Models\SchoolYear;
use App\Mail\BoletinDisponible;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Nota: los métodos académicos (planillaAcademica, guardarAcademica, publicarAcademica,
// exportarPlanillaPdf, exportarPlanillaExcel) se encuentran en CalificacionAcademicaController.

class CalificacionController extends Controller
{
    // ── Helper: get docente for auth user (null if admin/director) ────────
    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            return Docente::where('user_id', $user->id)->first()
                ?? Docente::where('email', $user->email)->first();
        }
        return null;
    }

    // ── Index: selection screen ────────────────────────────────────────────
    public function index()
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $docente  = $this->docenteActual();
        $periodos = $this->getPeriodos($schoolYear);

        // Redirect docente to setup if no profile or no asignaciones for this year
        if ($docente) {
            $tieneAsignaciones = Asignacion::where('docente_id', $docente->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->exists();
            if (!$tieneAsignaciones) {
                return redirect()->route('admin.docente.setup')
                    ->with('info', 'Completa tu configuración indicando las materias que impartes.');
            }
        }
        // Si el usuario tiene rol Docente pero NO tiene docente record
        if (!$docente && auth()->user()->hasRole('Docente')) {
            return redirect()->route('admin.docente.setup')
                ->with('info', 'Completa tu perfil para continuar.');
        }

        // Docentes only see their own asignaciones
        if ($docente) {
            $asignaciones = Asignacion::with(['asignatura','grupo.grado','grupo.seccion'])
                ->where('school_year_id', $schoolYear->id)
                ->where('docente_id', $docente->id)
                ->where('activo', true)
                ->get();

            $asignacionesJs = $asignaciones->map(function ($a) {
                $grupoNombre = '—';
                if ($a->grupo) {
                    $grado   = $a->grupo->grado?->nombre   ?? '';
                    $seccion = $a->grupo->seccion?->nombre ?? '';
                    $grupoNombre = trim($grado . ' ' . $seccion) ?: '—';
                }
                return [
                    'id'     => $a->id,
                    'nombre' => $a->asignatura?->nombre ?? '—',
                    'grupo'  => $grupoNombre,
                    'es_ra'  => in_array($a->tipo_evaluacion, ['ra', 'competencias']),
                    'area'   => $a->area ?? 'academica',
                ];
            })->values();

            // Agrupar asignaciones del docente por grupo
            $asignacionesPorGrupo = $asignaciones->groupBy(function($a) {
                return $a->grupo_id;
            });

            return view('admin.calificaciones.index', compact('asignaciones', 'asignacionesJs', 'periodos', 'schoolYear', 'docente', 'asignacionesPorGrupo'));
        }

        // Admin/Coordinador/Director — filter by ciclo/area if provided
        $ciclo = request('ciclo');
        $area  = request('area');

        $grupos = Grupo::with(['grado','seccion','asignaciones'])
            ->where('school_year_id', $schoolYear->id)
            ->when($ciclo == 1, fn($q) => $q->whereHas('grado', fn($g) => $g->whereBetween('nivel', [1, 3])))
            ->when($ciclo == 2, fn($q) => $q->whereHas('grado', fn($g) => $g->whereBetween('nivel', [4, 6])))
            ->orderBy('grado_id')
            ->get();

        $contexto = null;
        if ($ciclo == 1) $contexto = 'Primer Ciclo (1ro–3ro)';
        elseif ($ciclo == 2 && $area === 'academica') $contexto = 'Segundo Ciclo — Área Académica';
        elseif ($ciclo == 2 && $area === 'tecnica')   $contexto = 'Segundo Ciclo — Área Técnica';

        return view('admin.calificaciones.index', compact('grupos', 'periodos', 'schoolYear', 'ciclo', 'area', 'contexto'));
    }

    // ── Grilla: grade entry grid (técnica/RA) or redirect to planilla académica ─
    public function grilla(Request $request)
    {
        // Validate asignacion first to detect area
        $request->validate(['asignacion_id' => 'required|exists:asignaciones,id']);

        $asignacion = Asignacion::with([
            'grupo.matriculas.estudiante',
            'asignatura.resultadosAprendizaje',
            'docente',
        ])->findOrFail($request->asignacion_id);

        // Verificar acceso por policy (cubre docente propio + docente guía del grupo)
        $this->authorize('verCalificaciones', $asignacion);

        // ── Área académica → planilla anual con 4 competencias ────────────
        if ($asignacion->area === 'academica') {
            return $this->planillaAcademica($request);
        }

        // ── Área técnica → grilla por período ────────────────────────────
        $request->validate(['periodo_id' => 'required|exists:periodos,id']);

        $periodo = Periodo::findOrFail($request->periodo_id);

        $esRA  = in_array($asignacion->tipo_evaluacion, ['ra', 'competencias']);
        $numRA = $asignacion->asignatura->num_ra ?? 0;
        $rasDB = $esRA ? $asignacion->asignatura->resultadosAprendizaje()->activos()->orderBy('numero')->get() : collect();

        // Build RA peso map for JS: { "ra1": 25.0, "ra2": 25.0, ... }
        $raPesosJs = [];
        if ($esRA) {
            $raCount = $rasDB->count() ?: max($numRA, 1);
            $raItems = $rasDB->count() > 0
                ? $rasDB
                : collect(range(1, $raCount))->map(fn($n) => (object)['numero' => $n, 'peso' => null]);
            foreach ($raItems as $ra) {
                $raPesosJs["ra{$ra->numero}"] = $ra->peso ?? round(100 / $raCount, 4);
            }
        }

        $pesos = ConfigCalificacion::getPesos($asignacion->school_year_id);

        if ($pesos->isEmpty()) {
            $defaults = ['tareas'=>20,'practicas'=>20,'participacion'=>20,'proyecto'=>20,'examen'=>20];
            $pesos = collect($defaults)->map(function ($peso, $componente) {
                return (object) ['componente' => $componente, 'peso' => $peso, 'activo' => true];
            });
        }

        $matriculas = $asignacion->grupo
            ->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();

        // Bulk-load: 1 query en lugar de N (una por alumno con firstOrNew)
        $matIds = $matriculas->pluck('id');
        $existentes = Calificacion::where('asignacion_id', $asignacion->id)
            ->where('periodo_id', $periodo->id)
            ->whereIn('matricula_id', $matIds)
            ->get()
            ->keyBy('matricula_id');

        $calificaciones = [];
        foreach ($matriculas as $m) {
            $calificaciones[$m->id] = $existentes->get($m->id)
                ?? new Calificacion([
                    'matricula_id'  => $m->id,
                    'asignacion_id' => $asignacion->id,
                    'periodo_id'    => $periodo->id,
                ]);
        }

        return view('admin.calificaciones.grilla', compact(
            'asignacion', 'periodo', 'matriculas', 'calificaciones', 'pesos',
            'esRA', 'rasDB', 'numRA', 'raPesosJs'
        ));
    }

    // ── Guardar técnica: batch save via AJAX ──────────────────────────────
    public function guardar(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'periodo_id'    => 'required|exists:periodos,id',
            'notas'         => 'required|array',
        ]);

        $asignacion = Asignacion::findOrFail($request->asignacion_id);
        $this->authorize('ingresarCalificaciones', $asignacion);

        $pesos      = ConfigCalificacion::getPesos($asignacion->school_year_id);

        if ($pesos->isEmpty()) {
            $defaults = ['tareas'=>20,'practicas'=>20,'participacion'=>20,'proyecto'=>20,'examen'=>20];
            $pesos = collect($defaults)->map(function ($peso, $componente) {
                return (object) ['componente' => $componente, 'peso' => $peso, 'activo' => true];
            });
        }

        $esRA  = in_array($asignacion->tipo_evaluacion, ['ra', 'competencias']);
        $saved = 0;

        // Pre-cargar pesos RA una sola vez fuera del loop (evita N+1)
        $rasConPeso = $esRA
            ? $asignacion->asignatura->resultadosAprendizaje()->activos()->orderBy('numero')->get()
            : collect();
        $numRaTotal = $rasConPeso->count() ?: ($asignacion->asignatura->num_ra ?? 10);
        $pesoPorRa  = [];
        foreach ($rasConPeso as $ra) {
            $pesoPorRa[$ra->numero] = $ra->peso ?? (100 / $numRaTotal);
        }

        foreach ($request->notas as $matriculaId => $componentes) {
            $data = [];

            if ($esRA) {
                foreach (['tareas','practicas','participacion','proyecto','examen'] as $comp) {
                    $data[$comp] = null;
                }

                // Criterios por RA desde el admin: criterios[matId][ra1][tp|ex|cc|oh|pd|ec]
                $criteriosInput     = $request->input('criterios', []);
                $recuperacionesInput = $request->input('recuperaciones', []);
                $critJson = [];
                $recJson  = [];

                $sumaPonderada = 0; $pesoActivo = 0;
                for ($i = 1; $i <= 10; $i++) {
                    $key  = "ra{$i}";
                    $pMax = $pesoPorRa[$i] ?? (100 / $numRaTotal);

                    // Criterios
                    $crit = $criteriosInput[$matriculaId][$key] ?? [];
                    $critData = null;
                    if (!empty(array_filter($crit, fn($v) => $v !== '' && $v !== null))) {
                        $tp = isset($crit['tp']) && $crit['tp'] !== '' ? min((float)$crit['tp'], 30) : null;
                        $ex = isset($crit['ex']) && $crit['ex'] !== '' ? min((float)$crit['ex'], 15) : null;
                        $cc = isset($crit['cc']) && $crit['cc'] !== '' ? min((float)$crit['cc'], 10) : null;
                        $oh = isset($crit['oh']) && $crit['oh'] !== '' ? min((float)$crit['oh'], 20) : null;
                        $pd = isset($crit['pd']) && $crit['pd'] !== '' ? min((float)$crit['pd'], 15) : null;
                        $ec = isset($crit['ec']) && $crit['ec'] !== '' ? min((float)$crit['ec'], 10) : null;
                        $cfCrit = ($tp??0)+($ex??0)+($cc??0)+($oh??0)+($pd??0)+($ec??0);
                        $critData = compact('tp','ex','cc','oh','pd','ec') + ['cf' => round($cfCrit,2)];
                    }
                    $critJson[$i] = $critData;

                    // Nota RA: si hay criterios, calcula de ellos; si no, usa el valor directo
                    if ($critData !== null) {
                        $raw = round($critData['cf'] / 100 * $pMax, 2);
                    } else {
                        $val = $componentes[$key] ?? null;
                        $raw = ($val !== null && $val !== '') ? (float) $val : null;
                    }

                    // Recuperación
                    $rec = $recuperacionesInput[$matriculaId][$key] ?? [];
                    $recData = null;
                    if (!empty(array_filter($rec, fn($v) => $v !== '' && $v !== null))) {
                        $rP  = isset($rec['practica'])     && $rec['practica']     !== '' ? min((float)$rec['practica'], 25) : null;
                        $rE  = isset($rec['exposicion'])   && $rec['exposicion']   !== '' ? min((float)$rec['exposicion'], 25) : null;
                        $rPE = isset($rec['practica_eval'])&& $rec['practica_eval']!== '' ? min((float)$rec['practica_eval'], 50) : null;
                        $notaRec  = ($rP??0)+($rE??0)+($rPE??0);
                        $notaAcum = ($raw !== null) ? round($raw / $pMax * 100, 2) : 0;
                        $cfRec    = round(0.5*$notaAcum + 0.5*$notaRec, 2);
                        $recData  = ['practica'=>$rP,'exposicion'=>$rE,'practica_eval'=>$rPE,
                                     'nota_rec'=>round($notaRec,2),'nota_acum'=>$notaAcum,
                                     'cf'=>$cfRec,'cf_escalada'=>round($cfRec/100*$pMax,2)];
                    }
                    $recJson[$i] = $recData;

                    // Nota efectiva (mejor entre raw y recuperación)
                    $efectiva = $raw;
                    if ($recData !== null && $recData['cf_escalada'] > ($raw ?? 0)) {
                        $efectiva = min($recData['cf_escalada'], $pMax);
                    }

                    $data[$key] = $raw;
                    if ($efectiva !== null && $i <= $numRaTotal) {
                        $sumaPonderada += $efectiva * $pMax;
                        $pesoActivo    += $pMax;
                    }
                }

                $data['criterios_ra']     = $critJson;
                $data['recuperaciones_ra'] = $recJson;
                // Proportional redistribution if some RAs missing
                $notaFinal = $pesoActivo > 0 ? round($sumaPonderada / $pesoActivo, 2) : null;
            } else {
                foreach (['tareas','practicas','participacion','proyecto','examen'] as $comp) {
                    $val = $componentes[$comp] ?? null;
                    $data[$comp] = ($val !== null && $val !== '') ? (float) $val : null;
                }
                for ($i = 1; $i <= 10; $i++) {
                    $data["ra{$i}"] = null;
                }
                $notaFinal = self::calcularNota($data, $pesos);
            }

            // Completivo: 50% nota_final + 50% C.C
            $cc = isset($componentes['nota_cc']) && $componentes['nota_cc'] !== ''
                ? (float) $componentes['nota_cc'] : null;
            $data['nota_cc'] = $cc;
            $data['nota_completiva'] = ($notaFinal !== null && $cc !== null)
                ? round(0.5 * $notaFinal + 0.5 * $cc, 2) : null;

            // Extraordinario: 30% C.F + 70% C.E
            $ce = isset($componentes['nota_ce']) && $componentes['nota_ce'] !== ''
                ? (float) $componentes['nota_ce'] : null;
            $data['nota_ce'] = $ce;
            $data['nota_extraordinaria'] = ($notaFinal !== null && $ce !== null)
                ? round(0.3 * $notaFinal + 0.7 * $ce, 2) : null;

            // Asistencia
            $data['asistencia_clases'] = isset($componentes['asistencia_clases']) && $componentes['asistencia_clases'] !== ''
                ? (int) $componentes['asistencia_clases'] : null;
            $data['asistencia_total'] = isset($componentes['asistencia_total']) && $componentes['asistencia_total'] !== ''
                ? (int) $componentes['asistencia_total'] : null;

            // Observaciones
            $data['observaciones'] = isset($componentes['observaciones']) && $componentes['observaciones'] !== ''
                ? $componentes['observaciones'] : null;

            $indicador = self::resolverIndicador($notaFinal);

            // Auditoría: capturar registro anterior antes de guardar
            $anterior = Calificacion::where([
                'matricula_id'  => $matriculaId,
                'asignacion_id' => $request->asignacion_id,
                'periodo_id'    => $request->periodo_id,
            ])->first();

            CalificacionAudit::registrarCambios(
                'Calificacion',
                $anterior,
                array_merge($data, ['nota_final' => $notaFinal]),
                (int) $matriculaId,
                (int) $request->asignacion_id,
                ['nota_final', 'tareas', 'practicas', 'participacion', 'proyecto', 'examen']
            );

            Calificacion::updateOrCreate(
                [
                    'matricula_id'  => $matriculaId,
                    'asignacion_id' => $request->asignacion_id,
                    'periodo_id'    => $request->periodo_id,
                ],
                array_merge($data, [
                    'nota_final'    => $notaFinal,
                    'indicador'     => $indicador,
                    'modificado_por'=> auth()->id(),
                ])
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se guardaron las calificaciones de {$saved} estudiante(s).",
            'saved'   => $saved,
        ]);
    }

    // ── Publicar técnica ──────────────────────────────────────────────────
    public function publicar(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'periodo_id'    => 'required|exists:periodos,id',
        ]);

        $count = Calificacion::where('asignacion_id', $request->asignacion_id)
            ->where('periodo_id', $request->periodo_id)->count();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No hay calificaciones para publicar.'], 422);
        }

        $primera = Calificacion::where('asignacion_id', $request->asignacion_id)
            ->where('periodo_id', $request->periodo_id)->first();
        $nuevoEstado = ! $primera->publicado;

        $asignacion = Asignacion::find($request->asignacion_id);

        Calificacion::where('asignacion_id', $request->asignacion_id)
            ->where('periodo_id', $request->periodo_id)
            ->update(['publicado' => $nuevoEstado]);

        // Invalidar caché de ranking del grupo afectado
        if ($asignacion) {
            $schoolYear = SchoolYear::actual();
            $tid = tenant_id() ?? 0;
            Cache::forget("t{$tid}_ranking_{$asignacion->grupo_id}_p{$request->periodo_id}_sy" . optional($schoolYear)->id);
            Cache::forget("t{$tid}_ranking_{$asignacion->grupo_id}_p_sy" . optional($schoolYear)->id);
        }

        // ── Notificar por WhatsApp a representantes ───────────────────────
        if ($nuevoEstado && $asignacion) {
            $asignacion->load(['asignatura', 'grupo']);
            $materiaNombre = $asignacion->asignatura->nombre ?? 'una materia';

            $matriculas = Matricula::with(['estudiante.representantes'])
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('activo', true)
                ->get();

            // Pre-cargar todas las calificaciones del período en una sola query (evita N+1)
            $calificacionesMap = Calificacion::where('asignacion_id', $asignacion->id)
                ->where('periodo_id', $request->periodo_id)
                ->pluck('nota_final', 'matricula_id');

            foreach ($matriculas as $matricula) {
                $estudiante = $matricula->estudiante;
                if (! $estudiante) continue;

                $nota = $calificacionesMap[$matricula->id] ?? 0;

                $nombreEstudiante = trim($estudiante->nombres . ' ' . $estudiante->apellidos);

                foreach ($estudiante->representantes as $rep) {
                    $telefono = preg_replace('/[^0-9+]/', '', $rep->telefono ?? '');
                    if ($telefono) {
                        try {
                            WhatsAppService::sendGradePublished($telefono, $nombreEstudiante, $materiaNombre, (float) $nota);
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // ── Notificar por Email a representantes y estudiantes ────────────
        if ($nuevoEstado && $asignacion && \App\Helpers\Setting::get('email_notif_calificaciones', '1') === '1') {
            $periodo = Periodo::find($request->periodo_id);
            if ($periodo) {
                $matriculasEmail = $matriculas ?? Matricula::with(['estudiante.representantes', 'estudiante.user'])
                    ->where('grupo_id', $asignacion->grupo_id)
                    ->where('activo', true)->get();

                foreach ($matriculasEmail as $mat) {
                    $est = $mat->estudiante;
                    if (! $est) continue;

                    // Email al representante
                    foreach ($est->representantes as $rep) {
                        if ($rep->email) {
                            try {
                                Mail::to($rep->email)->queue(
                                    new BoletinDisponible($est, $periodo, route('portal.representante', $est->id))
                                );
                            } catch (\Throwable $e) {}
                        }
                    }
                    // Email al estudiante si tiene cuenta
                    if ($est->user?->email) {
                        try {
                            Mail::to($est->user->email)->queue(
                                new BoletinDisponible($est, $periodo, route('portal.estudiante.boletin'))
                            );
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // Broadcast realtime al grupo cuando se publican calificaciones
        if ($nuevoEstado && $asignacion) {
            try {
                $periodo = $periodo ?? Periodo::find($request->periodo_id);
                $periodoNombre  = $periodo?->nombre ?? "Período {$request->periodo_id}";
                $asignaturaNombre = $asignacion->asignatura?->nombre ?? 'Asignatura';
                CalificacionesPublicadas::dispatch(
                    $asignacion->grupo_id,
                    $periodoNombre,
                    $asignaturaNombre,
                );
                GradePublished::dispatch(
                    $asignacion->grupo_id,
                    $periodoNombre,
                    $asignaturaNombre,
                );
            } catch (\Throwable) {}
        }

        $msg = $nuevoEstado
            ? "Calificaciones publicadas ({$count} registros)."
            : "Calificaciones despublicadas ({$count} registros).";

        return response()->json(['success' => true, 'publicado' => $nuevoEstado, 'message' => $msg]);
    }

    // ── Resumen: all subjects × all periods matrix ─────────────────────────
    public function resumen(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) return back()->with('error', 'No hay un año escolar activo.');

        $grupos   = Grupo::with(['grado','seccion'])->where('school_year_id', $schoolYear->id)->get();
        $periodos = $this->getPeriodos($schoolYear);
        $grupoId  = $request->grupo_id ?? optional($grupos->first())->id;
        $grupo    = $grupoId ? Grupo::with(['grado','seccion'])->find($grupoId) : null;

        $matriculas = collect(); $asignaciones = collect(); $matrix = [];

        if ($grupo) {
            $matriculas   = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();
            $asignaciones = Asignacion::with('asignatura')->where('grupo_id', $grupo->id)->get();

            // Bulk load — one query instead of (matriculas × asignaciones × periodos)
            $matriculaIds  = $matriculas->pluck('id');
            $asignacionIds = $asignaciones->pluck('id');
            $periodoIds    = $periodos->pluck('id');

            $allCals = Calificacion::whereIn('matricula_id', $matriculaIds)
                ->whereIn('asignacion_id', $asignacionIds)
                ->whereIn('periodo_id', $periodoIds)
                ->get()
                ->keyBy(fn ($c) => "{$c->matricula_id}_{$c->asignacion_id}_{$c->periodo_id}");

            $matrix = [];
            foreach ($matriculas as $m) {
                foreach ($asignaciones as $asi) {
                    foreach ($periodos as $p) {
                        $matrix[$m->id][$asi->id][$p->id] = $allCals->get("{$m->id}_{$asi->id}_{$p->id}");
                    }
                }
            }
        }

        return view('admin.calificaciones.resumen', compact(
            'schoolYear','grupos','periodos','grupo','matriculas','asignaciones','matrix'
        ));
    }

    // ── Progreso por Período PDF ───────────────────────────────────────────
    public function progresoPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupoId = $request->grupo_id;
        if (! $grupoId) return back()->with('error', 'Selecciona un grupo primero.');

        $grupo        = Grupo::with(['grado','seccion'])->findOrFail($grupoId);
        $periodos     = $this->getPeriodos($schoolYear);
        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        $matriculaIds  = $grupo->matriculas()->activas()->pluck('id');
        $allCals       = Calificacion::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get();

        $promedios = [];
        foreach ($asignaciones as $asig) {
            $promedios[$asig->id] = [];
            foreach ($periodos as $p) {
                $notas = $allCals->where('asignacion_id', $asig->id)->where('periodo_id', $p->id)->pluck('nota_final')->filter();
                $promedios[$asig->id][$p->id] = $notas->count() ? round($notas->avg(), 1) : null;
            }
            $general = collect($promedios[$asig->id])->filter();
            $promedios[$asig->id]['general'] = $general->count() ? round($general->avg(), 1) : null;
        }

        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.calificaciones.progreso_pdf', compact(
            'grupo', 'periodos', 'asignaciones', 'promedios', 'schoolYear', 'inst'
        ))->setPaper('letter', 'landscape');

        return $pdf->stream('Progreso_' . \Str::slug($grupo->nombre_completo) . '.pdf');
    }

    // ── Progreso Excel ────────────────────────────────────────────────────
    public function progresoExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupoId = $request->grupo_id;
        if (! $grupoId) return back()->with('error', 'Selecciona un grupo primero.');

        $grupo        = Grupo::with(['grado','seccion'])->findOrFail($grupoId);
        $periodos     = $this->getPeriodos($schoolYear);
        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->orderBy('id')->get();

        $matriculaIds = $grupo->matriculas()->activas()->pluck('id');
        $allCals      = Calificacion::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get();

        $promedios = [];
        foreach ($asignaciones as $asig) {
            $promedios[$asig->id] = [];
            foreach ($periodos as $p) {
                $notas = $allCals->where('asignacion_id', $asig->id)->where('periodo_id', $p->id)->pluck('nota_final')->filter();
                $promedios[$asig->id][$p->id] = $notas->count() ? round($notas->avg(), 1) : null;
            }
            $general = collect($promedios[$asig->id])->filter();
            $promedios[$asig->id]['general'] = $general->count() ? round($general->avg(), 1) : null;
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Progreso');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $headers = ['Asignatura'];
        foreach ($periodos as $p) { $headers[] = 'P' . $p->numero . ' Promedio'; }
        $headers[] = 'Promedio General';
        $lastCol = chr(64 + count($headers));

        $ws->setCellValue('A1', 'Progreso de Calificaciones — ' . $grupo->nombre_completo . ' — ' . $schoolYear->nombre);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        foreach ($headers as $i => $h) { $ws->setCellValue(chr(65 + $i) . '3', $h); }
        $ws->getStyle("A3:{$lastCol}3")->applyFromArray($hdrStyle);

        foreach ($asignaciones as $i => $asig) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $asig->asignatura?->nombre ?? '—');
            $col = 1;
            foreach ($periodos as $p) {
                $prom = $promedios[$asig->id][$p->id] ?? null;
                $ws->setCellValue([$col + 1, $row], $prom ?? '');
                if ($prom !== null && $prom < 60) {
                    $ws->getStyle([$col + 1, $row])->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fee2e2');
                } elseif ($prom !== null && $prom >= 90) {
                    $ws->getStyle([$col + 1, $row])->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('d1fae5');
                }
                $col++;
            }
            $gen = $promedios[$asig->id]['general'] ?? null;
            $ws->setCellValue([$col + 1, $row], $gen ?? '');
            if ($i % 2 === 0) {
                $ws->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f9fafb');
            }
        }

        foreach (range('A', $lastCol) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'prog_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($grupo->nombre_completo);
        return response()->download($tmp, "progreso_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Ranking ────────────────────────────────────────────────────────────
    public function ranking(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) return back()->with('error', 'No hay un año escolar activo.');

        $grupos   = Grupo::with(['grado','seccion'])->where('school_year_id', $schoolYear->id)->get();
        $periodos = $this->getPeriodos($schoolYear);

        $grupoId   = $request->grupo_id  ?? optional($grupos->first())->id;
        $periodoId = $request->periodo_id;
        $grupo     = $grupoId ? Grupo::with(['grado','seccion'])->find($grupoId) : null;
        $ranking   = collect();

        if ($grupo) {
            $tid = tenant_id() ?? 0;
            $cacheKey = "t{$tid}_ranking_{$grupoId}_p{$periodoId}_sy{$schoolYear->id}";
            $ranking  = Cache::remember($cacheKey, 1800, function () use ($grupo, $periodoId) {
                $matriculas = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();

                $calsQuery = Calificacion::whereIn('matricula_id', $matriculas->pluck('id'))
                    ->whereNotNull('nota_final');
                if ($periodoId) $calsQuery->where('periodo_id', $periodoId);
                $allCals = $calsQuery->get()->groupBy('matricula_id');

                $result = collect();
                foreach ($matriculas as $m) {
                    $cals     = $allCals->get($m->id, collect());
                    $promedio = $cals->avg('nota_final');
                    $result->push([
                        'matricula'  => $m,
                        'estudiante' => $m->estudiante,
                        'promedio'   => $promedio ? round($promedio, 2) : null,
                        'materias'   => $cals->count(),
                    ]);
                }
                return $result->sortByDesc('promedio')->values();
            });
        }

        return view('admin.calificaciones.ranking', compact(
            'schoolYear','grupos','periodos','grupo','ranking','grupoId','periodoId'
        ));
    }

    // ── Acta de notas por asignación PDF (admin/coordinador) ─────────────
    public function actaPdf(Asignacion $asignacion)
    {
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero_orden')->get();

        $esTecnica = $asignacion->area === 'tecnica';
        $periodos  = $this->getPeriodos($schoolYear);

        if ($esTecnica) {
            $calificaciones = Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->get()->groupBy(fn($c) => $c->matricula_id . '_' . $c->periodo_id);
        } else {
            $calificaciones = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()->keyBy('matricula_id');
        }

        $docente = $asignacion->docente;
        $si      = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config  = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.acta_pdf',
            compact('docente', 'asignacion', 'matriculas', 'calificaciones',
                    'periodos', 'esTecnica', 'schoolYear', 'si', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug(
            ($asignacion->asignatura?->nombre ?? 'materia') . '-' . ($asignacion->grupo?->nombre_completo ?? 'grupo')
        );
        return $pdf->download("acta_{$slug}.pdf");
    }

    // ── Acta de calificaciones Excel ──────────────────────────────────────
    public function actaExcel(Asignacion $asignacion)
    {
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero_orden')->get();

        $esTecnica = $asignacion->area === 'tecnica';
        $periodos  = $this->getPeriodos($schoolYear);

        if ($esTecnica) {
            $calificaciones = Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->get()->groupBy(fn($c) => $c->matricula_id . '_' . $c->periodo_id);
        } else {
            $calificaciones = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()->keyBy('matricula_id');
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $headers = ['#', 'Estudiante'];
        if ($esTecnica) {
            foreach ($periodos as $p) $headers[] = 'P' . $p->numero;
        } else {
            $headers[] = 'Nota Final';
        }

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet()->setTitle('Acta');

        $lastCol = chr(64 + count($headers));
        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $inst);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Acta — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . ($asignacion->grupo?->nombre_completo ?? '') . ' — ' . ($schoolYear?->nombre ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65 + $i) . '4', $h);
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray($hdrStyle);

        foreach ($matriculas as $i => $mat) {
            $row = $i + 5;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $mat->estudiante?->nombre_completo ?? '—');
            if ($esTecnica) {
                foreach ($periodos as $j => $p) {
                    $key   = $mat->id . '_' . $p->id;
                    $notas = $calificaciones->get($key, collect());
                    $nota  = is_object($notas) ? $notas->first()?->nota_final : null;
                    $sheet->setCellValue(chr(67 + $j) . $row, $nota !== null ? number_format($nota, 1) : '—');
                    if ($nota !== null && $nota < 70) {
                        $sheet->getStyle(chr(67 + $j) . $row)->getFont()->getColor()->setRGB('dc2626');
                        $sheet->getStyle(chr(67 + $j) . $row)->getFont()->setBold(true);
                    }
                }
            } else {
                $cal  = $calificaciones->get($mat->id);
                $nota = $cal?->nota_final;
                $sheet->setCellValue("C{$row}", $nota !== null ? number_format($nota, 1) : '—');
                if ($nota !== null && $nota < 70) {
                    $sheet->getStyle("C{$row}")->getFont()->getColor()->setRGB('dc2626');
                    $sheet->getStyle("C{$row}")->getFont()->setBold(true);
                }
            }
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'acta_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug(
            ($asignacion->asignatura?->nombre ?? 'materia') . '-' . ($asignacion->grupo?->nombre_completo ?? 'grupo')
        );
        return response()->download($tmp, "acta_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Cuadro de Honor PDF ───────────────────────────────────────────────
    public function rankingPdf(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear = SchoolYear::actual();
        $periodoId  = $request->periodo_id;
        $grupo      = Grupo::with(['grado','seccion'])->findOrFail($request->grupo_id);
        $periodo    = $periodoId ? Periodo::find($periodoId) : null;

        $matriculas = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();
        $calsQuery  = Calificacion::whereIn('matricula_id', $matriculas->pluck('id'))->whereNotNull('nota_final');
        if ($periodoId) $calsQuery->where('periodo_id', $periodoId);
        $allCals = $calsQuery->get()->groupBy('matricula_id');

        $ranking = collect();
        foreach ($matriculas as $m) {
            $cals     = $allCals->get($m->id, collect());
            $promedio = $cals->avg('nota_final');
            $ranking->push([
                'matricula'  => $m,
                'estudiante' => $m->estudiante,
                'promedio'   => $promedio ? round($promedio, 2) : null,
                'materias'   => $cals->count(),
            ]);
        }
        $ranking = $ranking->sortByDesc('promedio')->values();

        $boletinConfig = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.calificaciones.ranking_pdf',
            compact('schoolYear', 'grupo', 'periodo', 'ranking', 'boletinConfig', 'inst')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($grupo->nombre_completo ?? 'grupo');
        return $pdf->download("cuadro_honor_{$slug}.pdf");
    }

    // ── Ranking Excel ─────────────────────────────────────────────────────
    public function rankingExcel(Request $request)
    {
        $request->validate(['grupo_id' => 'required|exists:grupos,id']);

        $schoolYear = SchoolYear::actual();
        $periodoId  = $request->periodo_id;
        $grupo      = Grupo::with(['grado','seccion'])->findOrFail($request->grupo_id);
        $periodo    = $periodoId ? Periodo::find($periodoId) : null;

        $matriculas = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();
        $calsQuery  = Calificacion::whereIn('matricula_id', $matriculas->pluck('id'))->whereNotNull('nota_final');
        if ($periodoId) $calsQuery->where('periodo_id', $periodoId);
        $allCals = $calsQuery->get()->groupBy('matricula_id');

        $ranking = collect();
        foreach ($matriculas as $m) {
            $cals     = $allCals->get($m->id, collect());
            $promedio = $cals->avg('nota_final');
            $ranking->push([
                'estudiante' => $m->estudiante,
                'promedio'   => $promedio ? round($promedio, 2) : null,
                'materias'   => $cals->count(),
            ]);
        }
        $ranking = $ranking->sortByDesc('promedio')->values();

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Ranking');

        $titulo = 'Cuadro de Honor — ' . ($grupo->nombre_completo ?? '') . ($periodo ? ' — ' . $periodo->nombre : '');
        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', $titulo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['Posición', 'Estudiante', 'Cédula', 'Materias', 'Promedio'];
        foreach ($headers as $i => $h) {
            $ws->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '2', $h);
        }
        $ws->getStyle('A2:E2')->getFont()->setBold(true);
        $ws->getStyle('A2:E2')->getFill()
           ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a6e');
        $ws->getStyle('A2:E2')->getFont()->getColor()->setRGB('ffffff');

        foreach ($ranking as $i => $item) {
            $row = $i + 3;
            $prom = $item['promedio'];
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", trim(($item['estudiante']?->apellidos ?? '') . ', ' . ($item['estudiante']?->nombres ?? '')));
            $ws->setCellValue("C{$row}", $item['estudiante']?->cedula ?? '—');
            $ws->setCellValue("D{$row}", $item['materias']);
            $ws->setCellValue("E{$row}", $prom !== null ? $prom : '—');

            if ($prom !== null && $prom >= 90) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d1fae5');
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range(1, 5) as $ci) {
            $ws->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
        }

        $writer = new Xlsx($ss);
        $slug   = \Illuminate\Support\Str::slug($grupo->nombre_completo ?? 'grupo');
        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"cuadro_honor_{$slug}.xlsx\"",
        ]);
    }

    // ── Guardar pesos RA (solo área técnica) ──────────────────────────────
    public function guardarRaPesos(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones,id',
            'pesos'         => 'required|array',
        ]);

        $asignacion = Asignacion::with('asignatura')->findOrFail($request->asignacion_id);

        if ($asignacion->area !== 'tecnica') {
            return response()->json(['success' => false, 'message' => 'Solo disponible para área técnica.'], 403);
        }

        // Access control
        $docente = $this->docenteActual();
        if ($docente && $asignacion->docente_id !== $docente->id) {
            abort(403);
        }

        $total = array_sum(array_map('floatval', $request->pesos));
        if (abs($total - 100) > 0.5) {
            return response()->json([
                'success' => false,
                'message' => "Los pesos deben sumar 100%. Total actual: " . round($total, 1) . "%",
            ], 422);
        }

        // pesos keyed by ra_id
        foreach ($request->pesos as $raId => $peso) {
            ResultadoAprendizaje::where('id', $raId)
                ->where('asignatura_id', $asignacion->asignatura_id)
                ->update(['peso' => round((float) $peso, 2)]);
        }

        // Rebuild peso map to return to JS
        $rasActualizados = $asignacion->asignatura->resultadosAprendizaje()->activos()->orderBy('numero')->get();
        $numRaTotal = $rasActualizados->count();
        $nuevosJs = [];
        foreach ($rasActualizados as $ra) {
            $nuevosJs["ra{$ra->numero}"] = $ra->peso ?? round(100 / $numRaTotal, 4);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Pesos guardados correctamente.',
            'pesos_js' => $nuevosJs,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    public static function calcularNota(array $componentes, $pesos): ?float
    {
        $pesoTotal = 0; $sumaNotas = 0; $hayValores = false;

        foreach ($componentes as $comp => $valor) {
            if ($valor === null || $valor === '') continue;
            $peso = isset($pesos[$comp]) ? (float) $pesos[$comp]->peso : 0;
            if ($peso <= 0) continue;
            $sumaNotas += (float) $valor * $peso;
            $pesoTotal += $peso;
            $hayValores = true;
        }

        if (! $hayValores || $pesoTotal <= 0) return null;
        return round($sumaNotas / $pesoTotal, 2);
    }

    private static function resolverIndicador(?float $nota): ?string
    {
        if ($nota === null) return null;
        if ($nota >= 90) return 'Excelente';
        if ($nota >= 75) return 'Bueno';
        if ($nota >= 60) return 'En proceso';
        return 'Insuficiente';
    }

    // ── Import: show selection / dual-mode form ───────────────────────────
    public function import(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $docente = $this->docenteActual();

        $query = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true);

        if ($docente) {
            $query->where('docente_id', $docente->id);
        }

        $asignaciones = $query->get()
            ->sortBy(fn ($a) => ($a->grupo->grado->nombre ?? '') . ' ' . ($a->grupo->seccion->nombre ?? '') . ' ' . ($a->asignatura->nombre ?? ''));

        $periodos = $this->getPeriodos($schoolYear);

        return view('admin.calificaciones.import', compact('schoolYear', 'asignaciones', 'periodos'));
    }

    // ── Import: download template ─────────────────────────────────────────
    public function downloadTemplate(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $periodoId    = $request->periodo_id;
        $format       = $request->input('format', 'csv');

        $asignacion = $asignacionId ? Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion', 'asignatura.resultadosAprendizaje'])->find($asignacionId) : null;
        $periodo    = $periodoId ? Periodo::find($periodoId) : null;
        $matriculas = collect();

        if ($asignacion) {
            $matriculas = $asignacion->grupo->matriculas()
                ->activas()->with('estudiante')->orderBy('numero_orden')->get();
        }

        $esAcademica = $asignacion && $asignacion->area === 'academica';
        $esTecnica   = $asignacion && $asignacion->area === 'tecnica';

        // Build header based on area type
        if ($esTecnica) {
            $rasCount = $asignacion->asignatura->resultadosAprendizaje()->activos()->count();
            $rasCount = max($rasCount, 5);
            $raCols   = array_map(fn ($n) => "ra{$n}", range(1, $rasCount));
            $headers  = array_merge(['numero_matricula', 'cedula', 'nombres', 'apellidos', 'periodo'], $raCols, ['nota_final']);
        } elseif ($esAcademica) {
            $headers = ['numero_matricula', 'cedula', 'nombres', 'apellidos',
                'p1_comp1', 'p1_comp2', 'p1_comp3', 'p1_comp4',
                'p2_comp1', 'p2_comp2', 'p2_comp3', 'p2_comp4',
                'p3_comp1', 'p3_comp2', 'p3_comp3', 'p3_comp4',
                'p4_comp1', 'p4_comp2', 'p4_comp3', 'p4_comp4',
            ];
        } else {
            $headers = ['numero_matricula', 'cedula', 'nombres', 'apellidos', 'periodo', 'nota_final'];
        }

        $rows = [];
        if ($matriculas->count()) {
            foreach ($matriculas as $mat) {
                if ($esTecnica) {
                    $row = [
                        $mat->numero_matricula ?? '',
                        $mat->estudiante->cedula ?? '',
                        $mat->estudiante->nombres ?? '',
                        $mat->estudiante->apellidos ?? '',
                        $periodo?->numero ?? 1,
                    ];
                    foreach (range(1, $rasCount) as $n) $row[] = '';
                    $row[] = '';
                } elseif ($esAcademica) {
                    $row = [
                        $mat->numero_matricula ?? '',
                        $mat->estudiante->cedula ?? '',
                        $mat->estudiante->nombres ?? '',
                        $mat->estudiante->apellidos ?? '',
                    ];
                    foreach (range(1, 16) as $_) $row[] = '';
                } else {
                    $row = [
                        $mat->numero_matricula ?? '',
                        $mat->estudiante->cedula ?? '',
                        $mat->estudiante->nombres ?? '',
                        $mat->estudiante->apellidos ?? '',
                        $periodo?->numero ?? 1,
                        '',
                    ];
                }
                $rows[] = $row;
            }
        } else {
            if ($esTecnica) {
                $rows[] = array_merge(['2024-00001', '001-1234567-8', 'Juan',  'Pérez',    1], array_fill(0, $rasCount, 85), [85]);
                $rows[] = array_merge(['2024-00002', '001-2345678-9', 'María', 'González', 1], array_fill(0, $rasCount, 92), [92]);
            } elseif ($esAcademica) {
                $rows[] = array_merge(['2024-00001', '001-1234567-8', 'Juan',  'Pérez'],    array_fill(0, 16, 85));
                $rows[] = array_merge(['2024-00002', '001-2345678-9', 'María', 'González'], array_fill(0, 16, 92));
            } else {
                $rows[] = ['2024-00001', '001-1234567-8', 'Juan',  'Pérez',    1, 85];
                $rows[] = ['2024-00002', '001-2345678-9', 'María', 'González', 1, 92];
            }
        }

        $nombreBase = 'plantilla_calificaciones' . ($asignacion ? '_' . \Illuminate\Support\Str::slug($asignacion->asignatura->nombre ?? 'notas') : '');

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Calificaciones');
            $sheet->fromArray([$headers], null, 'A1');
            $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
                ->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
                ]);
            $sheet->freezePane('A2');
            foreach (range(1, count($headers)) as $colIdx) {
                $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx))->setAutoSize(true);
            }
            $sheet->fromArray($rows, null, 'A2');

            // Gray reference columns (nombres, apellidos)
            $refStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f3f4f6']], 'font' => ['color' => ['rgb' => '6b7280']]];
            $lastRow  = count($rows) + 1;
            if ($lastRow > 1) {
                $sheet->getStyle("C2:D{$lastRow}")->applyFromArray($refStyle);
            }

            $writer = new Xlsx($spreadsheet);
            $tmp    = tempnam(sys_get_temp_dir(), 'cal_') . '.xlsx';
            $writer->save($tmp);

            return response()->download($tmp, $nombreBase . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombreBase . '.csv"',
        ]);
    }

    // ── Import: process uploaded file ─────────────────────────────────────
    public function importStore(Request $request)
    {
        $request->validate([
            'archivo'       => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'asignacion_id' => 'required|exists:asignaciones,id',
        ]);

        $asignacion = Asignacion::with(['asignatura', 'grupo'])->findOrFail($request->asignacion_id);
        $periodoId  = $request->periodo_id ?: null;
        $archivo    = $request->file('archivo');
        $ext        = strtolower($archivo->getClientOriginalExtension());

        // ── Read rows ────────────────────────────────────────────────────
        $rows = [];
        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname());
            $sheet       = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $header      = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), null));
            }
        } else {
            $raw      = file_get_contents($archivo->getPathname());
            $encoding = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
            }
            $lines  = array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF"))));
            $lines  = array_values($lines);
            $delim  = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
            $header = array_map('strtolower', array_map('trim', str_getcsv($lines[0] ?? '', $delim)));
            foreach (array_slice($lines, 1) as $line) {
                if (trim($line) === '') continue;
                $cols   = str_getcsv($line, $delim);
                $rows[] = array_combine($header, array_pad($cols, count($header), ''));
            }
        }

        // Pre-load all active matriculas for this group
        $matriculasPorNum    = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->get()->keyBy('numero_matricula');
        $matriculasPorCedula = $matriculasPorNum->groupBy(fn ($m) => $m->estudiante->cedula ?? '');

        $esAcademica = $asignacion->area === 'academica';
        $importados  = 0;
        $omitidos    = 0;
        $errores     = [];

        // Pre-load periods and school year once outside the row loop
        $schoolYear      = SchoolYear::actual();
        $periodosIndexed = Periodo::where('school_year_id', $asignacion->school_year_id)
            ->orderBy('numero')->get()->keyBy('numero');
        $periodoFijo     = $periodoId ? Periodo::find($periodoId) : null;

        foreach ($rows as $i => $row) {
            $linea = $i + 2;

            // Resolve matricula
            $numMat  = trim($row['numero_matricula'] ?? '');
            $cedula  = trim($row['cedula'] ?? '');
            $matricula = null;

            if ($numMat && $matriculasPorNum->has($numMat)) {
                $matricula = $matriculasPorNum->get($numMat);
            } elseif ($cedula && $matriculasPorCedula->has($cedula)) {
                $matricula = $matriculasPorCedula->get($cedula)->first();
            }

            if (! $matricula) {
                $errores[] = "Fila {$linea}: Estudiante no encontrado (matrícula: '{$numMat}', cédula: '{$cedula}').";
                $omitidos++;
                continue;
            }

            if ($esAcademica) {
                // ── Área académica: 4 competencias × 4 períodos ─────────
                $data = [];
                foreach (range(1, 4) as $p) {
                    foreach (range(1, 4) as $c) {
                        $key = "p{$p}_comp{$c}";
                        $val = trim($row[$key] ?? '');
                        if ($val !== '' && is_numeric($val)) {
                            $data["periodo_{$p}_comp{$c}"] = min(100, max(0, (float) $val));
                        }
                    }
                }
                if (! empty($data)) {
                    CalificacionAcademica::updateOrCreate(
                        ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $schoolYear?->id],
                        $data
                    );
                    $importados++;
                } else {
                    $omitidos++;
                }
            } else {
                // ── Área técnica / simple: nota_final por período ────────
                $periodoNum = (int) trim($row['periodo'] ?? $request->periodo_numero ?? 1);
                $periodo    = $periodoFijo ?? $periodosIndexed->get($periodoNum);

                if (! $periodo) {
                    $errores[] = "Fila {$linea}: Período {$periodoNum} no encontrado.";
                    $omitidos++;
                    continue;
                }

                $notaFinal = trim($row['nota_final'] ?? '');
                if ($notaFinal === '' || ! is_numeric($notaFinal)) {
                    $errores[] = "Fila {$linea}: nota_final '{$notaFinal}' no es válida — fila omitida.";
                    $omitidos++;
                    continue;
                }
                $notaFinal = min(100, max(0, (float) $notaFinal));

                Calificacion::updateOrCreate(
                    ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id],
                    ['nota_final' => $notaFinal]
                );
                $importados++;
            }
        }

        $msg = "Se importaron {$importados} nota(s) correctamente.";
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        return back()
            ->with('success', $msg)
            ->with('errores_import', $errores);
    }

    // ── Auditoría de cambios en calificaciones ────────────────────────────
    public function auditoria(Request $request)
    {
        $query = CalificacionAudit::with([
            'user',
            'matricula.estudiante',
            'asignacion.asignatura',
            'asignacion.grupo.grado',
            'asignacion.grupo.seccion',
        ])->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('asignatura')) {
            $query->whereHas('asignacion.asignatura', fn($q) =>
                $q->where('nombre', 'like', '%' . $request->asignatura . '%')
            );
        }
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $audits = $query->paginate(50)->withQueryString();
        $users  = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('admin.calificaciones.auditoria', compact('audits', 'users'));
    }

    // ── Resumen de calificaciones Excel ───────────────────────────────────
    public function resumenExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupos   = Grupo::with(['grado','seccion'])->where('school_year_id', $schoolYear->id)->get();
        $periodos = $this->getPeriodos($schoolYear);
        $grupoId  = $request->grupo_id ?? optional($grupos->first())->id;
        $grupo    = $grupoId ? Grupo::with(['grado','seccion'])->find($grupoId) : null;

        if (! $grupo) abort(404);

        $matriculas   = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();
        $asignaciones = Asignacion::with('asignatura')->where('grupo_id', $grupo->id)->get();

        $allCals = Calificacion::whereIn('matricula_id', $matriculas->pluck('id'))
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->keyBy(fn($c) => "{$c->matricula_id}_{$c->asignacion_id}_{$c->periodo_id}");

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Resumen');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:' . chr(65 + 1 + $asignaciones->count() * $periodos->count()) . '1');
        $sheet->setCellValue('A1', 'RESUMEN DE CALIFICACIONES — ' . $grupo->grado?->nombre . ' ' . $grupo->seccion?->nombre . ' — ' . $schoolYear->nombre);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'Estudiante');
        $col = 2;
        foreach ($asignaciones as $asi) {
            foreach ($periodos as $p) {
                $col++;
                $sheet->setCellValue(chr(65 + $col - 1) . '2', \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '—', 12) . ' P' . $p->numero);
            }
        }
        $lastCol = chr(65 + $col - 1);
        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray($hdrStyle);
        $sheet->getRowDimension(2)->setRowHeight(30);

        foreach ($matriculas as $i => $m) {
            $row = $i + 3;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", trim(($m->estudiante?->apellidos ?? '') . ', ' . ($m->estudiante?->nombres ?? '')));

            $col = 2;
            foreach ($asignaciones as $asi) {
                foreach ($periodos as $p) {
                    $col++;
                    $cal   = $allCals->get("{$m->id}_{$asi->id}_{$p->id}");
                    $nota  = $cal?->nota_final;
                    $sheet->setCellValue(chr(65 + $col - 1) . $row, $nota ?? '');
                    if ($nota !== null && $nota < 60) {
                        $sheet->getStyle(chr(65 + $col - 1) . $row)->getFont()->getColor()->setRGB('dc2626');
                        $sheet->getStyle(chr(65 + $col - 1) . $row)->getFont()->setBold(true);
                    }
                }
            }

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->freezePane('C3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'cal_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($grupo->grado?->nombre . '-' . $grupo->seccion?->nombre);
        return response()->download($tmp, "calificaciones_{$slug}_" . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Resumen de calificaciones PDF ─────────────────────────────────────
    public function resumenPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) abort(404);

        $grupos   = Grupo::with(['grado','seccion'])->where('school_year_id', $schoolYear->id)->get();
        $periodos = $this->getPeriodos($schoolYear);
        $grupoId  = $request->grupo_id ?? optional($grupos->first())->id;
        $grupo    = $grupoId ? Grupo::with(['grado','seccion'])->find($grupoId) : null;

        if (! $grupo) abort(404);

        $matriculas   = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();
        $asignaciones = Asignacion::with('asignatura')->where('grupo_id', $grupo->id)->get();

        $allCals = Calificacion::whereIn('matricula_id', $matriculas->pluck('id'))
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->keyBy(fn($c) => "{$c->matricula_id}_{$c->asignacion_id}_{$c->periodo_id}");

        $matrix = [];
        foreach ($matriculas as $m) {
            foreach ($asignaciones as $asi) {
                foreach ($periodos as $p) {
                    $matrix[$m->id][$asi->id][$p->id] = $allCals->get("{$m->id}_{$asi->id}_{$p->id}");
                }
            }
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.calificaciones.resumen_pdf',
            compact('schoolYear', 'grupo', 'grupos', 'periodos', 'matriculas', 'asignaciones', 'matrix', 'inst')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($grupo->grado?->nombre . '-' . $grupo->seccion?->nombre);
        return $pdf->download("resumen_calificaciones_{$slug}.pdf");
    }
}
