<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Comunicado;
use App\Models\Encuesta;
use App\Models\Evento;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\InscripcionEvento;
use App\Models\IntegranteProyecto;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\Planificacion;
use App\Models\InsigniaEstudiante;
use App\Models\Pago;
use App\Services\CardNetService;
use App\Models\ProyectoEscolar;
use App\Models\PuntoEstudiante;
use App\Models\RecursoMateria;
use App\Models\RespuestaEncuesta;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalEstudianteController extends Controller
{
    private function getEstudiante()
    {
        $user = auth()->user();
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            abort(403, 'No tienes un perfil de estudiante asociado a esta cuenta.');
        }

        return $estudiante;
    }

    // ── Dashboard principal ──────────────────────────────────────────────
    public function dashboard()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        // Calificaciones publicadas
        $calificaciones = collect();
        $calificacionesAcademicas = collect();
        $promedioGeneral = null;

        if ($matricula) {
            $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
                ->where('matricula_id', $matricula->id)
                ->where('publicado', true)
                ->get()
                ->groupBy('periodo_id');

            $calificacionesAcademicas = CalificacionAcademica::with('asignacion.asignatura')
                ->where('matricula_id', $matricula->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->whereNotNull('nota_final')
                ->get();

            // Promedio general
            $todasNotas = $calificaciones->flatten()->pluck('nota_final')
                ->merge($calificacionesAcademicas->pluck('nota_final'))
                ->filter()
                ->values();

            $promedioGeneral = $todasNotas->count() ? round($todasNotas->avg(), 1) : null;
        }

        // Asistencia resumen
        $resumenAsistencia = $this->calcularResumenAsistencia($matricula);

        // Horario
        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        // Comunicados (noticias) — cacheados 10 min
        $tid = tenant_id() ?? 0;
        $comunicados = \Illuminate\Support\Facades\Cache::remember(
            "t{$tid}_comunicados_recientes", 600,
            fn() => Comunicado::publicados()->orderByDesc('published_at')->limit(5)->get()
        );

        // Notificaciones no leídas
        $notificaciones = Notificacion::where('user_id', auth()->id())
            ->noLeidas()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalNoLeidas = $notificaciones->count();

        // Observaciones del docente (no privadas)
        $observaciones = $matricula
            ? Observacion::with(['docente', 'asignacion.asignatura'])
                ->delEstudiante($estudiante->id)
                ->publicas()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
            : collect();

        // Asignaciones del grupo (para acceso a recursos y planificaciones)
        $asignaciones = $matricula
            ? \App\Models\Asignacion::with(['asignatura', 'docente'])
                ->where('grupo_id', $matricula->grupo_id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->where('activo', true)
                ->get()
            : collect();

        // Próximos eventos del calendario académico
        $eventosCalendario = $schoolYear
            ? \App\Models\CalendarioAcademico::where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->where('fecha_inicio', '>=', today())
                ->orderBy('fecha_inicio')
                ->limit(5)
                ->get()
            : collect();

        // ZuraClass — resumen para el estudiante
        $zuraClasesData = null;
        if ($matricula) {
            $misClases = \App\Models\ClaseVirtual::with(['asignacion.asignatura', 'asignacion.docente'])
                ->whereHas('asignacion', fn($q) =>
                    $q->where('grupo_id', $matricula->grupo_id)
                      ->where('school_year_id', $matricula->school_year_id)
                      ->where('activo', true)
                )
                ->where('activo', true)
                ->get();

            // Bulk-load materiales pendientes de todas las clases — 1 sola query
            $claseIds = $misClases->pluck('id');
            $claseMap = $misClases->keyBy('id');

            $materialesPendientes = $claseIds->isNotEmpty()
                ? \App\Models\MaterialClase::whereIn('clase_virtual_id', $claseIds)
                    ->whereIn('tipo', ['tarea', 'evaluacion'])
                    ->where('publicado', true)
                    ->whereDoesntHave('entregas', fn($q) =>
                        $q->where('matricula_id', $matricula->id)
                          ->whereIn('estado', ['entregado', 'calificado', 'atrasado'])
                    )
                    ->get()
                : collect();

            $tareasPendientes = [];
            $tareasVencidas   = 0;
            foreach ($materialesPendientes as $mat) {
                $clase = $claseMap->get($mat->clase_virtual_id);
                if ($mat->estaVencido()) {
                    $tareasVencidas++;
                } else {
                    $tareasPendientes[] = [
                        'titulo'       => $mat->titulo,
                        'clase'        => $clase?->nombre,
                        'asignatura'   => $clase?->asignacion?->asignatura?->nombre,
                        'fecha_limite' => $mat->fecha_limite,
                        'clase_id'     => $mat->clase_virtual_id,
                        'material_id'  => $mat->id,
                    ];
                }
            }
            usort($tareasPendientes, fn($a, $b) => $a['fecha_limite'] <=> $b['fecha_limite']);

            $zuraClasesData = [
                'totalClases'      => $misClases->count(),
                'tareasPendientes' => array_slice($tareasPendientes, 0, 5),
                'totalPendientes'  => count($tareasPendientes),
                'tareasVencidas'   => $tareasVencidas,
            ];
        }

        return view('portal.estudiante.dashboard', compact(
            'estudiante', 'schoolYear', 'matricula', 'periodos',
            'calificaciones', 'calificacionesAcademicas', 'promedioGeneral',
            'resumenAsistencia', 'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig',
            'comunicados', 'notificaciones', 'totalNoLeidas', 'observaciones',
            'asignaciones', 'eventosCalendario', 'zuraClasesData'
        ));
    }

    // ── Historial completo de notificaciones ────────────────────────────
    public function notificaciones()
    {
        $notificaciones = \App\Models\Notificacion::where('user_id', auth()->id())
            ->latest()
            ->paginate(30);

        // Marcar todas como leídas al ver el historial
        \App\Models\Notificacion::where('user_id', auth()->id())
            ->noLeidas()->update(['leida' => true, 'leida_en' => now()]);
        Cache::put('user_' . auth()->id() . '_notif_unread', 0, 15);

        return view('portal.notificaciones', compact('notificaciones'));
    }

    // ── Boletín personal del estudiante ─────────────────────────────────
    public function boletin()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        if (! $matricula) {
            return back()->with('error', 'No tienes una matrícula activa en el año escolar actual.');
        }

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        // Calificaciones técnicas publicadas (por asignatura y período)
        $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()
            ->groupBy('asignacion_id');

        // Calificaciones académicas publicadas
        $calificacionesAcademicas = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->get()
            ->keyBy('asignacion_id');

        $resumenAsistencia = $this->calcularResumenAsistencia($matricula);

        return view('portal.estudiante.boletin', compact(
            'estudiante', 'schoolYear', 'matricula', 'periodos',
            'calificaciones', 'calificacionesAcademicas', 'resumenAsistencia'
        ));
    }

    // ── PDF del boletín ──────────────────────────────────────────────────
    public function boletinPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

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

        // Construir tablaNotas para la vista PDF del admin
        $asignaciones = collect()->merge(
            $calificaciones->map(fn($g) => $g->first()?->asignacion)->filter()
        )->merge(
            $calificacionesAcademicas->map(fn($c) => $c->asignacion)->filter()
        )->unique('id');

        $tablaNotas = [];
        foreach ($asignaciones as $asi) {
            $esTecnica    = $asi->area === 'tecnica';
            $periodosData = [];
            $notasValidas = [];

            if ($esTecnica) {
                $calsPorPeriodo = $calificaciones->get($asi->id, collect())->keyBy('periodo_id');
                foreach ($periodos as $p) {
                    $notaPeriodo = $calsPorPeriodo->get($p->id)?->nota_final;
                    $periodosData[$p->id] = $notaPeriodo;
                    if ($notaPeriodo !== null) $notasValidas[] = $notaPeriodo;
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
                            $pb = (float) $pb;
                            $cv = ($rv !== null && $pb < 70) ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2) : round($pb, 2);
                            $vals[] = $cv;
                        }
                    }
                    $periodosData[$p->id] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
                }
                $promedio  = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                $situacion = $cal?->situacion;
            }

            $tablaNotas[] = [
                'asignatura' => $asi->asignatura?->nombre ?? '—',
                'esTecnica'  => $esTecnica,
                'periodos'   => $periodosData,
                'promedio'   => $promedio,
                'situacion'  => $situacion,
            ];
        }

        $boletinConfig = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $data = compact('matricula', 'periodos', 'tablaNotas', 'schoolYear', 'boletinConfig');
        $data['asistencias'] = collect();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)
            ->setPaper('letter', 'portrait');

        $apellidos = \Illuminate\Support\Str::slug($matricula->estudiante->apellidos ?? 'boletin');
        return $pdf->download("boletin_{$apellidos}.pdf");
    }

    // ── Marcar notificaciones como leídas ───────────────────────────────
    public function marcarNotificacionLeida(Request $request, Notificacion $notificacion)
    {
        if ($notificacion->user_id !== auth()->id()) abort(403);

        $notificacion->update(['leida' => true, 'leida_en' => now()]);
        Cache::forget('user_' . auth()->id() . '_notif_unread');

        return response()->json(['ok' => true]);
    }

    public function marcarTodasLeidas()
    {
        Notificacion::where('user_id', auth()->id())
            ->noLeidas()
            ->update(['leida' => true, 'leida_en' => now()]);
        Cache::put('user_' . auth()->id() . '_notif_unread', 0, 15);

        return response()->json(['ok' => true]);
    }

    // ── Planificaciones publicadas de mis docentes ───────────────────────
    public function planificaciones()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $planificaciones = collect();

        if ($matricula) {
            // Asignaciones del grupo del estudiante
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

        return view('portal.estudiante.planificaciones', compact(
            'estudiante', 'schoolYear', 'matricula', 'planificaciones'
        ));
    }

    // ── Planificaciones PDF ──────────────────────────────────────────────
    public function planificacionesPdf()
    {
        $estudiante      = $this->getEstudiante();
        $schoolYear      = SchoolYear::actual();

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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.estudiante.planificaciones_pdf',
            compact('estudiante', 'matricula', 'schoolYear', 'planificaciones', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('Planificaciones_' . \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est') . '.pdf');
    }

    // ── Planificaciones Excel ────────────────────────────────────────────
    public function planificacionesExcel()
    {
        $estudiante = $this->getEstudiante();
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

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', ($schoolYear?->nombre ?? '') . ' — ' . ($matricula?->grupo?->nombre_completo ?? ''));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Asignatura', 'Módulo / Título', 'Código MF', 'R.A.', 'Actividades'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($planificaciones as $idx => $plan) {
            $row = $idx + 6;
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
        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est');
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "planificaciones_{$slug}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Recursos de la materia ───────────────────────────────────────────
    public function recursos(Asignacion $asignacion)
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        // Verificar que el estudiante está matriculado en el grupo de esta asignación
        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')
            ->first();

        if (! $matricula) abort(403);

        $recursos = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.estudiante.recursos', compact('asignacion', 'schoolYear', 'recursos', 'matricula'));
    }

    // ── PDF de recursos de materia ───────────────────────────────────────
    public function recursosPdf(Asignacion $asignacion)
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')->first();

        if (! $matricula) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        $recursos = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')->orderByDesc('created_at')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.estudiante.recursos_pdf', compact(
            'asignacion', 'recursos', 'inst', 'schoolYear'
        ))->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'recursos');
        return $pdf->download("recursos_{$slug}.pdf");
    }

    // ── Excel recursos de materia ─────────────────────────────────────────
    public function recursosExcel(Asignacion $asignacion)
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('grupo_id', $asignacion->grupo_id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('estado', 'activa')->first();

        if (! $matricula) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion', 'docente']);

        $recursos = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->where('publicado', true)
            ->orderBy('orden')->orderByDesc('created_at')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Recursos');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? '') . ' · ' . ($asignacion->grupo?->nombre_completo ?? ''));
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
        $filename = "recursos_{$slug}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Helpers privados ─────────────────────────────────────────────────

    private function calcularResumenAsistencia($matricula): array
    {
        if (! $matricula) return ['total' => 0, 'presentes' => 0, 'ausentes' => 0, 'tardanzas' => 0, 'porcentaje' => null, 'por_materia' => []];

        $asistencias = Asistencia::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)->get();

        $total     = $asistencias->count();
        $presentes = $asistencias->whereIn('estado', ['presente', 'tardanza'])->count();
        $ausentes  = $asistencias->where('estado', 'ausente')->count();
        $tardanzas = $asistencias->where('estado', 'tardanza')->count();

        // Desglose por materia
        $porMateria = [];
        foreach ($asistencias->groupBy('asignacion_id') as $asigId => $grupo) {
            $nombreAsig = $grupo->first()?->asignacion?->asignatura?->nombre ?? 'Materia';
            $t = $grupo->count();
            $p = $grupo->whereIn('estado', ['presente', 'tardanza'])->count();
            $porMateria[] = [
                'asignatura' => $nombreAsig,
                'total'      => $t,
                'presentes'  => $p,
                'ausentes'   => $grupo->where('estado', 'ausente')->count(),
                'porcentaje' => $t > 0 ? round($p / $t * 100, 1) : 0,
            ];
        }
        usort($porMateria, fn($a, $b) => $a['asignatura'] <=> $b['asignatura']);

        return [
            'total'      => $total,
            'presentes'  => $presentes,
            'ausentes'   => $ausentes,
            'tardanzas'  => $tardanzas,
            'porcentaje' => $total > 0 ? round($presentes / $total * 100, 1) : null,
            'por_materia'=> $porMateria,
        ];
    }

    // ── Página de Asistencia ─────────────────────────────────────────────
    public function asistencia()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumenAsistencia = $this->calcularResumenAsistencia($matricula);

        return view('portal.estudiante.asistencia', compact('estudiante', 'matricula', 'schoolYear', 'resumenAsistencia'));
    }

    // ── Página de Observaciones ──────────────────────────────────────────
    public function observaciones()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $observaciones = $matricula
            ? \App\Models\Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
                ->delEstudiante($estudiante->id)
                ->publicas()
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('portal.estudiante.observaciones', compact('estudiante', 'matricula', 'schoolYear', 'observaciones'));
    }

    // ── Observaciones PDF ────────────────────────────────────────────────
    public function observacionesPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $observaciones = $matricula
            ? \App\Models\Observacion::with(['docente', 'asignacion.asignatura', 'periodo'])
                ->delEstudiante($estudiante->id)
                ->publicas()
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.estudiante.observaciones_pdf',
            compact('estudiante', 'matricula', 'schoolYear', 'observaciones', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('observaciones_' . \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est') . '.pdf');
    }

    public function observacionesExcel()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $observaciones = $matricula
            ? \App\Models\Observacion::with(['docente', 'asignacion.asignatura'])
                ->delEstudiante($estudiante->id)
                ->publicas()
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Observaciones');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Observaciones — ' . $estudiante->nombre_completo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Docente', 'Asignatura', 'Tipo', 'Observación', 'Fecha'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($observaciones as $i => $obs) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $obs->docente?->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", $obs->asignacion?->asignatura?->nombre ?? '—');
            $ws->setCellValue("D{$row}", ucfirst($obs->tipo ?? '—'));
            $ws->setCellValue("E{$row}", $obs->texto ?? '—');
            $ws->setCellValue("F{$row}", $obs->created_at?->format('d/m/Y') ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        $ws->getColumnDimension('E')->setWidth(55);
        foreach (['A', 'B', 'C', 'D', 'F'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est');
        $filename = "observaciones_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Asistencia PDF ───────────────────────────────────────────────────
    public function asistenciaPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumenAsistencia = $this->calcularResumenAsistencia($matricula);
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.estudiante.asistencia_pdf',
            compact('estudiante', 'matricula', 'schoolYear', 'resumenAsistencia', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('asistencia_' . \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est') . '.pdf');
    }

    public function asistenciaExcel()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $resumen = $this->calcularResumenAsistencia($matricula);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Asistencia');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Resumen de Asistencia — ' . $estudiante->nombre_completo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->setCellValue('A2', 'Total clases'); $ws->setCellValue('B2', $resumen['total']);
        $ws->setCellValue('C2', 'Presentes');    $ws->setCellValue('D2', $resumen['presentes']);
        $ws->setCellValue('A3', 'Ausentes');     $ws->setCellValue('B3', $resumen['ausentes']);
        $ws->setCellValue('C3', 'Tardanzas');    $ws->setCellValue('D3', $resumen['tardanzas']);
        $ws->setCellValue('A4', 'Porcentaje');   $ws->setCellValue('B4', ($resumen['porcentaje'] ?? '—') . '%');

        $headers = ['#', 'Asignatura', 'Total', 'Presentes', 'Ausentes', '%'];
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
        $slug     = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'est');
        $filename = "asistencia_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Constancia de matrícula ──────────────────────────────────────────
    public function constancia()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
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

    // ── Página de comunicados del portal ────────────────────────────────
    public function comunicados()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $comunicados = \App\Models\Comunicado::publicados()
            ->where(function ($q) use ($matricula) {
                $q->where('tipo_destinatarios', 'todos')
                  ->orWhere('tipo_destinatarios', 'estudiantes');
                if ($matricula) {
                    $q->orWhere(function ($s) use ($matricula) {
                        $s->where('tipo_destinatarios', 'grupo')
                          ->where('grupo_id', $matricula->grupo_id);
                    });
                }
            })
            ->with('autor')
            ->latest('published_at')
            ->paginate(15);

        return view('portal.estudiante.comunicados', compact('comunicados', 'estudiante'));
    }

    public function comunicadosPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $comunicados = \App\Models\Comunicado::publicados()
            ->where(function ($q) use ($matricula) {
                $q->where('tipo_destinatarios', 'todos')
                  ->orWhere('tipo_destinatarios', 'estudiantes');
                if ($matricula) {
                    $q->orWhere(function ($s) use ($matricula) {
                        $s->where('tipo_destinatarios', 'grupo')
                          ->where('grupo_id', $matricula->grupo_id);
                    });
                }
            })
            ->with('autor')
            ->latest('published_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.estudiante.comunicados_pdf', compact(
            'comunicados', 'estudiante', 'inst', 'schoolYear'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('comunicados.pdf');
    }

    // ── Comunicados Excel ────────────────────────────────────────────────
    public function comunicadosExcel()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $comunicados = \App\Models\Comunicado::publicados()
            ->where(function ($q) use ($matricula) {
                $q->where('tipo_destinatarios', 'todos')
                  ->orWhere('tipo_destinatarios', 'estudiantes');
                if ($matricula) {
                    $q->orWhere(function ($s) use ($matricula) {
                        $s->where('tipo_destinatarios', 'grupo')
                          ->where('grupo_id', $matricula->grupo_id);
                    });
                }
            })
            ->with('autor')
            ->latest('published_at')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Comunicados');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', $inst);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:E2');
        $ws->setCellValue('A2', 'Comunicados — ' . $estudiante->nombre_completo);
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $ws->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Título', 'Dirigido a', 'Fecha', 'Contenido'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '4', $h);
        }
        $ws->getStyle('A4:E4')->applyFromArray($hdrStyle);

        foreach ($comunicados as $i => $com) {
            $row = $i + 5;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $com->titulo ?? '—');
            $ws->setCellValue("C{$row}", ucfirst($com->tipo_destinatarios ?? 'todos'));
            $ws->setCellValue("D{$row}", $com->published_at?->format('d/m/Y') ?? '—');
            $ws->setCellValue("E{$row}", \Illuminate\Support\Str::limit(strip_tags($com->contenido ?? ''), 200));
            $ws->getStyle("E{$row}")->getAlignment()->setWrapText(true);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
        }

        foreach (['A','B','C','D'] as $col) $ws->getColumnDimension($col)->setAutoSize(true);
        $ws->getColumnDimension('E')->setWidth(50);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'com_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'comunicados_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Página de horario (vista web) ──────────────────────────────────
    public function horario()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($matricula, $schoolYear);

        return view('portal.estudiante.horario', compact(
            'estudiante', 'matricula', 'schoolYear',
            'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig'
        ));
    }

    // ── Mis notas PDF ───────────────────────────────────────────────────
    public function notasPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
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

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'mis-notas');
        return $pdf->download("mis_notas_{$slug}.pdf");
    }

    // ── Excel notas del estudiante ───────────────────────────────────────
    public function notasExcel()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404);

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()->groupBy('periodo_id');

        $calAcad = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Mis Notas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $headers = ['Asignatura'];
        foreach ($periodos as $p) { $headers[] = 'P' . $p->numero; }
        $headers[] = 'Promedio';
        $lastCol = chr(64 + count($headers));

        $ws->setCellValue('A1', 'Mis Notas — ' . $estudiante->nombre_completo . ' — ' . ($schoolYear?->nombre ?? ''));
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        foreach ($headers as $i => $h) { $ws->setCellValue(chr(65 + $i) . '3', $h); }
        $ws->getStyle("A3:{$lastCol}3")->applyFromArray($hdrStyle);

        $row = 4;
        $asigIds = collect();
        foreach ($periodos as $p) {
            foreach (($calificaciones->get($p->id) ?? collect()) as $cal) {
                $asigIds->push($cal->asignacion_id);
            }
        }
        foreach ($asigIds->unique() as $asigId) {
            $nombre = null;
            $notasFinal = [];
            foreach ($periodos as $p) {
                $cal = $calificaciones->get($p->id)?->firstWhere('asignacion_id', $asigId);
                if (! $nombre) $nombre = $cal?->asignacion?->asignatura?->nombre ?? '—';
            }
            $ws->setCellValue("A{$row}", $nombre);
            $col = 1;
            foreach ($periodos as $p) {
                $nota = $calificaciones->get($p->id)?->firstWhere('asignacion_id', $asigId)?->nota_final;
                $ws->setCellValueByColumnAndRow($col + 1, $row, $nota ?? '');
                if ($nota !== null) $notasFinal[] = $nota;
                $col++;
            }
            $prom = count($notasFinal) ? round(array_sum($notasFinal) / count($notasFinal), 1) : '';
            $ws->setCellValueByColumnAndRow($col + 1, $row, $prom);
            if ($row % 2 === 0) {
                $ws->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
            $row++;
        }

        foreach ($calAcad as $ca) {
            $ws->setCellValue("A{$row}", ($ca->asignacion?->asignatura?->nombre ?? '—') . ' (Acad.)');
            $ws->setCellValueByColumnAndRow(count($headers), $row, $ca->nota_final ?? '');
            $row++;
        }

        foreach (range('A', $lastCol) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'notas_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($estudiante->apellidos ?? 'estudiante');
        return response()->download($tmp, "mis_notas_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Horario PDF del estudiante ───────────────────────────────────────
    public function horarioPdf()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
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

    // ── Horario del estudiante Excel ─────────────────────────────────────
    public function horarioExcel()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
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

        $lastCol = chr(65 + count($dias));

        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValue('A1', $inst);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells("A2:{$lastCol}2");
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
            $ws->setCellValue("A{$row}", ($franja->hora_inicio ?? '') . '-' . ($franja->hora_fin ?? ''));
            foreach ($dias as $k => $dia) {
                $col    = chr(66 + $k);
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

        foreach (range('A', $lastCol) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'hor_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'estudiante');
        return response()->download($tmp, "horario_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Mis Logros ───────────────────────────────────────────────────────
    public function logros()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        $logros = [];

        // ── Asistencia Perfecta (≥ 95%) ──
        $resumenAsist = $this->calcularResumenAsistencia($matricula);
        $pctAsist = $resumenAsist['porcentaje'];
        $logros['asistencia_perfecta'] = [
            'titulo'      => 'Asistencia Perfecta',
            'descripcion' => 'Mantiene un porcentaje de asistencia del 95% o más.',
            'icono'       => 'bi-calendar-check-fill',
            'obtenido'    => $pctAsist !== null && $pctAsist >= 95,
            'valor'       => $pctAsist !== null ? $pctAsist . '%' : null,
        ];

        // ── Estudiante Destacado (promedio ≥ 85) ──
        $todasNotas = collect();
        if ($matricula) {
            $cals = Calificacion::with('periodo')
                ->where('matricula_id', $matricula->id)
                ->where('publicado', true)
                ->whereNotNull('nota_final')
                ->pluck('nota_final');

            $calsAcad = CalificacionAcademica::where('matricula_id', $matricula->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->whereNotNull('nota_final')
                ->pluck('nota_final');

            $todasNotas = $cals->merge($calsAcad)->filter();
        }
        $promedioGeneral = $todasNotas->count() ? round($todasNotas->avg(), 1) : null;
        $logros['estudiante_destacado'] = [
            'titulo'      => 'Estudiante Destacado',
            'descripcion' => 'Promedio general de 85 puntos o más.',
            'icono'       => 'bi-star-fill',
            'obtenido'    => $promedioGeneral !== null && $promedioGeneral >= 85,
            'valor'       => $promedioGeneral,
        ];

        // ── Mejora Continua (P2 > P1 o P3 > P2) ──
        $mejoraContinua = false;
        $detalleMejora  = null;
        if ($matricula && $schoolYear) {
            $periodos = $this->getPeriodos($schoolYear);
            $promediosPorPeriodo = [];

            foreach ($periodos as $periodo) {
                $notas = Calificacion::where('matricula_id', $matricula->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('publicado', true)
                    ->whereNotNull('nota_final')
                    ->pluck('nota_final');
                if ($notas->count()) {
                    $promediosPorPeriodo[$periodo->numero] = round($notas->avg(), 1);
                }
            }

            $numeros = array_keys($promediosPorPeriodo);
            sort($numeros);
            for ($i = 1; $i < count($numeros); $i++) {
                $prev = $promediosPorPeriodo[$numeros[$i - 1]];
                $curr = $promediosPorPeriodo[$numeros[$i]];
                if ($curr > $prev) {
                    $mejoraContinua = true;
                    $detalleMejora  = "P{$numeros[$i-1]}: {$prev} → P{$numeros[$i]}: {$curr}";
                    break;
                }
            }
        }
        $logros['mejora_continua'] = [
            'titulo'      => 'Mejora Continua',
            'descripcion' => 'El promedio de un período supera al anterior (P2 > P1 o P3 > P2).',
            'icono'       => 'bi-graph-up-arrow',
            'obtenido'    => $mejoraContinua,
            'valor'       => $detalleMejora,
        ];

        // ── Sin Faltas Disciplinarias ──
        $sinFaltas = true;
        if ($matricula) {
            $hayFaltas = \DB::table('faltas_disciplinarias')
                ->where('matricula_id', $matricula->id)
                ->exists();
            $sinFaltas = ! $hayFaltas;
        }
        $logros['sin_faltas'] = [
            'titulo'      => 'Sin Faltas Disciplinarias',
            'descripcion' => 'No registra faltas disciplinarias en el período actual.',
            'icono'       => 'bi-shield-check',
            'obtenido'    => $sinFaltas,
            'valor'       => null,
        ];

        return view('portal.estudiante.logros', compact(
            'estudiante', 'schoolYear', 'matricula', 'logros', 'promedioGeneral'
        ));
    }

    private function cargarHorario($matricula, $schoolYear): array
    {
        $grid    = [];
        $franjas = collect();
        $horario = null;
        $dias    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        if ($matricula && $schoolYear) {
            $horario = Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')
                ->latest()
                ->first();

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

    // ── Encuestas disponibles para el estudiante ─────────────────────────
    public function encuestas()
    {
        $encuestas = Encuesta::activas()
            ->dirigidaA('estudiantes')
            ->withCount('preguntas')
            ->latest()
            ->get()
            ->map(function ($encuesta) {
                $encuesta->_ya_respondio = $encuesta->yaRespondio(auth()->id());
                return $encuesta;
            });

        return view('portal.estudiante.encuestas', compact('encuestas'));
    }

    // ── Mostrar formulario de respuesta (GET) ─────────────────────────────
    public function verEncuesta(Encuesta $encuesta)
    {
        abort_unless($encuesta->activo, 403, 'Esta encuesta no está disponible.');
        abort_if($encuesta->fecha_cierre && $encuesta->fecha_cierre->isPast(), 403, 'La encuesta ha cerrado.');

        if ($encuesta->yaRespondio(auth()->id())) {
            return redirect()->route('portal.estudiante.encuestas')
                             ->with('success', 'Ya has respondido esta encuesta anteriormente.');
        }

        $encuesta->load('preguntas.opciones');

        return view('portal.estudiante.encuestas_responder', compact('encuesta'));
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

        return redirect()->route('portal.estudiante.encuestas')
                         ->with('success', '¡Gracias por participar! Tus respuestas han sido registradas.');
    }

    // ── Mis Documentos ───────────────────────────────────────────────────
    public function misDocumentos()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        // Períodos del año escolar actual (para boletines por período)
        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        return view('portal.estudiante.mis_documentos', compact(
            'estudiante', 'schoolYear', 'matricula', 'periodos'
        ));
    }

    // ── Eventos del centro ───────────────────────────────────────────────────
    public function eventos()
    {
        $estudiante = $this->getEstudiante();

        $eventos = Evento::activos()
            ->withCount('inscripciones')
            ->orderBy('fecha_inicio')
            ->get()
            ->map(function (Evento $evento) use ($estudiante) {
                $evento->_inscrito = InscripcionEvento::where('evento_id', $evento->id)
                    ->where('estudiante_id', $estudiante->id)
                    ->exists();

                $evento->_cupos_disponibles = is_null($evento->cupo_maximo)
                    ? null
                    : max(0, $evento->cupo_maximo - $evento->inscripciones_count);

                $evento->_lleno = ! is_null($evento->cupo_maximo)
                    && $evento->inscripciones_count >= $evento->cupo_maximo;

                return $evento;
            });

        return view('portal.estudiante.eventos', compact('estudiante', 'eventos'));
    }

    // ── Inscribirse en un evento (POST) ──────────────────────────────────────
    public function inscribirseEvento(Request $request, Evento $evento)
    {
        $estudiante = $this->getEstudiante();

        abort_unless($evento->activo, 403, 'Este evento no está disponible.');

        $yaInscrito = InscripcionEvento::where('evento_id', $evento->id)
            ->where('estudiante_id', $estudiante->id)
            ->exists();

        if ($yaInscrito) {
            return back()->with('info', 'Ya estás inscrito en este evento.');
        }

        if (! is_null($evento->cupo_maximo)) {
            $inscritos = InscripcionEvento::where('evento_id', $evento->id)->count();
            if ($inscritos >= $evento->cupo_maximo) {
                return back()->with('error', 'Lo sentimos, el evento ya no tiene cupo disponible.');
            }
        }

        InscripcionEvento::create([
            'evento_id'         => $evento->id,
            'estudiante_id'     => $estudiante->id,
            'fecha_inscripcion' => now()->toDateString(),
            'asistio'           => false,
        ]);

        return back()->with('success', '¡Te has inscrito en "' . $evento->nombre . '" exitosamente!');
    }

    // ── Proyectos escolares ──────────────────────────────────────────────────
    public function proyectos()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        // Proyectos donde el estudiante es integrante
        $misProyectos = ProyectoEscolar::with(['fases', 'integrantes.estudiante', 'tutor', 'schoolYear'])
            ->whereHas('integrantes', fn($q) => $q->where('estudiante_id', $estudiante->id))
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProyectoEscolar $proyecto) use ($estudiante) {
                $integrante = $proyecto->integrantes->firstWhere('estudiante_id', $estudiante->id);
                $proyecto->_rol       = $integrante?->rol ?? 'integrante';
                $proyecto->_rol_label = $integrante?->rol_label ?? 'Integrante';
                return $proyecto;
            });

        // Proyectos del año actual donde NO es integrante (para explorar)
        $todosProyectos = ProyectoEscolar::with(['fases', 'integrantes', 'tutor'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotIn('id', $misProyectos->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProyectoEscolar $proyecto) {
                $proyecto->_rol       = null;
                $proyecto->_rol_label = null;
                return $proyecto;
            });

        return view('portal.estudiante.proyectos', compact(
            'estudiante', 'schoolYear', 'misProyectos', 'todosProyectos'
        ));
    }

    // ── Mis Tareas ────────────────────────────────────────────────────────
    public function tareas()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        if (! $matricula) {
            return view('portal.estudiante.tareas', [
                'tareas'    => collect(),
                'entregas'  => collect(),
                'estudiante'=> $estudiante,
            ]);
        }

        // Asignaciones activas del grupo
        $asignaciones = \App\Models\Asignacion::with('asignatura')
            ->where('grupo_id', $matricula->grupo_id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $asignacionIds = $asignaciones->pluck('id');

        // Tareas activas de esas asignaciones
        $tareas = \App\Models\Tarea::with('asignacion.asignatura')
            ->whereIn('asignacion_id', $asignacionIds)
            ->where('activo', true)
            ->orderBy('fecha_limite')
            ->get();

        // Entregas del estudiante
        $entregas = \App\Models\EntregaTarea::where('estudiante_id', $estudiante->id)
            ->whereIn('tarea_id', $tareas->pluck('id'))
            ->get()
            ->keyBy('tarea_id');

        return view('portal.estudiante.tareas', compact('tareas', 'entregas', 'estudiante'));
    }

    // ── Mis Puntos (Gamificación) ────────────────────────────────────────
    public function misPuntos()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        // ── Total de puntos y desglose por categoría ──────────────────
        $totalPuntos = 0;
        $historial   = collect();
        $puntosCategoria = [];
        $insigniasObtenidas = collect();
        $ranking = collect();
        $miPosicion = null;

        if ($matricula) {
            $totalPuntos = PuntoEstudiante::where('matricula_id', $matricula->id)->sum('puntos');

            $historial = PuntoEstudiante::where('matricula_id', $matricula->id)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit(50)
                ->get();

            // Desglose por categoría
            foreach (PuntoEstudiante::CATEGORIAS as $cat => $info) {
                $puntosCategoria[$cat] = PuntoEstudiante::where('matricula_id', $matricula->id)
                    ->where('categoria', $cat)
                    ->sum('puntos');
            }

            // Insignias obtenidas
            $insigniasObtenidas = InsigniaEstudiante::where('matricula_id', $matricula->id)->get()->keyBy('tipo');

            // Ranking del grupo (top 10)
            $matriculasGrupo = Matricula::with('estudiante')
                ->where('grupo_id', $matricula->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get();

            $ranking = $matriculasGrupo->map(function (Matricula $m) {
                return [
                    'matricula_id' => $m->id,
                    'nombre'       => $m->estudiante?->nombre_completo ?? '—',
                    'total'        => PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos'),
                ];
            })->sortByDesc('total')->values()->take(10);

            // Posición del estudiante en el grupo
            $allRanking = $matriculasGrupo->map(function (Matricula $m) {
                return [
                    'matricula_id' => $m->id,
                    'total'        => PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos'),
                ];
            })->sortByDesc('total')->values();

            $miPosicion = $allRanking->search(fn($r) => $r['matricula_id'] === $matricula->id);
            $miPosicion = $miPosicion !== false ? $miPosicion + 1 : null;
        }

        return view('portal.estudiante.mis_puntos', compact(
            'estudiante', 'schoolYear', 'matricula',
            'totalPuntos', 'historial', 'puntosCategoria',
            'insigniasObtenidas', 'ranking', 'miPosicion'
        ));
    }

    public function misPrestamos()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $prestamosActivos = \App\Models\PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('estado', ['activo', 'vencido'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $historial = \App\Models\PrestamoBiblioteca::with('libro')
            ->where('estudiante_id', $estudiante->id)
            ->where('estado', 'devuelto')
            ->orderByDesc('fecha_devolucion')
            ->limit(20)
            ->get();

        return view('portal.estudiante.mis_prestamos', compact(
            'estudiante', 'schoolYear', 'prestamosActivos', 'historial'
        ));
    }

    public function misPagos()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        if (!$matricula) {
            return view('portal.estudiante.mis_pagos', [
                'estudiante' => $estudiante,
                'pagos'      => collect(),
                'totales'    => ['pagado' => 0, 'pendiente' => 0, 'vencido' => 0],
                'matricula'  => null,
            ]);
        }

        Pago::sincronizarVencidos();

        $pagos = Pago::where('matricula_id', $matricula->id)
            ->orderByDesc('fecha_vencimiento')
            ->get();

        $totales = [
            'pagado'    => $pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $pagos->where('estado', 'pendiente')->sum('monto'),
            'vencido'   => $pagos->where('estado', 'vencido')->sum('monto'),
        ];

        return view('portal.estudiante.mis_pagos', compact(
            'estudiante', 'schoolYear', 'matricula', 'pagos', 'totales'
        ));
    }

    public function iniciarPago(Pago $pago)
    {
        $estudiante = $this->getEstudiante();

        // Verificar que el pago pertenece a este estudiante
        $matricula = $estudiante->matriculas()->where('id', $pago->matricula_id)->first();
        abort_if(! $matricula, 403);
        abort_if(! in_array($pago->estado, ['pendiente', 'vencido']), 422, 'Este pago ya no puede procesarse en línea.');

        if (! CardNetService::isConfigured()) {
            return back()->with('error', 'El pago en línea no está configurado. Contacta la administración.');
        }

        $orderId = 'P' . str_pad($pago->id, 11, '0', STR_PAD_LEFT);
        $result  = CardNetService::createCheckoutParams($orderId, (float) $pago->monto, [
            'pago_id'    => $pago->id,
            'origen'     => 'portal_estudiante',
        ]);

        $token = \Illuminate\Support\Str::random(32);
        cache()->put("cardnet_form_{$token}", $result, now()->addMinutes(20));

        return redirect()->route('cardnet.checkout', $token);
    }

    public function reciboPago(Pago $pago)
    {
        $estudiante = $this->getEstudiante();
        $matricula  = $estudiante->matriculas()->where('id', $pago->matricula_id)->first();
        abort_if(! $matricula, 403);
        abort_if($pago->estado !== 'pagado', 422, 'Solo se puede generar recibo de pagos confirmados.');

        $pago->load(['matricula.estudiante.representantes', 'matricula.grupo.grado', 'matricula.grupo.seccion', 'registrador']);

        $mon    = \App\Helpers\Setting::get('payments_currency', 'DOP');
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.recibo_pdf',
            compact('pago', 'inst', 'dir', 'mon', 'config')
        )->setPaper([0, 0, 340, 500], 'portrait');

        return $pdf->download('recibo_' . $pago->id . '_' . now()->format('Ymd') . '.pdf');
    }

    public function miSaldoCafeteria()
    {
        $estudiante     = $this->getEstudiante();
        $saldo          = \App\Models\VentaCafeteria::saldoEstudiante($estudiante->id);
        $historial      = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->latest()->limit(50)->get();
        $totalRecargado = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->where('tipo', 'recarga')->sum('monto');
        $totalGastado   = \App\Models\VentaCafeteria::where('estudiante_id', $estudiante->id)
                            ->where('tipo', 'venta')->sum('monto');

        return view('portal.estudiante.mi_saldo_cafeteria', compact(
            'estudiante', 'saldo', 'historial', 'totalRecargado', 'totalGastado'
        ));
    }

    public function certificadoCalificaciones()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear', 'estudiante.representantes'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()->first();

        if (! $matricula) abort(404, 'Sin matrícula activa.');

        $periodos = $schoolYear
            ? $this->getPeriodos($schoolYear)
            : collect();

        $calificaciones = \App\Models\Calificacion::with(['asignacion.asignatura', 'periodo'])
            ->where('matricula_id', $matricula->id)
            ->where('publicado', true)
            ->get()->groupBy('asignacion_id');

        $calificacionesAcademicas = CalificacionAcademica::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->whereNotNull('nota_final')
            ->where('publicado', true)
            ->orderBy('id')
            ->get();

        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.estudiante.certificado_calificaciones_pdf',
            compact('matricula', 'periodos', 'calificaciones', 'calificacionesAcademicas',
                    'config', 'si', 'dir', 'cod')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'certificado');
        return $pdf->download("certificado_calificaciones_{$slug}.pdf");
    }

    public function cartaBuenaConducta()
    {
        $estudiante = $this->getEstudiante();
        $schoolYear = SchoolYear::actual();
        abort_if(! $schoolYear, 404);

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->latest()->first();

        abort_if(! $matricula, 404, 'Sin matrícula activa.');

        // Determinar nivel de conducta según faltas registradas
        $faltas = \Illuminate\Support\Facades\DB::table('faltas_disciplinarias')
            ->where('estudiante_id', $estudiante->id)
            ->whereYear('fecha', now()->year)
            ->count();

        $nivelConducta = match(true) {
            $faltas === 0         => 'EXCELENTE',
            $faltas <= 2          => 'BUENA',
            $faltas <= 5          => 'REGULAR',
            default               => 'DEFICIENTE',
        };

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $config = \App\Models\BoletinConfig::getOrCreate($schoolYear->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.perfiles.certificado_conducta_pdf',
            compact('estudiante', 'matricula', 'si', 'dir', 'cod', 'config', 'schoolYear', 'nivelConducta', 'faltas')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($estudiante->nombre_completo ?? 'conducta');
        return $pdf->download("carta_conducta_{$slug}.pdf");
    }

    public function historialAcademico()
    {
        $estudiante = $this->getEstudiante();

        $matriculas = $estudiante->matriculas()
            ->with(['schoolYear', 'grupo.grado', 'grupo.seccion'])
            ->orderByDesc('school_year_id')
            ->get()
            ->map(function (Matricula $m) {
                // Promedio CalificacionAcademica (MINERD)
                $notaFinal = CalificacionAcademica::where('matricula_id', $m->id)
                    ->whereNotNull('nota_final')
                    ->avg('nota_final');

                // Si no hay MINERD, intentar con Calificacion técnica
                if (is_null($notaFinal)) {
                    $notaFinal = \App\Models\Calificacion::where('matricula_id', $m->id)
                        ->whereNotNull('nota_final')
                        ->avg('nota_final');
                }

                // Asistencia total vs presentes
                $totalAsist    = \App\Models\Asistencia::where('matricula_id', $m->id)->count();
                $presenteAsist = \App\Models\Asistencia::where('matricula_id', $m->id)->where('estado', 'presente')->count();
                $pctAsistencia = $totalAsist > 0 ? round($presenteAsist / $totalAsist * 100, 1) : null;

                // Conteo de asignaturas con calificación
                $asignaturas = CalificacionAcademica::where('matricula_id', $m->id)->distinct('asignacion_id')->count('asignacion_id');

                return [
                    'matricula'      => $m,
                    'nota_promedio'  => $notaFinal ? round($notaFinal, 1) : null,
                    'pct_asistencia' => $pctAsistencia,
                    'asignaturas'    => $asignaturas,
                ];
            });

        return view('portal.estudiante.historial_academico', compact('estudiante', 'matriculas'));
    }

    public function miRutaTransporte()
    {
        $estudiante = $this->getEstudiante();
        $asignacion = \App\Models\EstudianteRuta::where('estudiante_id', $estudiante->id)
                        ->with(['ruta.paradas', 'parada'])
                        ->latest()->first();
        $ruta = $asignacion?->ruta;

        return view('portal.estudiante.mi_ruta_transporte', compact(
            'estudiante', 'asignacion', 'ruta'
        ));
    }

    // ── Calendario escolar ───────────────────────────────────────────────
    public function calendario()
    {
        $schoolYear = SchoolYear::actual();
        $eventos    = $this->calendarEventos(['todos', 'estudiantes']);
        return view('portal.estudiante.calendario', compact('schoolYear', 'eventos'));
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

        $evs = Evento::activos()->get()->map(fn($e) => [
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

        return $cal->concat($evs)->concat($pers)->values()->all();
    }
}
