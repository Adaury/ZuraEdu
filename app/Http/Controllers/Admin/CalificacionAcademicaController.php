<?php

namespace App\Http\Controllers\Admin;

use App\Events\GradePublished;
use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\CalificacionAudit;
use App\Models\Docente;
use App\Models\IndicadorAprendizaje;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Mail\BoletinDisponible;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CalificacionAcademicaController extends Controller
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

    private static function resolverIndicador(?float $nota): ?string
    {
        if ($nota === null) return null;
        if ($nota >= 90) return 'Excelente';
        if ($nota >= 75) return 'Bueno';
        if ($nota >= 60) return 'En proceso';
        return 'Insuficiente';
    }

    // ── Planilla Académica: vista de competencias por período ─────────────
    public function planillaAcademica(Request $request)
    {
        $request->validate(['asignacion_id' => 'required|exists:asignaciones,id']);

        $asignacion = Asignacion::with([
            'grupo.matriculas.estudiante',
            'asignatura',
            'docente',
        ])->findOrFail($request->asignacion_id);

        $docente = $this->docenteActual();
        if ($docente && $asignacion->docente_id !== $docente->id) {
            abort(403);
        }

        $schoolYear = SchoolYear::actual();

        $matriculas = $asignacion->grupo
            ->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();

        $existentes = CalificacionAcademica::where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->get()
            ->keyBy('matricula_id');

        $registros = [];
        foreach ($matriculas as $m) {
            $registros[$m->id] = $existentes->get($m->id) ?? new CalificacionAcademica([
                'matricula_id'   => $m->id,
                'asignacion_id'  => $asignacion->id,
                'school_year_id' => $schoolYear->id,
            ]);
        }

        $periodos = Periodo::where('school_year_id', $schoolYear->id)
            ->orderBy('numero')->get();

        $gradoId = $asignacion->grupo->grado_id;
        $indicadoresPorPeriodo = IndicadorAprendizaje::where('asignatura_id', $asignacion->asignatura_id)
            ->where('grado_id', $gradoId)
            ->where('activo', true)
            ->orderBy('periodo_numero')
            ->orderBy('orden')
            ->get()
            ->groupBy('periodo_numero');

        return view('admin.calificaciones.planilla_academica', compact(
            'asignacion', 'matriculas', 'registros', 'schoolYear', 'periodos', 'indicadoresPorPeriodo'
        ));
    }

    // ── Guardar académica: AJAX save for full-year planilla ───────────────
    public function guardarAcademica(Request $request)
    {
        $request->validate([
            'asignacion_id'  => 'required|exists:asignaciones,id',
            'school_year_id' => 'required|exists:school_years,id',
            'notas'          => 'required|array',
        ]);

        $saved = 0;

        foreach ($request->notas as $matriculaId => $datos) {
            $rec = [];

            // ── Competencias, RP y promedios ─────────────────────────────
            foreach ([1, 2, 3, 4] as $c) {
                $vals = [];
                foreach ([1, 2, 3, 4] as $p) {
                    $pKey   = "comp{$c}_p{$p}";
                    $rKey   = "comp{$c}_r{$p}";
                    $avgKey = "avg_comp{$c}_p{$p}";

                    $pv = isset($datos[$pKey]) && $datos[$pKey] !== '' ? (float) $datos[$pKey] : null;
                    $rv = isset($datos[$rKey]) && $datos[$rKey] !== '' ? (float) $datos[$rKey] : null;

                    $rec[$pKey] = $pv;
                    $rec[$rKey] = $rv;

                    // CF = P + min(R, 100-P) si R existe y P < 70
                    $cf = null;
                    if ($pv !== null) {
                        if ($rv !== null && $pv < 70) {
                            $maxR = max(0.0, 100.0 - $pv);
                            $cf   = round($pv + min($rv, $maxR), 2);
                        } else {
                            $cf = round($pv, 2);
                        }
                    }
                    $rec[$avgKey] = $cf;
                    if ($cf !== null) $vals[] = $cf;
                }
                $rec["prom_comp{$c}"] = count($vals) > 0
                    ? round(array_sum($vals) / count($vals), 2) : null;
            }

            // ── Nota final = promedio de las 4 competencias ───────────────
            $proms = array_filter([
                $rec['prom_comp1'], $rec['prom_comp2'],
                $rec['prom_comp3'], $rec['prom_comp4'],
            ], fn ($v) => $v !== null);
            $notaFinal = count($proms) > 0 ? round(array_sum($proms) / count($proms), 2) : null;
            $rec['nota_final'] = $notaFinal;

            // ── Completivo: 50% NF + 50% CC ──────────────────────────────
            $cc = isset($datos['nota_cc']) && $datos['nota_cc'] !== '' ? (float) $datos['nota_cc'] : null;
            $rec['nota_cc']        = $cc;
            $rec['nota_completiva'] = ($notaFinal !== null && $cc !== null)
                ? round(0.5 * $notaFinal + 0.5 * $cc, 2) : null;

            // ── Extraordinario: 30% NF + 70% CE ──────────────────────────
            $ce = isset($datos['nota_ce']) && $datos['nota_ce'] !== '' ? (float) $datos['nota_ce'] : null;
            $rec['nota_ce']            = $ce;
            $rec['nota_extraordinaria'] = ($notaFinal !== null && $ce !== null)
                ? round(0.3 * $notaFinal + 0.7 * $ce, 2) : null;

            // ── Evaluación Especial ────────────────────────────────────────
            $rec['eval_cf'] = isset($datos['eval_cf']) && $datos['eval_cf'] !== ''
                ? (float) $datos['eval_cf'] : null;
            $rec['eval_ce'] = isset($datos['eval_ce']) && $datos['eval_ce'] !== ''
                ? (float) $datos['eval_ce'] : null;

            // ── Asistencia por período ─────────────────────────────────────
            $totalAsist = 0; $totalClases = 0;
            foreach ([1, 2, 3, 4] as $p) {
                $a = isset($datos["asist_p{$p}"]) && $datos["asist_p{$p}"] !== ''
                    ? (int) $datos["asist_p{$p}"] : null;
                $t = isset($datos["clases_p{$p}"]) && $datos["clases_p{$p}"] !== ''
                    ? (int) $datos["clases_p{$p}"] : null;
                $rec["asist_p{$p}"]  = $a;
                $rec["clases_p{$p}"] = $t;
                if ($a !== null) $totalAsist  += $a;
                if ($t !== null) $totalClases += $t;
            }
            $rec['pct_asistencia'] = $totalClases > 0
                ? round($totalAsist / $totalClases * 100, 2) : null;

            $rec['observaciones']  = isset($datos['observaciones']) && $datos['observaciones'] !== ''
                ? $datos['observaciones'] : null;
            $rec['indicador'] = self::resolverIndicador($notaFinal);
            // Situación: usar la mejor nota disponible (extraordinaria > completiva > final)
            $gradeFinal = $rec['nota_extraordinaria'] ?? $rec['nota_completiva'] ?? $notaFinal;
            $rec['situacion'] = $gradeFinal !== null ? ($gradeFinal >= 70 ? 'A' : 'R') : null;
            $rec['modificado_por'] = auth()->id();

            // Auditoría: capturar registro anterior antes de guardar
            $anterior = CalificacionAcademica::where([
                'matricula_id'   => $matriculaId,
                'asignacion_id'  => $request->asignacion_id,
                'school_year_id' => $request->school_year_id,
            ])->first();

            $camposAudit = [];
            foreach ([1,2,3,4] as $c) {
                foreach ([1,2,3,4] as $p) {
                    $camposAudit[] = "comp{$c}_p{$p}";
                }
            }
            $camposAudit[] = 'nota_final';

            CalificacionAudit::registrarCambios(
                'CalificacionAcademica',
                $anterior,
                $rec,
                (int) $matriculaId,
                (int) $request->asignacion_id,
                $camposAudit
            );

            CalificacionAcademica::updateOrCreate(
                [
                    'matricula_id'   => $matriculaId,
                    'asignacion_id'  => $request->asignacion_id,
                    'school_year_id' => $request->school_year_id,
                ],
                $rec
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "Guardadas {$saved} calificaciones.",
            'saved'   => $saved,
        ]);
    }

    // ── Publicar académica ────────────────────────────────────────────────
    public function publicarAcademica(Request $request)
    {
        $request->validate([
            'asignacion_id'  => 'required|exists:asignaciones,id',
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        $count = CalificacionAcademica::where('asignacion_id', $request->asignacion_id)
            ->where('school_year_id', $request->school_year_id)->count();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No hay calificaciones para publicar.'], 422);
        }

        $primera     = CalificacionAcademica::where('asignacion_id', $request->asignacion_id)
            ->where('school_year_id', $request->school_year_id)->first();
        $nuevoEstado = ! $primera->publicado;

        CalificacionAcademica::where('asignacion_id', $request->asignacion_id)
            ->where('school_year_id', $request->school_year_id)
            ->update(['publicado' => $nuevoEstado]);

        if ($nuevoEstado) {
            $asignacion = Asignacion::with(['asignatura', 'grupo'])->find($request->asignacion_id);
            if ($asignacion) {
                $materiaNombre = $asignacion->asignatura->nombre ?? 'una materia';
                $matriculas = Matricula::with(['estudiante.representantes'])
                    ->where('grupo_id', $asignacion->grupo_id)
                    ->where('estado', 'activa')
                    ->get();

                $calMap = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                    ->where('school_year_id', $request->school_year_id)
                    ->whereIn('matricula_id', $matriculas->pluck('id'))
                    ->pluck('nota_final', 'matricula_id');

                foreach ($matriculas as $matricula) {
                    $estudiante = $matricula->estudiante;
                    if (! $estudiante) continue;

                    $nota = $calMap[$matricula->id] ?? 0;
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
        }

        // ── Email a representantes y estudiantes ──────────────────────────
        if ($nuevoEstado && isset($asignacion) && $asignacion) {
            $periodoActual = Periodo::where('school_year_id', $request->school_year_id)
                ->orderBy('numero')->first();
            if ($periodoActual && isset($matriculas)) {
                foreach ($matriculas as $mat) {
                    $est = $mat->estudiante;
                    if (! $est) continue;
                    foreach ($est->representantes as $rep) {
                        if ($rep->email) {
                            try {
                                Mail::to($rep->email)->queue(
                                    new BoletinDisponible($est, $periodoActual, route('portal.representante', $est->id))
                                );
                            } catch (\Throwable $e) {}
                        }
                    }
                    if ($est->user?->email) {
                        try {
                            Mail::to($est->user->email)->queue(
                                new BoletinDisponible($est, $periodoActual, route('portal.estudiante.boletin'))
                            );
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // Notificación in-app cuando se publican (no al despublicar)
        if ($nuevoEstado && isset($asignacion) && $asignacion && isset($matriculas)) {
            try {
                $materia = $asignacion->asignatura->nombre ?? 'una materia';
                $titulo  = '📊 Calificaciones publicadas';
                $mensaje = "Las calificaciones de {$materia} ya están disponibles en tu portal.";
                foreach ($matriculas as $mat) {
                    $est = $mat->estudiante;
                    if (!$est) continue;
                    if ($est->user_id) {
                        Notificacion::enviar($est->user_id, 'academica', $titulo, $mensaje);
                    }
                    foreach ($est->representantes as $rep) {
                        if ($rep->user_id) {
                            Notificacion::enviar($rep->user_id, 'academica', $titulo, $mensaje);
                        }
                    }
                }
            } catch (\Throwable) {}
        }

        if ($nuevoEstado && isset($asignacion) && $asignacion) {
            try {
                GradePublished::dispatch(
                    $asignacion->grupo_id,
                    'Planilla Anual',
                    $asignacion->asignatura?->nombre ?? 'Asignatura',
                );
            } catch (\Throwable) {}
        }

        $msg = $nuevoEstado
            ? "Planilla publicada ({$count} registros)."
            : "Planilla despublicada ({$count} registros).";

        return response()->json(['success' => true, 'publicado' => $nuevoEstado, 'message' => $msg]);
    }

    // ── Exportar Planilla Académica – PDF ─────────────────────────────────
    public function exportarPlanillaPdf(Request $request)
    {
        $asignacion = Asignacion::with(['asignatura','grupo.grado','grupo.seccion','docente'])
            ->findOrFail($request->asignacion_id);
        $schoolYear = SchoolYear::actual();

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        $registros = CalificacionAcademica::where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->keyBy('matricula_id');

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'admin.calificaciones.planilla_pdf',
                compact('asignacion', 'schoolYear', 'matriculas', 'registros')
            )->setPaper('a4', 'landscape');
            return $pdf->download('planilla_' . $asignacion->asignatura->nombre . '.pdf');
        }

        return view('admin.calificaciones.planilla_pdf',
            compact('asignacion', 'schoolYear', 'matriculas', 'registros'));
    }

    // ── Exportar Planilla Académica – CSV/Excel ───────────────────────────
    public function exportarPlanillaExcel(Request $request)
    {
        $asignacion = Asignacion::with(['asignatura','grupo.grado','grupo.seccion','docente'])
            ->findOrFail($request->asignacion_id);
        $schoolYear = SchoolYear::actual();

        $matriculas = $asignacion->grupo->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        $registros = CalificacionAcademica::where('asignacion_id', $asignacion->id)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->keyBy('matricula_id');

        $filename = 'planilla_' . \Illuminate\Support\Str::slug($asignacion->asignatura->nombre) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($matriculas, $registros, $asignacion, $schoolYear) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [\App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'))]);
            fputcsv($out, ['Planilla de Calificaciones — Área Académica']);
            fputcsv($out, ['Asignatura:', $asignacion->asignatura->nombre, 'Grupo:', $asignacion->grupo->nombre_completo, 'Año:', $schoolYear->nombre]);
            fputcsv($out, ['Docente:', $asignacion->docente?->nombre_completo ?? '—']);
            fputcsv($out, []);

            $cols = ['#', 'Estudiante'];
            foreach ([1,2,3,4] as $c) {
                foreach ([1,2,3,4] as $p) {
                    $cols[] = "C{$c}_P{$p}";
                }
            }
            foreach ([1,2,3,4] as $i) {
                $cols[] = "PromComp{$i}";
            }
            $cols = array_merge($cols, [
                'Cal.Final',
                'Nota_CC', '50%CF', 'Nota_CE', '50%CE',
                'Nota_Completiva', '30%CF', 'Nota_Extraordinaria', '70%CEX',
                'Eval_CF', 'Eval_CE', 'Situacion',
                'Asist_P1', 'Asist_P2', 'Asist_P3', 'Asist_P4', '%Asistencia',
            ]);
            fputcsv($out, $cols);

            $i = 1;
            foreach ($matriculas as $m) {
                $r   = $registros[$m->id] ?? null;
                $row = [$i++, $m->estudiante->nombre_completo];

                foreach ([1,2,3,4] as $c) {
                    foreach ([1,2,3,4] as $p) {
                        $row[] = $r ? ($r->{"comp{$c}_p{$p}"} ?? '') : '';
                    }
                }
                foreach ([1,2,3,4] as $ci) {
                    $row[] = $r ? ($r->{"prom_comp{$ci}"} ?? '') : '';
                }

                $row[] = $r?->nota_final ?? '';
                $row[] = $r?->nota_cc ?? '';
                $row[] = $r?->nota_cc !== null ? round($r->nota_cc * 0.5, 2) : '';
                $row[] = $r?->nota_ce ?? '';
                $row[] = $r?->nota_ce !== null ? round($r->nota_ce * 0.5, 2) : '';
                $row[] = $r?->nota_completiva ?? '';
                $row[] = $r?->nota_final !== null ? round($r->nota_final * 0.3, 2) : '';
                $row[] = $r?->nota_extraordinaria ?? '';
                $row[] = $r?->nota_extraordinaria !== null ? round($r->nota_extraordinaria * 0.7, 2) : '';
                $row[] = $r?->eval_cf ?? '';
                $row[] = $r?->eval_ce ?? '';
                $row[] = $r?->situacion ?? '';
                foreach ([1,2,3,4] as $p) {
                    $row[] = $r?->{"asist_p{$p}"} ?? '';
                }
                $row[] = $r?->pct_asistencia ?? '';

                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
