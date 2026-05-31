<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Encuesta;
use App\Models\Planificacion;
use App\Models\Calificacion;
use App\Models\Comunicado;
use App\Models\Estudiante;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\RespuestaEncuesta;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\InsigniaEstudiante;
use App\Models\PuntoEstudiante;
use App\Models\ProyectoEscolar;
use App\Models\Reconocimiento;
use App\Models\Representante;
use App\Models\Pago;
use App\Models\PlanEvaluacionPeriodo;
use App\Models\InstrumentoEvaluacion;
use App\Models\SchoolYear;
use App\Models\SolicitudRepresentante;
use App\Services\CardNetService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalPadreController extends Controller
{
    private function getRepresentante()
    {
        $rep = Representante::where('user_id', auth()->id())->first();

        if (! $rep) {
            abort(403, 'No tienes un perfil de representante asociado a esta cuenta.');
        }

        return $rep;
    }

    // ── Dashboard — lista de hijos ───────────────────────────────────────
    public function dashboard()
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();
        $syId          = $schoolYear?->id;
        $tid           = tenant_id() ?? 0;
        $userId        = auth()->id();

        // Hijos con matrícula activa
        $hijosBase = $representante->estudiantes()
            ->with([
                'matriculas' => function ($q) use ($syId) {
                    $q->with(['grupo.grado', 'grupo.seccion'])
                      ->where('estado', 'activa')
                      ->when($syId, fn($q) => $q->where('school_year_id', $syId));
                },
            ])
            ->get();

        // Recopilar todos los matricula_ids de hijos activos — 1 sola vez
        $matriculaIds = $hijosBase->flatMap(fn($e) => $e->matriculas->pluck('id'))->filter()->unique()->values();

        // Bulk-load calificaciones, calificacionesAcademicas y asistencias para TODOS los hijos
        $califsPorMatricula = $matriculaIds->isNotEmpty()
            ? Calificacion::whereIn('matricula_id', $matriculaIds)
                ->where('publicado', true)
                ->whereNotNull('nota_final')
                ->get()
                ->groupBy('matricula_id')
            : collect();

        $califsAcadPorMatricula = ($matriculaIds->isNotEmpty() && $syId)
            ? CalificacionAcademica::whereIn('matricula_id', $matriculaIds)
                ->where('school_year_id', $syId)
                ->whereNotNull('nota_final')
                ->get()
                ->groupBy('matricula_id')
            : collect();

        $asistenciaStats = $matriculaIds->isNotEmpty()
            ? Asistencia::whereIn('matricula_id', $matriculaIds)
                ->selectRaw('matricula_id, COUNT(*) as total, SUM(estado = "ausente") as ausentes')
                ->groupBy('matricula_id')
                ->get()
                ->keyBy('matricula_id')
            : collect();

        // Gamificación bulk (1 query) — solo si el módulo está activo
        $gamificacionActiva  = tenant_can('gamificacion');
        $puntosPorMatriculaDash = collect();
        $insigniasPorMatriculaDash = collect();
        if ($gamificacionActiva && $matriculaIds->isNotEmpty()) {
            $puntosPorMatriculaDash = PuntoEstudiante::whereIn('matricula_id', $matriculaIds)
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');
            $insigniasPorMatriculaDash = InsigniaEstudiante::whereIn('matricula_id', $matriculaIds)
                ->selectRaw('matricula_id, COUNT(*) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');
        }

        $hijos = $hijosBase->map(function ($estudiante) use ($califsPorMatricula, $califsAcadPorMatricula, $asistenciaStats, $gamificacionActiva, $puntosPorMatriculaDash, $insigniasPorMatriculaDash) {
            $matricula       = $estudiante->matriculas->first();
            $promedioGeneral = null;
            $alertas         = [];

            if ($matricula) {
                $califs     = $califsPorMatricula->get($matricula->id, collect())->pluck('nota_final')->filter();
                $califsAcad = $califsAcadPorMatricula->get($matricula->id, collect())->pluck('nota_final')->filter();
                $todas      = $califs->merge($califsAcad);

                $promedioGeneral = $todas->count() ? round($todas->avg(), 1) : null;

                $bajas = $califs->filter(fn($n) => $n < 60)->count()
                       + $califsAcad->filter(fn($n) => $n < 60)->count();
                if ($bajas > 0) {
                    $alertas[] = ['tipo' => 'rendimiento', 'texto' => "{$bajas} materia(s) con nota menor a 60"];
                }

                $stat = $asistenciaStats->get($matricula->id);
                if ($stat && $stat->total > 0) {
                    $pct = ($stat->ausentes / $stat->total) * 100;
                    if ($pct > 20) {
                        $alertas[] = ['tipo' => 'asistencia', 'texto' => number_format($pct, 1) . '% de ausencias registradas'];
                    }
                }
            }

            $estudiante->_matricula = $matricula;
            $estudiante->_promedio  = $promedioGeneral;
            $estudiante->_alertas   = $alertas;
            $estudiante->_puntos    = $gamificacionActiva && $matricula ? (int) ($puntosPorMatriculaDash->get($matricula->id, 0)) : null;
            $estudiante->_insignias = $gamificacionActiva && $matricula ? (int) ($insigniasPorMatriculaDash->get($matricula->id, 0)) : null;

            return $estudiante;
        });

        $notificaciones = Notificacion::where('user_id', $userId)
            ->noLeidas()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalNoLeidas = $notificaciones->count();

        $comunicados = \Illuminate\Support\Facades\Cache::remember(
            "t{$tid}_comunicados_recientes", 600,
            fn() => Comunicado::publicados()->orderByDesc('published_at')->limit(4)->get()
        );

        $eventosCalendario = ($syId)
            ? \App\Models\CalendarioAcademico::where('school_year_id', $syId)
                ->where('activo', true)
                ->where('fecha_inicio', '>=', today())
                ->orderBy('fecha_inicio')
                ->limit(5)
                ->get()
            : collect();

        return view('portal.padre.dashboard', compact(
            'representante', 'hijos', 'schoolYear',
            'notificaciones', 'totalNoLeidas', 'comunicados',
            'eventosCalendario', 'gamificacionActiva'
        ));
    }

    // ── Detalle de un hijo ───────────────────────────────────────────────
    public function hijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        // Verificar que este estudiante sea hijo del representante
        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403, 'No tienes acceso a la información de este estudiante.');

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        // Calificaciones
        $calificaciones = collect();
        $calificacionesAcademicas = collect();

        if ($matricula) {
            $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
                ->where('matricula_id', $matricula->id)
                ->where('publicado', true)
                ->get()
                ->groupBy('periodo_id');

            $calificacionesAcademicas = CalificacionAcademica::with('asignacion.asignatura')
                ->where('matricula_id', $matricula->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->whereNotNull('nota_final')   // mostrar si tiene nota, sin importar publicado
                ->get();
        }

        // Asistencia resumen
        $resumenAsistencia = $this->calcularAsistencia($matricula);

        // Horario
        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        // Observaciones (no privadas)
        $observaciones = Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
            ->delEstudiante($estudiante->id)
            ->publicas()
            ->orderByDesc('created_at')
            ->get();

        // Asignaciones del grupo
        $asignaciones = $matricula
            ? Asignacion::with(['asignatura', 'docente'])
                ->where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('activo', true)
                ->get()
            : collect();

        // Planificaciones técnicas publicadas
        $planificaciones = collect();
        if ($matricula) {
            $asignacionIds = Asignacion::where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('area', 'tecnica')
                ->pluck('id');

            $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente', 'raItems', 'actividades'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->latest()
                ->get()
                ->groupBy('asignacion_id');
        }

        // Pagos (solo si módulo activo)
        $pagosHijo    = collect();
        $resumenPagos = null;
        if ($matricula && \App\Models\ConfigInstitucional::moduloActivo('pagos')) {
            \App\Models\Pago::sincronizarVencidos();
            $pagosHijo = \App\Models\Pago::where('matricula_id', $matricula->id)
                ->latest('fecha_vencimiento')->get();
            $resumenPagos = [
                'pagado'    => $pagosHijo->where('estado', 'pagado')->sum('monto'),
                'pendiente' => $pagosHijo->whereIn('estado', ['pendiente', 'vencido'])->sum('monto'),
                'vencido'   => $pagosHijo->where('estado', 'vencido')->count(),
            ];
        }

        // ── Comparativo de notas por período (para gráfica Chart.js) ──
        $comparativoChart = $this->calcularComparativoPeriodos($matricula, $periodos, $calificaciones, $calificacionesAcademicas);

        // ── Gamificación del hijo ──
        $gamificacionActiva = tenant_can('gamificacion');
        $totalPuntosHijo   = 0;
        $insigniasHijo     = collect();
        $posicionHijo      = null;

        if ($matricula && $gamificacionActiva) {
            $totalPuntosHijo = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
            $insigniasHijo   = InsigniaEstudiante::where('matricula_id', $matricula->id)->get()->keyBy('tipo');

            // Bulk-load puntos del grupo con 1 sola query GROUP BY
            $grupoMatIds = Matricula::where('grupo_id', $matricula->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->pluck('id');

            $puntosPorMatricula = PuntoEstudiante::whereIn('matricula_id', $grupoMatIds)
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')
                ->pluck('total', 'matricula_id');

            $todosDelGrupo = $grupoMatIds
                ->map(fn($id) => ['id' => $id, 'total' => $puntosPorMatricula->get($id, 0)])
                ->sortByDesc('total')->values();
            $idx = $todosDelGrupo->search(fn($r) => $r['id'] === $matricula->id);
            $posicionHijo = $idx !== false ? $idx + 1 : null;
        }

        $reconocimientosCount = Reconocimiento::where('estudiante_id', $estudiante->id)->count();
        $proyectosCount = ProyectoEscolar::whereHas('integrantes', fn($q) => $q->where('estudiante_id', $estudiante->id))->count();

        return view('portal.padre.hijo', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear', 'periodos',
            'calificaciones', 'calificacionesAcademicas',
            'resumenAsistencia', 'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig',
            'observaciones', 'planificaciones', 'asignaciones',
            'pagosHijo', 'resumenPagos', 'comparativoChart',
            'gamificacionActiva', 'totalPuntosHijo', 'insigniasHijo', 'posicionHijo',
            'reconocimientosCount', 'proyectosCount'
        ));
    }

    // ── Historial completo de notificaciones ────────────────────────────
    public function notificaciones()
    {
        $notificaciones = \App\Models\Notificacion::where('user_id', auth()->id())
            ->latest()->paginate(30);

        \App\Models\Notificacion::where('user_id', auth()->id())
            ->noLeidas()->update(['leida' => true, 'leida_en' => now()]);
        Cache::put('user_' . auth()->id() . '_notif_unread', 0, 15);

        return view('portal.notificaciones', compact('notificaciones'));
    }

    // ── Boletín imprimible del hijo ──────────────────────────────────────
    public function boletin(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403, 'No tienes acceso a la información de este estudiante.');

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        if (! $matricula) {
            return back()->with('error', 'El estudiante no tiene una matrícula activa.');
        }

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()
            ->groupBy('asignacion_id');

        $calificacionesAcademicas = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->get()
            ->keyBy('asignacion_id');

        $resumenAsistencia = $this->calcularAsistencia($matricula);
        $minerdData        = $this->buildMinerdData($matricula, $schoolYear);

        // Promedio general
        $notasTecnicas  = $calificaciones->flatten()->pluck('nota_final')->filter();
        $notasAcademicas = $calificacionesAcademicas->pluck('nota_final')->filter();
        $todasNotas     = $notasTecnicas->merge($notasAcademicas);
        $promedioGeneral = $todasNotas->count() ? round($todasNotas->avg(), 1) : null;

        // Ranking en el grupo
        $rankingGrupo = null;
        if ($matricula && $periodos->isNotEmpty()) {
            $ultimoPeriodo = $periodos->last();
            $boletinCtrl   = app(\App\Http\Controllers\Admin\BoletinController::class);
            try {
                $rankingGrupo = $boletinCtrl->buildBoletinDataPublic($matricula, $ultimoPeriodo)['rankingGrupo'] ?? null;
            } catch (\Exception $e) { }
        }

        return view('portal.padre.boletin', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear', 'periodos',
            'calificaciones', 'calificacionesAcademicas', 'resumenAsistencia',
            'promedioGeneral', 'rankingGrupo', 'minerdData'
        ));
    }

    // ── Constancia de matrícula del hijo ─────────────────────────────────
    public function constancia(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear', 'estudiante.representantes'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.matriculas.constancia_pdf',
            compact('matricula', 'config', 'si', 'dir', 'cod')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("constancia_{$slug}.pdf");
    }

    // ── Estado de cuenta (HTML) del hijo ────────────────────────────────
    public function estadoCuenta(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        if (! \App\Models\ConfigInstitucional::moduloActivo('pagos')) abort(404);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'estudiante.representantes',
                    'pagos' => fn($q) => $q->orderBy('fecha_vencimiento')])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        \App\Models\Pago::sincronizarVencidos();

        $sy  = $schoolYear;
        $mon = \App\Helpers\Setting::get('payments_currency', 'DOP');

        $totales = [
            'pagado'    => $matricula->pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $matricula->pagos->where('estado', 'pendiente')->sum('monto'),
            'vencido'   => $matricula->pagos->where('estado', 'vencido')->sum('monto'),
            'total'     => $matricula->pagos->sum('monto'),
        ];

        return view('portal.padre.estado_cuenta', compact(
            'estudiante', 'matricula', 'sy', 'mon', 'totales'
        ));
    }

    // ── Estado de cuenta PDF del hijo ────────────────────────────────────
    public function estadoCuentaPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        if (! \App\Models\ConfigInstitucional::moduloActivo('pagos')) abort(404);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'estudiante.representantes',
                    'pagos' => fn($q) => $q->orderBy('fecha_vencimiento')])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        \App\Models\Pago::sincronizarVencidos();

        $sy     = $schoolYear;
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $mon    = \App\Helpers\Setting::get('payments_currency', 'DOP');

        $totales = [
            'pagado'    => $matricula->pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $matricula->pagos->whereIn('estado', ['pendiente', 'vencido'])->sum('monto'),
            'total'     => $matricula->pagos->sum('monto'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.estado_cuenta_pdf',
            compact('matricula', 'inst', 'config', 'mon', 'totales', 'sy')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("estado_cuenta_{$slug}.pdf");
    }

    // ── Pago en línea de cuota del hijo ─────────────────────────────────
    public function iniciarPagoHijo(Estudiante $estudiante, Pago $pago)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $matricula = $estudiante->matriculas()->where('id', $pago->matricula_id)->first();
        abort_if(! $matricula, 403);
        abort_if(! in_array($pago->estado, ['pendiente', 'vencido']), 422, 'Este pago ya no puede procesarse en línea.');

        $metadata = ['pago_id' => $pago->id, 'origen' => 'portal_padre'];

        if (PaymentService::isStripe()) {
            $appUrl = config('app.url');
            $result = PaymentService::createCheckout(
                $pago->concepto,
                (float) $pago->monto,
                $metadata,
                "{$appUrl}/stripe/pago-ok?session_id={CHECKOUT_SESSION_ID}",
                "{$appUrl}/stripe/pago-cancelado?origen=portal_padre",
            );

            if (! $result) {
                return back()->with('error', 'El pago en línea no está disponible. Contacta la administración.');
            }

            return redirect($result['url']);
        }

        // CardNet
        if (! CardNetService::isConfigured()) {
            return back()->with('error', 'El pago en línea no está configurado. Contacta la administración.');
        }

        $orderId = 'P' . str_pad($pago->id, 11, '0', STR_PAD_LEFT);
        $result  = CardNetService::createCheckoutParams($orderId, (float) $pago->monto, $metadata);
        $token   = \Illuminate\Support\Str::random(32);
        cache()->put("cardnet_form_{$token}", $result, now()->addMinutes(20));

        return redirect()->route('cardnet.checkout', $token);
    }

    // ── PDF boletín del hijo ─────────────────────────────────────────────
    public function boletinPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403);

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = \App\Models\Calificacion::with(['asignacion.asignatura'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)->get()->groupBy('asignacion_id');

        $calificacionesAcademicas = \App\Models\CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('publicado', true)->whereNotNull('nota_final')->get()->keyBy('asignacion_id');

        $asignaciones = collect()->merge(
            $calificaciones->map(fn($g) => $g->first()?->asignacion)->filter()
        )->merge(
            $calificacionesAcademicas->map(fn($c) => $c->asignacion)->filter()
        )->unique('id');

        $tablaNotas = [];
        foreach ($asignaciones as $asi) {
            $esTecnica = $asi->area === 'tecnica';
            $periodosData = []; $notasValidas = [];
            if ($esTecnica) {
                $calsPorPeriodo = $calificaciones->get($asi->id, collect())->keyBy('periodo_id');
                foreach ($periodos as $p) {
                    $n = $calsPorPeriodo->get($p->id)?->nota_final;
                    $periodosData[$p->id] = $n;
                    if ($n !== null) $notasValidas[] = $n;
                }
                $promedio  = count($notasValidas) ? round(array_sum($notasValidas) / count($notasValidas), 2) : null;
                $situacion = $promedio !== null ? ($promedio >= 70 ? 'A' : 'R') : null;
            } else {
                $cal = $calificacionesAcademicas->get($asi->id);
                foreach ($periodos as $p) {
                    $n = $p->numero; $vals = [];
                    for ($ci = 1; $ci <= 4; $ci++) {
                        $pb = $cal?->{"comp{$ci}_p{$n}"};
                        if ($pb !== null) {
                            $rv = $cal?->{"comp{$ci}_r{$n}"};
                            $pb = (float)$pb;
                            $cv = ($rv !== null && $pb < 70) ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2) : round($pb, 2);
                            $vals[] = $cv;
                        }
                    }
                    $periodosData[$p->id] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
                }
                $promedio  = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                $situacion = $cal?->situacion;
            }
            $tablaNotas[] = ['asignatura' => $asi->asignatura?->nombre ?? '—', 'esTecnica' => $esTecnica, 'periodos' => $periodosData, 'promedio' => $promedio, 'situacion' => $situacion];
        }

        $boletinConfig = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $data = compact('matricula', 'periodos', 'tablaNotas', 'schoolYear', 'boletinConfig');
        $data['asistencias'] = collect();
        $data['minerdData']  = $this->buildMinerdData($matricula, $schoolYear);
        $data['ciclo']       = $matricula->grupo?->grado?->ciclo ?? null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)->setPaper('letter', 'portrait');
        $apellidos = \Illuminate\Support\Str::slug($estudiante->apellidos ?? 'boletin');
        return $pdf->download("boletin_{$apellidos}.pdf");
    }

    // ── Helper: datos MINERD CE/IL para el boletín ───────────────────────
    private function buildMinerdData(?Matricula $matricula, ?SchoolYear $schoolYear): ?array
    {
        if (!$matricula || !$schoolYear) return null;
        $ciclo = $matricula->grupo?->grado?->ciclo ?? null;
        if (!in_array($ciclo, ['primer_ciclo', 'segundo_ciclo'])) return null;

        $asignaciones = Asignacion::with([
            'asignatura.competenciasActivas' => fn($q) => $q->where('ciclo', $ciclo)
                ->orderBy('orden')->with(['indicadoresActivos']),
        ])
        ->where('grupo_id', $matricula->grupo_id)
        ->where('school_year_id', $schoolYear->id)
        ->where('activo', true)
        ->get();

        $rawEvals = \App\Models\EvaluacionRegistro::where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear->id)
            ->get();

        $evalMap = [];
        foreach ($rawEvals as $e) {
            $k = $e->indicador_id ? "il_{$e->indicador_id}" : "ce_{$e->competencia_id}";
            $evalMap[$e->asignacion_id][$k][$e->periodo_id] = $e->valor_cualitativo ?? $e->nota_numerica;
        }

        return [
            'ciclo'             => $ciclo,
            'asignaciones'      => $asignaciones,
            'evalMap'           => $evalMap,
            'tieneEvaluaciones' => $rawEvals->isNotEmpty(),
        ];
    }

    // ── Recursos de la materia del hijo ─────────────────────────────────
    public function hijosRecursos(Estudiante $estudiante, \App\Models\Asignacion $asignacion)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403);

        // Verificar que la asignación corresponde al grupo del hijo
        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        if (! $matricula) abort(403);

        $recursos = \App\Models\RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.padre.recursos_hijo', compact(
            'representante', 'estudiante', 'matricula', 'asignacion', 'recursos', 'schoolYear'
        ));
    }

    // ── Página de comunicados ────────────────────────────────────────────
    public function comunicados()
    {
        // Grupos de los hijos del representante
        $representante = \App\Models\Representante::where('user_id', auth()->id())->first();
        $grupoIds = collect();
        if ($representante) {
            $estudianteIds = $representante->estudiantes()->pluck('estudiantes.id');
            $grupoIds = \App\Models\Matricula::whereIn('estudiante_id', $estudianteIds)
                ->where('estado', 'activa')
                ->pluck('grupo_id')
                ->unique();
        }

        $comunicados = Comunicado::publicados()
            ->where(function ($q) use ($grupoIds) {
                $q->whereIn('tipo_destinatarios', ['todos'])
                  ->orWhere(function ($g) use ($grupoIds) {
                      $g->where('tipo_destinatarios', 'grupo')
                        ->whereIn('grupo_id', $grupoIds);
                  });
            })
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('portal.padre.comunicados', compact('comunicados'));
    }

    // ── PDF lista comunicados del padre ─────────────────────────────────
    public function comunicadosPdf()
    {
        $comunicados = Comunicado::publicados()
            ->orderByDesc('published_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.padre.comunicados_pdf', compact(
            'comunicados', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('comunicados_' . now()->format('Ymd') . '.pdf');
    }

    // ── Excel comunicados del padre ──────────────────────────────────────
    public function comunicadosExcel()
    {
        $comunicados = \App\Models\Comunicado::publicados()
            ->orderByDesc('published_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comunicados');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Comunicados — ' . now()->format('d/m/Y'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Dirigido a', 'Fecha', 'Contenido'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($comunicados as $idx => $com) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $dirigido = match($com->dirigido_a ?? '') {
                'todos'        => 'Todos',
                'estudiantes'  => 'Estudiantes',
                'representantes' => 'Representantes',
                'docentes'     => 'Docentes',
                default        => ucfirst($com->dirigido_a ?? 'General'),
            };
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $com->titulo);
            $sheet->setCellValue('C' . $row, $dirigido);
            $sheet->setCellValue('D' . $row, $com->published_at?->format('d/m/Y') ?? '—');
            $sheet->setCellValue('E' . $row, \Illuminate\Support\Str::limit(strip_tags($com->contenido ?? ''), 200));
            $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
        }

        foreach (['A'=>5,'B'=>35,'C'=>16,'D'=>12,'E'=>50] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'comunicados_' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Marcar notificaciones leídas ─────────────────────────────────────
    public function marcarTodasLeidas()
    {
        Notificacion::where('user_id', auth()->id())->noLeidas()
            ->update(['leida' => true, 'leida_en' => now()]);
        Cache::put('user_' . auth()->id() . '_notif_unread', 0, 15);

        return response()->json(['ok' => true]);
    }

    // ── PDF de notas del hijo ────────────────────────────────────────────
    public function notasPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = \App\Models\Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()->groupBy('periodo_id');

        $calificacionesAcademicas = \App\Models\CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')
            ->get();

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.estudiante.notas_pdf', compact(
            'estudiante', 'matricula', 'schoolYear', 'periodos',
            'calificaciones', 'calificacionesAcademicas', 'inst', 'config'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("notas_{$slug}.pdf");
    }

    // ── Excel notas del hijo ─────────────────────────────────────────────
    public function notasExcel(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = \App\Models\Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()->groupBy('periodo_id');

        $calAcad = \App\Models\CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Notas');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->setCellValue('A1', 'Notas — ' . $estudiante->nombre_completo . ' — ' . ($schoolYear?->nombre ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['Asignatura'];
        foreach ($periodos as $p) { $headers[] = 'P' . $p->numero; }
        $headers[] = 'Promedio';

        foreach ($headers as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $colCount = count($headers);
        $lastCol  = chr(64 + $colCount);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->getStyle("A3:{$lastCol}3")->applyFromArray($hdrStyle);

        $row = 4;
        foreach ($calificaciones->first()?->groupBy(fn($c) => $c->asignacion_id) ?? [] as $asigId => $group) {
            $nombre = $group->first()->asignacion?->asignatura?->nombre ?? '—';
            $ws->setCellValue("A{$row}", $nombre);
            $col  = 1;
            $prom = [];
            foreach ($periodos as $p) {
                $nota = $calificaciones->get($p->id)?->firstWhere('asignacion_id', $asigId)?->nota_final;
                $ws->setCellValue([$col + 1, $row], $nota ?? '');
                if ($nota !== null) $prom[] = $nota;
                $col++;
            }
            $ws->setCellValue([$col + 1, $row], count($prom) ? round(array_sum($prom) / count($prom), 1) : '');
            if ($row % 2 === 0) {
                $ws->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
            $row++;
        }

        foreach ($calAcad as $ca) {
            $ws->setCellValue("A{$row}", $ca->asignacion?->asignatura?->nombre . ' (Acad.)');
            $ws->setCellValue([count($headers), $row], $ca->nota_final ?? '');
            $row++;
        }

        foreach (range('A', $lastCol) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'notas_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($estudiante->apellidos ?? 'estudiante');
        return response()->download($tmp, "notas_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Observaciones del hijo ───────────────────────────────────────────
    public function observacionesHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $observaciones = Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
            ->delEstudiante($estudiante->id)
            ->publicas()
            ->orderByDesc('created_at')
            ->get();

        return view('portal.padre.observaciones_hijo', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear', 'observaciones'
        ));
    }

    // ── Observaciones PDF del hijo ───────────────────────────────────────
    public function observacionesHijoPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $observaciones = Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
            ->delEstudiante($estudiante->id)
            ->publicas()
            ->orderByDesc('created_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.padre.observaciones_hijo_pdf',
            compact('estudiante', 'matricula', 'schoolYear', 'observaciones', 'inst')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("observaciones_{$slug}.pdf");
    }

    // ── Excel observaciones del hijo ─────────────────────────────────────
    public function observacionesHijoExcel(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $observaciones = Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
            ->delEstudiante($estudiante->id)
            ->publicas()
            ->orderByDesc('created_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Observaciones');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Observaciones — ' . $estudiante->nombre_completo);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'Fecha: ' . now()->format('d/m/Y'));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Docente', 'Asignatura', 'Tipo', 'Observación', 'Fecha'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($observaciones as $idx => $obs) {
            $row = $idx + 6;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $tipo = match($obs->tipo) {
                'felicitacion'     => 'Felicitación',
                'llamada_atencion' => 'Llamada de Atención',
                'compromiso'       => 'Compromiso',
                'informativa'      => 'Informativa',
                default            => ucfirst($obs->tipo ?? 'Observación'),
            };
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $obs->docente?->nombre_completo);
            $sheet->setCellValue('C' . $row, $obs->asignacion?->asignatura?->nombre ?? '—');
            $sheet->setCellValue('D' . $row, $tipo);
            $sheet->setCellValue('E' . $row, $obs->descripcion);
            $sheet->setCellValue('F' . $row, $obs->created_at?->format('d/m/Y'));
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
        }

        foreach (['A'=>5,'B'=>28,'C'=>22,'D'=>20,'E'=>50,'F'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        $filename = "observaciones_{$slug}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Asistencia del hijo ──────────────────────────────────────────────
    public function asistenciaHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumenAsistencia = $this->calcularAsistencia($matricula);

        // Ausencias individuales para mostrar estado de justificación
        $ausenciasDetalle = $matricula
            ? Asistencia::with('asignacion.asignatura')
                ->where('matricula_id', $matricula->id)
                ->where('estado', 'ausente')
                ->orderBy('fecha', 'desc')
                ->limit(30)
                ->get()
            : collect();

        $tiposJustificacion = \App\Models\Asistencia::TIPOS_JUSTIFICACION;

        return view('portal.padre.asistencia_hijo', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear',
            'resumenAsistencia', 'ausenciasDetalle', 'tiposJustificacion'
        ));
    }

    // ── Asistencia PDF del hijo ──────────────────────────────────────────
    public function asistenciaHijoPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumenAsistencia = $this->calcularAsistencia($matricula);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.padre.asistencia_hijo_pdf',
            compact('estudiante', 'matricula', 'schoolYear', 'resumenAsistencia', 'inst')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("asistencia_{$slug}.pdf");
    }

    public function asistenciaHijoExcel(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumen = $this->calcularAsistencia($matricula);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Asistencia');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Asistencia — ' . $estudiante->nombre_completo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->setCellValue('A2', 'Total'); $ws->setCellValue('B2', $resumen['total']);
        $ws->setCellValue('C2', 'Presentes'); $ws->setCellValue('D2', $resumen['presentes']);
        $ws->setCellValue('A3', 'Ausentes'); $ws->setCellValue('B3', $resumen['ausentes']);
        $ws->setCellValue('C3', 'Tardanzas'); $ws->setCellValue('D3', $resumen['tardanzas']);
        $ws->setCellValue('A4', 'Porcentaje'); $ws->setCellValue('B4', ($resumen['porcentaje'] ?? '—') . '%');

        $headers = ['#', 'Asignatura', 'Total', 'Presentes', 'Ausentes', 'Porcentaje'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '6';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($resumen['por_materia'] as $i => $mat) {
            $row = $i + 7;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $mat['asignatura']);
            $ws->setCellValue("C{$row}", $mat['total']);
            $ws->setCellValue("D{$row}", $mat['presentes']);
            $ws->setCellValue("E{$row}", $mat['ausentes']);
            $pct = $mat['porcentaje'];
            $ws->setCellValue("F{$row}", $pct !== null ? $pct . '%' : '—');

            if ($pct !== null && $pct < 80) {
                $ws->getStyle("F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('fee2e2');
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        $filename = "asistencia_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Solicitar justificación de ausencia ──────────────────────────────
    public function solicitarJustificacion(Request $request, Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $data = $request->validate([
            'fecha_evento'  => 'required|date',
            'descripcion'   => 'required|string|max:1000',
            'tipo'          => 'nullable|string|max:40',
        ]);

        $tipoLabel = \App\Models\Asistencia::TIPOS_JUSTIFICACION[$data['tipo'] ?? ''] ?? 'Ausencia';
        $fecha     = \Carbon\Carbon::parse($data['fecha_evento'])->format('d/m/Y');

        SolicitudRepresentante::create([
            'tenant_id'       => tenant_id(),
            'representante_id'=> $representante->id,
            'estudiante_id'   => $estudiante->id,
            'tipo'            => 'justificacion_ausencia',
            'asunto'          => "Justificación de ausencia — {$fecha}",
            'descripcion'     => "Tipo: {$tipoLabel}\n{$data['descripcion']}",
            'fecha_evento'    => $data['fecha_evento'],
            'estado'          => 'pendiente',
        ]);

        return back()->with('success', 'Solicitud de justificación enviada correctamente.');
    }

    // ── Horario web del hijo ─────────────────────────────────────────────
    public function horarioHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        return view('portal.padre.horario_hijo', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear',
            'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig'
        ));
    }

    // ── Horario PDF del hijo ─────────────────────────────────────────────
    public function horarioPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        if (! $horarioActivo || empty($gridHorario)) abort(404, 'Horario no disponible.');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.horario_pdf',
            compact('estudiante', 'matricula', 'gridHorario', 'franjasHorario', 'diasConfig', 'inst', 'config', 'schoolYear')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("horario_{$slug}.pdf");
    }

    // ── Horario del hijo Excel ───────────────────────────────────────────
    public function horarioExcel(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        if (! $horarioActivo || empty($gridHorario)) abort(404, 'Horario no disponible.');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dias = array_keys($gridHorario);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Horario');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $ws->mergeCells('A1:' . chr(65 + count($dias)) . '1');
        $ws->setCellValue('A1', $inst);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:' . chr(65 + count($dias)) . '2');
        $ws->setCellValue('A2', 'Horario — ' . $estudiante->nombre_completo . ' — ' . ($matricula->grupo?->nombre_completo ?? '') . ' — ' . ($schoolYear?->nombre ?? ''));
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $ws->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->setCellValue('A4', 'Hora');
        $ws->getStyle('A4')->applyFromArray($hdrStyle);
        $diasNombres = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado'];
        foreach ($dias as $k => $dia) {
            $col = chr(66 + $k);
            $ws->setCellValue("{$col}4", $diasNombres[$dia] ?? ucfirst($dia));
            $ws->getStyle("{$col}4")->applyFromArray($hdrStyle);
        }

        foreach ($franjasHorario as $j => $franja) {
            $row = $j + 5;
            $ws->setCellValue("A{$row}", ($franja->hora_inicio ?? '') . ' - ' . ($franja->hora_fin ?? ''));
            foreach ($dias as $k => $dia) {
                $col   = chr(66 + $k);
                $bloque = $gridHorario[$dia][$franja->id] ?? null;
                $ws->setCellValue("{$col}{$row}", $bloque ? ($bloque->asignatura?->nombre ?? '—') : '—');
                if ($j % 2 === 1) {
                    $ws->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
                }
            }
            if ($j % 2 === 1) {
                $ws->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
        }

        foreach (range('A', chr(65 + count($dias))) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'hor_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return response()->download($tmp, "horario_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Planificaciones del Hijo ─────────────────────────────────────────
    public function planificacionesHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $planificaciones = collect();

        if ($matricula) {
            $asignacionIds = \App\Models\Asignacion::where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('area', 'tecnica')
                ->pluck('id');

            $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente', 'raItems', 'actividades'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->latest()
                ->get()
                ->groupBy('asignacion_id');
        }

        return view('portal.padre.planificaciones_hijo', compact(
            'estudiante', 'matricula', 'schoolYear', 'planificaciones'
        ));
    }

    public function planificacionesHijoPdf(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $planificaciones = collect();

        if ($matricula) {
            $asignacionIds = \App\Models\Asignacion::where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('area', 'tecnica')
                ->pluck('id');

            $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente', 'raItems', 'actividades'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->latest()
                ->get()
                ->groupBy('asignacion_id');
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.padre.planificaciones_hijo_pdf', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear', 'planificaciones', 'inst'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("planificaciones_{$slug}.pdf");
    }

    // ── Excel planificaciones del hijo ───────────────────────────────────
    public function planificacionesHijoExcel(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $planificaciones = collect();

        if ($matricula) {
            $asignacionIds = \App\Models\Asignacion::where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('area', 'tecnica')
                ->pluck('id');

            $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente', 'raItems', 'actividades'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->latest()
                ->get();
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planificaciones');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Planificaciones — ' . $estudiante->nombre_completo);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Asignatura', 'Módulo / Título', 'Código MF', 'R.A.', 'Actividades'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($planificaciones as $idx => $plan) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $plan->asignacion?->asignatura?->nombre);
            $sheet->setCellValue('C' . $row, $plan->titulo ?? $plan->modulo);
            $sheet->setCellValue('D' . $row, $plan->codigo_mf ?? '—');
            $sheet->setCellValue('E' . $row, $plan->raItems?->count() ?? 0);
            $sheet->setCellValue('F' . $row, $plan->actividades?->count() ?? 0);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (['A'=>5,'B'=>25,'C'=>35,'D'=>14,'E'=>8,'F'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "planificaciones_{$slug}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Excel recursos del hijo ──────────────────────────────────────────
    public function hijosRecursosExcel(Estudiante $estudiante, \App\Models\Asignacion $asignacion)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        if (! $matricula) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        $recursos = \App\Models\RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Recursos');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . $estudiante->nombre_completo);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Tipo', 'Descripción', 'URL / Archivo'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($recursos as $idx => $rec) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $rec->titulo);
            $sheet->setCellValue('C' . $row, ucfirst($rec->tipo ?? '—'));
            $sheet->setCellValue('D' . $row, $rec->descripcion);
            $sheet->setCellValue('E' . $row, $rec->url ?? $rec->archivo_nombre ?? '—');
            $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);
        }

        foreach (['A'=>5,'B'=>30,'C'=>14,'D'=>35,'E'=>40] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'recursos');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "recursos_{$slug}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function hijosRecursosPdf(Estudiante $estudiante, \App\Models\Asignacion $asignacion)
    {
        $representante = $this->getRepresentante();
        $schoolYear    = SchoolYear::actual();

        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        if (! $matricula) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        $recursos = \App\Models\RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.padre.recursos_hijo_pdf', compact(
            'representante', 'estudiante', 'matricula', 'asignacion', 'recursos', 'schoolYear', 'inst'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'recursos');
        return $pdf->download("recursos_{$slug}.pdf");
    }

    // ── Documentos del hijo ──────────────────────────────────────────────
    public function documentosHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403, 'No tienes acceso a la información de este estudiante.');
        }

        $schoolYear = SchoolYear::actual();
        $matricula  = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        $moduloPagos = \App\Models\ConfigInstitucional::moduloActivo('pagos');

        return view('portal.padre.documentos_hijo', compact(
            'representante', 'estudiante', 'matricula', 'schoolYear', 'moduloPagos'
        ));
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function calcularAsistencia($matricula): array
    {
        if (! $matricula) return ['total' => 0, 'presentes' => 0, 'ausentes' => 0, 'tardanzas' => 0, 'porcentaje' => null];

        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->orderBy('fecha', 'desc')
            ->get();

        $total     = $asistencias->count();
        $presentes = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();
        $ausentes  = $asistencias->where('estado', 'ausente')->count();
        $tardanzas = $asistencias->where('estado', 'tardanza')->count();

        $porMateria = $asistencias->groupBy('asignacion_id')->map(function ($rows) {
            $total    = $rows->count();
            $pres     = $rows->whereIn('estado', ['presente', 'tardanza'])->count();
            return [
                'asignatura' => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
                'total'      => $total,
                'presentes'  => $pres,
                'ausentes'   => $rows->where('estado', 'ausente')->count(),
                'porcentaje' => $total > 0 ? round($pres / $total * 100, 1) : null,
            ];
        })->values();

        return [
            'total'      => $total,
            'presentes'  => $presentes,
            'ausentes'   => $ausentes,
            'tardanzas'  => $tardanzas,
            'porcentaje' => $total > 0 ? round($presentes / $total * 100, 1) : null,
            'por_materia'=> $porMateria,
        ];
    }

    // ── Comparativo de notas del hijo por período ─────────────────────────
    private function calcularComparativoPeriodos($matricula, $periodos, $calificaciones, $calificacionesAcademicas): array
    {
        if (! $matricula || $periodos->isEmpty()) {
            return ['labels' => '[]', 'data' => '[]', 'tendencia' => null];
        }

        $promediosPorPeriodo = [];

        foreach ($periodos as $periodo) {
            $notas = collect();

            // Calificaciones técnicas
            $calsPeriodo = $calificaciones->get($periodo->id, collect());
            foreach ($calsPeriodo as $cal) {
                if ($cal->nota_final !== null) {
                    $notas->push((float) $cal->nota_final);
                }
            }

            // Calificaciones académicas: extraer la nota del período específico
            foreach ($calificacionesAcademicas as $calAcad) {
                $n = $periodo->numero;
                // Promedio de las 4 competencias en ese período
                $vals = [];
                for ($ci = 1; $ci <= 4; $ci++) {
                    $pb = $calAcad->{"comp{$ci}_p{$n}"};
                    if ($pb !== null) {
                        $rv = $calAcad->{"comp{$ci}_r{$n}"};
                        $pb = (float) $pb;
                        $cv = ($rv !== null && $pb < 70)
                            ? round($pb + min((float) $rv, max(0.0, 100.0 - $pb)), 2)
                            : round($pb, 2);
                        $vals[] = $cv;
                    }
                }
                if (count($vals)) {
                    $notas->push(round(array_sum($vals) / count($vals), 1));
                }
            }

            if ($notas->count()) {
                $promediosPorPeriodo[$periodo->numero] = round($notas->avg(), 1);
            }
        }

        if (empty($promediosPorPeriodo)) {
            return ['labels' => '[]', 'data' => '[]', 'tendencia' => null];
        }

        $labels = collect(array_keys($promediosPorPeriodo))->map(fn($n) => "P{$n}")->values();
        $data   = collect(array_values($promediosPorPeriodo))->values();

        // Tendencia: comparar primer y último período con datos
        $valores = array_values($promediosPorPeriodo);
        $primero = reset($valores);
        $ultimo  = end($valores);
        $tendencia = count($valores) >= 2
            ? ($ultimo > $primero ? 'positiva' : ($ultimo < $primero ? 'negativa' : 'estable'))
            : null;

        return [
            'labels'    => $labels->toJson(),
            'data'      => $data->toJson(),
            'tendencia' => $tendencia,
            'primero'   => $primero,
            'ultimo'    => count($valores) >= 2 ? $ultimo : null,
        ];
    }

    private function cargarHorario($matricula, $schoolYear): array
    {
        $grid    = [];
        $franjas = collect();
        $horario = null;
        $dias    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        if ($matricula && $schoolYear) {
            $horario = Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')->latest()->first();

            if ($horario) {
                $detalles = HorarioDetalle::with(['asignacion.asignatura', 'asignacion.docente', 'franja', 'aula'])
                    ->where('horario_id', $horario->id)
                    ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $matricula->grupo_id))
                    ->get();

                $franjas = FranjaHoraria::where('activa', true)->orderBy('numero')->get();

                foreach ($detalles as $d) {
                    $grid[$d->franja_id][$d->dia] = $d;
                }

                $dias = \App\Models\ConfigInstitucional::get('horario_dias', $dias);
            }
        }

        return [$grid, $franjas, $horario, $dias];
    }

    // ── Encuestas disponibles para el padre ──────────────────────────────
    public function encuestas()
    {
        $encuestas = Encuesta::activas()
            ->dirigidaA('padres')
            ->withCount('preguntas')
            ->latest()
            ->get()
            ->map(function ($encuesta) {
                $encuesta->_ya_respondio = $encuesta->yaRespondio(auth()->id());
                return $encuesta;
            });

        return view('portal.padre.encuestas', compact('encuestas'));
    }

    // ── Mostrar formulario de respuesta (GET) ─────────────────────────────
    public function verEncuesta(Encuesta $encuesta)
    {
        abort_unless($encuesta->activo, 403, 'Esta encuesta no está disponible.');
        abort_if($encuesta->fecha_cierre && $encuesta->fecha_cierre->isPast(), 403, 'La encuesta ha cerrado.');

        if ($encuesta->yaRespondio(auth()->id())) {
            return redirect()->route('portal.padre.encuestas')
                             ->with('success', 'Ya has respondido esta encuesta anteriormente.');
        }

        $encuesta->load('preguntas.opciones');

        return view('portal.padre.encuestas_responder', compact('encuesta'));
    }

    // ── Guardar respuestas (POST) ─────────────────────────────────────────
    public function responderEncuesta(Request $request, Encuesta $encuesta)
    {
        abort_unless($encuesta->activo, 403, 'Esta encuesta no está disponible.');
        abort_if($encuesta->fecha_cierre && $encuesta->fecha_cierre->isPast(), 403, 'La encuesta ha cerrado.');
        abort_if($encuesta->yaRespondio(auth()->id()), 403, 'Ya respondiste esta encuesta.');

        $encuesta->load('preguntas.opciones');

        $rules = [];
        foreach ($encuesta->preguntas as $pregunta) {
            if ($pregunta->tipo === 'opcion_multiple') {
                $rules["respuestas.{$pregunta->id}.opcion_id"] = "required|exists:opciones_pregunta,id";
            } elseif ($pregunta->tipo === 'escala_1_5') {
                $rules["respuestas.{$pregunta->id}.escala_valor"] = "required|integer|min:1|max:5";
            } else {
                $rules["respuestas.{$pregunta->id}.respuesta_texto"] = "required|string|max:1000";
            }
        }

        $validated = $request->validate($rules);

        foreach ($encuesta->preguntas as $pregunta) {
            $dato = $validated['respuestas'][$pregunta->id] ?? [];
            RespuestaEncuesta::create([
                'encuesta_id'     => $encuesta->id,
                'pregunta_id'     => $pregunta->id,
                'user_id'         => auth()->id(),
                'opcion_id'       => $dato['opcion_id'] ?? null,
                'escala_valor'    => isset($dato['escala_valor']) ? (int) $dato['escala_valor'] : null,
                'respuesta_texto' => $dato['respuesta_texto'] ?? null,
            ]);
        }

        return redirect()->route('portal.padre.encuestas')
                         ->with('success', '¡Gracias por participar! Tus respuestas han sido registradas.');
    }

    public function saldoCafeteriaHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403, 'No tienes acceso a la información de este estudiante.');

        $saldo          = \App\Models\VentaCafeteria::saldoEstudiante($estudiante->id);
        $historial      = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->latest()->limit(50)->get();
        $totalRecargado = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->where('tipo', 'recarga')->sum('monto');
        $totalGastado   = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->where('tipo', 'venta')->sum('monto');

        return view('portal.padre.cafeteria_hijo', compact(
            'estudiante', 'saldo', 'historial', 'totalRecargado', 'totalGastado'
        ));
    }

    public function rutaTransporteHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        $esHijo = $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists();
        if (! $esHijo) abort(403, 'No tienes acceso a la información de este estudiante.');

        $asignacion = \App\Models\EstudianteRuta::where('estudiante_id', $estudiante->id)
                        ->with(['ruta.paradas', 'parada'])
                        ->latest()->first();
        $ruta = $asignacion?->ruta;

        return view('portal.padre.transporte_hijo', compact(
            'estudiante', 'asignacion', 'ruta'
        ));
    }

    // ── Logros, Puntos y Reconocimientos del hijo ────────────────────────
    public function logrosHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $reconocimientos = Reconocimiento::with('tipo')
            ->where('estudiante_id', $estudiante->id)
            ->latest('fecha')->get();

        $gamificacionActiva = tenant_can('gamificacion');
        $schoolYear         = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $totalPuntos      = 0;
        $insigniasObtenidas = collect();
        $historial        = collect();
        $puntosCategoria  = [];
        $ranking          = collect();
        $miPosicion       = null;

        if ($gamificacionActiva && $matricula) {
            $totalPuntos       = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');
            $insigniasObtenidas = InsigniaEstudiante::where('matricula_id', $matricula->id)->get()->keyBy('tipo');
            $historial         = PuntoEstudiante::where('matricula_id', $matricula->id)
                ->orderByDesc('fecha')->orderByDesc('id')->limit(50)->get();
            $puntosCategoria   = PuntoEstudiante::where('matricula_id', $matricula->id)
                ->selectRaw('categoria, SUM(puntos) as total')
                ->groupBy('categoria')->pluck('total', 'categoria')->toArray();

            $grupoMats = Matricula::where('grupo_id', $matricula->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->with('estudiante')->get();

            $rankingRaw = PuntoEstudiante::whereIn('matricula_id', $grupoMats->pluck('id'))
                ->selectRaw('matricula_id, SUM(puntos) as total')
                ->groupBy('matricula_id')->orderByDesc('total')->get();

            foreach ($rankingRaw as $idx => $r) {
                if ($r->matricula_id === $matricula->id) { $miPosicion = $idx + 1; break; }
            }
            if ($miPosicion === null && (int) $totalPuntos === 0) {
                $miPosicion = $rankingRaw->count() + 1;
            }

            $ranking = $rankingRaw->take(10)->map(function ($r, $idx) use ($grupoMats, $matricula) {
                $mat = $grupoMats->firstWhere('id', $r->matricula_id);
                return [
                    'matricula_id' => $r->matricula_id,
                    'nombre'       => $mat?->estudiante?->nombre_completo ?? '—',
                    'total'        => (int) $r->total,
                    'es_hijo'      => $r->matricula_id === $matricula->id,
                ];
            })->values();
        }

        return view('portal.padre.logros_hijo', compact(
            'estudiante', 'reconocimientos',
            'gamificacionActiva', 'matricula', 'schoolYear',
            'totalPuntos', 'insigniasObtenidas', 'historial', 'puntosCategoria', 'ranking', 'miPosicion'
        ));
    }

    // ── Proyectos del hijo ────────────────────────────────────────────────
    public function proyectosHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $proyectos = ProyectoEscolar::with(['tutor', 'fases'])
            ->whereHas('integrantes', fn($q) => $q->where('estudiante_id', $estudiante->id))
            ->latest()
            ->get();

        return view('portal.padre.proyectos_hijo', compact('estudiante', 'proyectos'));
    }

    // ── Calendario escolar ───────────────────────────────────────────────
    public function calendario()
    {
        $schoolYear = SchoolYear::actual();
        $eventos    = $this->calendarEventos(['todos', 'estudiantes']);
        return view('portal.padre.calendario', compact('schoolYear', 'eventos'));
    }

    public function calendarioApi()
    {
        $eventos = $this->calendarEventos(['todos', 'estudiantes']);
        return response()->json($eventos);
    }

    private function calendarEventos(array $apliCa): array
    {
        $schoolYear = SchoolYear::actual();

        $cal = \App\Models\CalendarioAcademico::when($schoolYear, fn($q) => $q->delAnio($schoolYear->id))
            ->where('activo', true)
            ->where(fn($q) => $q->whereIn('aplica_a', $apliCa))
            ->get()
            ->map(fn($e) => [
                'id'     => 'cal_' . $e->id,
                'titulo' => $e->titulo,
                'inicio' => $e->fecha_inicio->format('Y-m-d'),
                'fin'    => $e->fecha_fin?->format('Y-m-d'),
                'tipo'   => $e->tipo,
                'color'  => $e->color ?? '#6b7280',
                'desc'   => $e->descripcion,
                'fuente' => 'calendario',
            ]);

        $evs = \App\Models\Evento::activos()->get()->map(fn($e) => [
            'id'     => 'ev_' . $e->id,
            'titulo' => $e->nombre,
            'inicio' => $e->fecha_inicio->format('Y-m-d'),
            'fin'    => $e->fecha_fin?->format('Y-m-d'),
            'tipo'   => 'evento_' . $e->tipo,
            'color'  => match($e->tipo) {
                'academico' => '#0891b2', 'deportivo' => '#16a34a',
                'cultural'  => '#7c3aed', 'social'    => '#d97706',
                default     => '#6b7280',
            },
            'desc'   => $e->descripcion,
            'fuente' => 'evento',
        ]);

        // Reuniones de padres
        $reu = \App\Models\Reunion::where('tipo', 'reunion_padres')
            ->where('estado', '!=', 'cancelada')
            ->get()
            ->map(fn($r) => [
                'id'     => 'reu_' . $r->id,
                'titulo' => $r->titulo,
                'inicio' => $r->fecha->format('Y-m-d'),
                'fin'    => null,
                'tipo'   => 'reunion',
                'color'  => '#0d9488',
                'desc'   => $r->lugar,
                'fuente' => 'reunion',
            ]);

        $pers = collect();
        if ($schoolYear) {
            foreach ($this->getPeriodos($schoolYear) as $p) {
                if ($p->fecha_inicio) $pers->push([
                    'id' => 'pi_' . $p->id, 'titulo' => 'Inicio ' . $p->nombre,
                    'inicio' => $p->fecha_inicio->format('Y-m-d'), 'fin' => null,
                    'tipo' => 'inicio_periodo', 'color' => '#2563eb', 'desc' => null, 'fuente' => 'periodo',
                ]);
                if ($p->fecha_fin) $pers->push([
                    'id' => 'pf_' . $p->id, 'titulo' => 'Fin ' . $p->nombre,
                    'inicio' => $p->fecha_fin->format('Y-m-d'), 'fin' => null,
                    'tipo' => 'fin_periodo', 'color' => '#dc2626', 'desc' => null, 'fuente' => 'periodo',
                ]);
            }
        }

        return $cal->concat($evs)->concat($reu)->concat($pers)->values()->all();
    }

    public function planEvaluacionHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $periodos   = collect();
        $planesData = collect();

        if ($matricula) {
            $periodos = Periodo::where('school_year_id', $schoolYear?->id)
                ->where('tenant_id', tenant_id())
                ->orderBy('numero')
                ->get();

            $asignaciones = Asignacion::with('asignatura', 'docente')
                ->where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $asignacionIds = $asignaciones->pluck('id');

            $planes = PlanEvaluacionPeriodo::with(['periodo', 'asignacion.asignatura'])
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->get();

            $instrumentos = InstrumentoEvaluacion::with('criterios')
                ->whereIn('asignacion_id', $asignacionIds)
                ->where('publicado', true)
                ->whereNotNull('periodo_id')
                ->get();

            $planesData = $asignaciones->map(function ($asignacion) use ($planes, $periodos, $instrumentos) {
                $planesAsig = $planes->where('asignacion_id', $asignacion->id)->keyBy('periodo_id');
                $instAsig   = $instrumentos->where('asignacion_id', $asignacion->id)->groupBy('periodo_id');

                $tienePlanes = $planesAsig->isNotEmpty();
                return compact('asignacion', 'planesAsig', 'instAsig', 'tienePlanes');
            })->filter(fn($row) => $row['tienePlanes'])->values();
        }

        $categorias = PlanEvaluacionPeriodo::$categorias;

        return view('portal.padre.plan_evaluacion_hijo', compact(
            'estudiante', 'schoolYear', 'matricula', 'periodos', 'planesData', 'categorias'
        ));
    }

    // ── Rúbricas del hijo ────────────────────────────────────────────────
    public function rubricasHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $aplicaciones = collect();
        if ($matricula) {
            $aplicaciones = \App\Models\RubricaAplicacion::with(['rubrica.asignatura', 'asignacion.docente'])
                ->where('matricula_id', $matricula->id)
                ->latest()
                ->get();
        }

        return view('portal.padre.rubricas_hijo', compact(
            'estudiante', 'schoolYear', 'matricula', 'aplicaciones'
        ));
    }

    // ── Tareas del hijo ──────────────────────────────────────────────────
    public function tareasHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $tareasPorMateria = collect();

        if ($matricula) {
            $asignaciones = Asignacion::with('asignatura', 'docente')
                ->where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $asgIds = $asignaciones->pluck('id');

            $entregas = \App\Models\EntregaTarea::where('estudiante_id', $estudiante->id)
                ->whereHas('tarea', fn($q) => $q->whereIn('asignacion_id', $asgIds))
                ->get()->keyBy('tarea_id');

            $tareas = \App\Models\Tarea::with('asignacion.asignatura')
                ->whereIn('asignacion_id', $asgIds)
                ->where('activo', true)
                ->orderByDesc('fecha_limite')
                ->get();

            $tareasPorMateria = $tareas->groupBy('asignacion_id')->map(fn($ts, $asgId) => [
                'asignacion' => $asignaciones->firstWhere('id', $asgId),
                'tareas'     => $ts,
                'entregas'   => $entregas,
            ]);
        }

        return view('portal.padre.tareas_hijo', compact(
            'estudiante', 'schoolYear', 'matricula', 'tareasPorMateria'
        ));
    }

    // ── Conducta del hijo ────────────────────────────────────────────────
    public function conductaHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $registros  = collect();
        $periodos   = collect();

        if ($matricula) {
            $periodos = Periodo::where('school_year_id', $schoolYear?->id)
                ->where('tenant_id', tenant_id())
                ->orderBy('numero')->get();

            $registros = \App\Models\ConductaRegistro::with(['asignacion.asignatura', 'asignacion.docente', 'periodo'])
                ->where('matricula_id', $matricula->id)
                ->get()
                ->groupBy('periodo_id');
        }

        $indicadores = \App\Models\ConductaRegistro::INDICADORES;
        $escala      = \App\Models\ConductaRegistro::ESCALA;

        return view('portal.padre.conducta_hijo', compact(
            'estudiante', 'schoolYear', 'matricula', 'periodos', 'registros', 'indicadores', 'escala'
        ));
    }

    // ── Risk Score del hijo ──────────────────────────────────────────────
    public function riesgoHijo(\App\Models\Estudiante $estudiante)
    {
        $rep = $this->getRepresentante();
        abort_unless(
            $rep->estudiantes()->where('estudiantes.id', $estudiante->id)->exists(),
            403, 'No tienes acceso a este estudiante.'
        );

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $score = $schoolYear
            ? \App\Models\AcademicRiskScore::where('estudiante_id', $estudiante->id)
                ->where('school_year_id', $schoolYear->id)
                ->first()
            : null;

        return view('portal.padre.hijo_riesgo', compact('estudiante', 'schoolYear', 'matricula', 'score'));
    }

    // ── Carnet+ del Hijo ────────────────────────────────────────────────────
    public function carnetHijo(\App\Models\Estudiante $estudiante)
    {
        $rep = $this->getRepresentante();
        abort_unless(
            $rep->estudiantes()->where('estudiantes.id', $estudiante->id)->exists(),
            403, 'No tienes acceso a este estudiante.'
        );

        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $carnet = \App\Models\CarnetIdentidad::where('user_id', $estudiante->user?->id)
            ->where('tipo', 'estudiante')
            ->first();

        $risk    = $carnet ? \App\Services\CarnetRiskScoreService::calcular($carnet) : null;
        $qrUrl   = $carnet ? \App\Services\CarnetQrService::qrContent($carnet) : null;
        $accesos = $carnet ? $carnet->accesos()->latest()->limit(30)->get() : collect();

        return view('portal.padre.hijo_carnet', compact(
            'estudiante', 'carnet', 'matricula', 'schoolYear', 'risk', 'qrUrl', 'accesos'
        ));
    }

    // ── Reconocimientos del Hijo ───────────────────────────────────────────
    public function reconocimientosHijo(\App\Models\Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $reconocimientos = \App\Models\Reconocimiento::with('tipo', 'emitidoPor')
            ->where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get();

        return view('portal.padre.reconocimientos_hijo', compact('estudiante', 'reconocimientos'));
    }

    // ── Salud del Hijo ────────────────────────────────────────────────────
    public function saludHijo(\App\Models\Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $ficha     = \App\Models\FichaSalud::where('estudiante_id', $estudiante->id)->first();
        $incidentes = \App\Models\IncidenteMedico::where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get();

        return view('portal.padre.salud_hijo', compact('estudiante', 'ficha', 'incidentes'));
    }

    // ── Registro MINERD del hijo (CE / IL) ──────────────────────────────
    public function registroMinerdHijo(Estudiante $estudiante)
    {
        $representante = $this->getRepresentante();
        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) abort(403);

        $schoolYear = SchoolYear::actual();

        $matricula = Matricula::with(['grupo.grado', 'grupo.seccion'])
            ->where('estudiante_id', $estudiante->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        $ciclo        = $matricula?->grupo?->grado?->ciclo ?? 'primer_ciclo';
        $asignaciones = collect();
        $periodos     = collect();
        $evalMap      = [];

        if ($matricula && $schoolYear) {
            $asignaciones = Asignacion::with([
                'asignatura.competenciasActivas' => fn($q) => $q->where('ciclo', $ciclo)
                    ->orderBy('orden')->with(['indicadoresActivos']),
                'docente',
            ])
            ->where('grupo_id', $matricula->grupo_id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get();

            $periodos = Periodo::where('school_year_id', $schoolYear->id)->orderBy('numero')->get();

            $rawEvals = \App\Models\EvaluacionRegistro::where('matricula_id', $matricula->id)
                ->where('school_year_id', $schoolYear->id)
                ->get();

            foreach ($rawEvals as $e) {
                $key = $e->indicador_id ? "il_{$e->indicador_id}" : "ce_{$e->competencia_id}";
                $evalMap[$e->asignacion_id][$key][$e->periodo_id] = $e->valor_cualitativo ?? $e->nota_numerica;
            }
        }

        return view('portal.padre.registro_minerd_hijo', compact(
            'estudiante', 'matricula', 'ciclo', 'asignaciones', 'periodos', 'evalMap', 'schoolYear'
        ));
    }
}
