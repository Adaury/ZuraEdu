<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Comunicado;
use App\Models\Docente;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\RecursoMateria;
use App\Models\SchoolYear;
use App\Models\Suplencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalDocenteController extends Controller
{
    private function getDocente(): Docente
    {
        $docente = Docente::where('user_id', auth()->id())->first();

        if (! $docente) {
            abort(403, 'No tienes un perfil de docente asociado a esta cuenta.');
        }

        return $docente;
    }

    // ── Dashboard del docente ────────────────────────────────────────────
    public function dashboard()
    {
        // Sin perfil → guiar al wizard de configuración
        $docente = Docente::where('user_id', auth()->id())->first();
        if (! $docente) {
            return redirect()->route('portal.docente.setup')
                ->with('info', 'Completa tu perfil para empezar. Indica tus materias y grupos.');
        }

        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        // Asignaciones activas — cacheadas 5 min por docente
        $asignaciones = Cache::remember(
            "portal_docente_{$docente->id}_asignaciones_{$syId}", 300,
            fn() => Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
                ->where('docente_id', $docente->id)
                ->where('activo', true)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
                ->get()
        );

        // Horario personal
        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($docente, $schoolYear);

        // Período activo
        $periodoActivo = $schoolYear
            ? Cache::remember("periodo_activo_{$syId}", 600,
                fn() => Periodo::where('school_year_id', $syId)->where('activo', true)->first())
            : null;

        // Estadísticas — 1 query en lugar de N (fix N+1 crítico)
        $grupoIds = $asignaciones->pluck('grupo_id')->unique()->values();
        $totalEstudiantes = $grupoIds->isNotEmpty()
            ? Matricula::whereIn('grupo_id', $grupoIds)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
                ->count()
            : 0;

        $stats = [
            'grupos'      => $grupoIds->count(),
            'asignaturas' => $asignaciones->pluck('asignatura_id')->unique()->count(),
            'estudiantes' => $totalEstudiantes,
        ];

        // Comunicados recientes — cacheados 10 min (no cambian frecuentemente)
        $comunicados = Cache::remember('comunicados_recientes', 600,
            fn() => Comunicado::publicados()->orderByDesc('published_at')->limit(4)->get()
        );

        // Notificaciones no leídas — sin caché (deben ser en tiempo real)
        $notificaciones = Notificacion::where('user_id', auth()->id())
            ->noLeidas()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalNoLeidas = $notificaciones->count();

        // ── Estadísticas de rendimiento por asignación ───────────────────
        $rendimiento = $this->calcularRendimiento($asignaciones, $schoolYear, $syId);

        // Suplencias próximas (donde soy suplente o docente original) — próximos 30 días
        $suplencias = Suplencia::with(['detalle.asignacion.asignatura', 'detalle.asignacion.grupo.grado',
                                        'detalle.asignacion.grupo.seccion', 'detalle.franja',
                                        'docenteOriginal', 'docenteSuplente'])
            ->where(function ($q) use ($docente) {
                $q->where('docente_original_id', $docente->id)
                  ->orWhere('docente_suplente_id', $docente->id);
            })
            ->where('fecha', '>=', today())
            ->where('fecha', '<=', today()->addDays(30))
            ->orderBy('fecha')
            ->get();

        return view('portal.docente.dashboard', compact(
            'docente', 'schoolYear', 'asignaciones', 'periodoActivo',
            'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig',
            'stats', 'comunicados', 'notificaciones', 'totalNoLeidas',
            'rendimiento', 'suplencias'
        ));
    }

    // ── Pasar asistencia ─────────────────────────────────────────────────
    public function asistencia(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $fecha = request('fecha', now()->toDateString());

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        // Asistencias ya registradas para esta fecha/asignación
        $registradas = Asistencia::where('asignacion_id', $asignacion->id)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('matricula_id');

        return view('portal.docente.asistencia', compact(
            'docente', 'asignacion', 'matriculas', 'fecha', 'registradas'
        ));
    }

    public function guardarAsistencia(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'fecha'                => 'required|date',
            'estados'              => 'required|array',
            'estados.*'            => 'required|in:presente,ausente,tardanza,justificado',
        ]);

        $schoolYear = SchoolYear::actual();

        foreach ($request->estados as $matriculaId => $estado) {
            Asistencia::updateOrCreate(
                [
                    'matricula_id'  => $matriculaId,
                    'asignacion_id' => $asignacion->id,
                    'fecha'         => $request->fecha,
                ],
                [
                    'estado'         => $estado,
                    'registrado_por' => auth()->id(),
                ]
            );

            // Notificar al representante si hay ausencia
            if ($estado === 'ausente') {
                $this->notificarAusencia($matriculaId, $asignacion, $request->fecha);
            }
        }

        // Alerta de asistencia crítica para estudiantes que bajen del 75%
        $this->verificarAlertasAsistencia(array_keys($request->estados), $asignacion);

        return redirect()
            ->route('portal.docente.asistencia', $asignacion)
            ->with('success', 'Asistencia registrada correctamente para el ' . $request->fecha);
    }

    // ── Ver estudiantes del grupo ────────────────────────────────────────
    public function estudiantes(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        // Añadir promedio y asistencia a cada estudiante
        $matriculas = $matriculas->map(function ($m) use ($asignacion, $schoolYear) {
            $calif = Calificacion::where('matricula_id', $m->id)
                ->where('asignacion_id', $asignacion->id)
                ->first();

            $asistTotal  = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)->count();
            $asistPres   = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)
                ->whereIn('estado', ['presente', 'tardanza'])->count();

            $m->_nota    = $calif?->nota_final;
            $m->_letra   = $calif?->letra;
            $m->_asist   = $asistTotal > 0 ? round($asistPres / $asistTotal * 100, 1) : null;

            return $m;
        });

        return view('portal.docente.estudiantes', compact('docente', 'asignacion', 'matriculas'));
    }

    public function estudiantesPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero_orden')
            ->get()
            ->map(function ($m) use ($asignacion) {
                $calif = Calificacion::where('matricula_id', $m->id)
                    ->where('asignacion_id', $asignacion->id)->first();
                $asistTotal = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)->count();
                $asistPres  = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)
                    ->whereIn('estado', ['presente', 'tardanza'])->count();
                $m->_nota  = $calif?->nota_final;
                $m->_asist = $asistTotal > 0 ? round($asistPres / $asistTotal * 100, 1) : null;
                return $m;
            });

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.estudiantes_pdf', compact(
            'docente', 'asignacion', 'matriculas', 'schoolYear', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('Estudiantes_' . \Str::slug($asignacion->asignatura?->nombre ?? 'grupo') . '.pdf');
    }

    public function estudiantesExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        $matriculas = Matricula::with(['estudiante'])
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero_orden')
            ->get()
            ->map(function ($m) use ($asignacion) {
                $calif = Calificacion::where('matricula_id', $m->id)
                    ->where('asignacion_id', $asignacion->id)->first();
                $asistTotal = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)->count();
                $asistPres  = Asistencia::where('matricula_id', $m->id)->where('asignacion_id', $asignacion->id)
                    ->whereIn('estado', ['presente', 'tardanza'])->count();
                $m->_nota  = $calif?->nota_final;
                $m->_letra = $calif?->letra;
                $m->_asist = $asistTotal > 0 ? round($asistPres / $asistTotal * 100, 1) : null;
                return $m;
            });

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Estudiantes');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Estudiantes — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Apellidos', 'Nombres', 'Nota Final', 'Asistencia %'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($matriculas as $i => $mat) {
            $row = $i + 4;
            $est = $mat->estudiante;
            $ws->setCellValue("A{$row}", $mat->numero_orden ?? ($i + 1));
            $ws->setCellValue("B{$row}", $est?->apellidos ?? $est?->apellido ?? '—');
            $ws->setCellValue("C{$row}", $est?->nombres  ?? $est?->nombre  ?? '—');
            $ws->setCellValue("D{$row}", $mat->_nota !== null ? number_format($mat->_nota, 1) : '—');
            $ws->setCellValue("E{$row}", $mat->_asist !== null ? $mat->_asist . '%' : '—');

            if ($mat->_nota !== null && $mat->_nota < 60) {
                $ws->getStyle("D{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('fee2e2');
            } elseif ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'E') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'est');
        $filename = "estudiantes_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Calificaciones ────────────────────────────────────────────────────
    public function calificaciones(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura.resultadosAprendizaje', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')
            ->get();

        $esTecnica = $asignacion->area === 'tecnica';

        if ($esTecnica) {
            // ── Área Técnica: columnas RA por período ──────────────────────
            $ras = $asignacion->asignatura
                ->resultadosAprendizaje()
                ->where('activo', true)
                ->orderBy('numero')
                ->get();
            $numRA = $ras->count() ?: ($asignacion->asignatura->num_ra ?? 3);

            // Construir mapa de pesos: primero pesos personalizados del docente,
            // luego los globales de la asignatura, y por último distribución uniforme.
            $pesosRA = [];
            $pesosPersonalizados = $asignacion->pesos_ra ?? [];
            foreach ($ras as $ra) {
                $pesosRA[$ra->numero] = $pesosPersonalizados[$ra->numero]
                    ?? $ra->peso
                    ?? round(100 / $numRA, 4);
            }
            if (empty($pesosRA)) {
                for ($i = 1; $i <= $numRA; $i++) {
                    $pesosRA[$i] = $pesosPersonalizados[$i] ?? round(100 / $numRA, 4);
                }
            }

            $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->orderBy('numero')
                ->get();

            $periodoId = (int) request('periodo_id', $periodos->first()?->id ?? 0);
            $periodoActual = $periodos->find($periodoId);

            $calificaciones = Calificacion::where('asignacion_id', $asignacion->id)
                ->where('periodo_id', $periodoId)
                ->get()
                ->keyBy('matricula_id');

            return view('portal.docente.calificaciones', compact(
                'docente', 'asignacion', 'matriculas', 'calificaciones', 'schoolYear',
                'esTecnica', 'ras', 'numRA', 'pesosRA', 'periodos', 'periodoActual', 'periodoId'
            ));
        }

        // ── Área Académica: grilla por competencias MINERD ───────────────
        $calificaciones = CalificacionAcademica::where('asignacion_id', $asignacion->id)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->keyBy('matricula_id');

        $competencias = CalificacionAcademica::COMPETENCIAS;

        $ras = collect(); $numRA = 0; $pesosRA = [];
        $periodos = collect(); $periodoActual = null; $periodoId = null;

        return view('portal.docente.calificaciones', compact(
            'docente', 'asignacion', 'matriculas', 'calificaciones', 'schoolYear',
            'esTecnica', 'ras', 'numRA', 'pesosRA', 'periodos', 'periodoActual', 'periodoId',
            'competencias'
        ));
    }

    // ── ZIP de boletines de mis estudiantes ──────────────────────────────
    public function boletinesZip(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        if ($matriculas->count() > 50) {
            return back()->with('error', 'El grupo tiene más de 50 estudiantes. Descarga los boletines individualmente.');
        }

        $periodos        = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))->orderBy('numero')->get();
        $misAsignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()->sortBy(fn($a) => $a->asignatura?->nombre);

        $boletinConfig = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;
        $zipPath = tempnam(sys_get_temp_dir(), 'boletines_doc_') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        foreach ($matriculas as $matricula) {
            try {
                // Build tablaNotas for this student
                $califAcadMap = CalificacionAcademica::where('matricula_id', $matricula->id)
                    ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
                    ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                    ->get()->keyBy('asignacion_id');

                $periodoIds  = $periodos->pluck('id');
                $califTecMap = Calificacion::where('matricula_id', $matricula->id)
                    ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
                    ->when($periodoIds->isNotEmpty(), fn($q) => $q->whereIn('periodo_id', $periodoIds))
                    ->get()->groupBy('asignacion_id');

                $tablaNotas = [];
                foreach ($misAsignaciones as $asi) {
                    $esTec = $asi->area === 'tecnica';
                    $periodosData = []; $notasValidas = [];
                    if ($esTec) {
                        $calsPP = $califTecMap->get($asi->id, collect())->keyBy('periodo_id');
                        foreach ($periodos as $p) {
                            $n = $calsPP->get($p->id)?->nota_final;
                            $periodosData[$p->id] = $n;
                            if ($n !== null) $notasValidas[] = $n;
                        }
                        $promedio = count($notasValidas) ? round(array_sum($notasValidas)/count($notasValidas),2) : null;
                        $sit = $promedio !== null ? ($promedio >= 70 ? 'A' : 'R') : null;
                    } else {
                        $cal = $califAcadMap->get($asi->id);
                        foreach ($periodos as $p) {
                            $n = $p->numero; $vals = [];
                            for ($ci = 1; $ci <= 4; $ci++) {
                                $pb = $cal?->{"comp{$ci}_p{$n}"};
                                if ($pb !== null) { $vals[] = (float)$pb; }
                            }
                            $periodosData[$p->id] = $vals ? round(array_sum($vals)/count($vals),2) : null;
                        }
                        $promedio = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                        $sit = $cal?->situacion;
                    }
                    $tablaNotas[] = ['asignatura' => $asi->asignatura?->nombre ?? '—', 'esTecnica' => $esTec, 'periodos' => $periodosData, 'promedio' => $promedio, 'situacion' => $sit];
                }

                $data = compact('matricula', 'periodos', 'tablaNotas', 'schoolYear', 'boletinConfig');
                $data['asistencias'] = collect();

                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)
                    ->setPaper('letter', 'portrait')->output();

                $nombre = \Illuminate\Support\Str::slug(
                    ($matricula->estudiante?->apellidos ?? '') . '_' . ($matricula->estudiante?->nombres ?? '')
                );
                $zip->addFromString("boletin_{$nombre}.pdf", $pdfContent);
            } catch (\Throwable $e) {}
        }

        $zip->close();
        $slug = \Illuminate\Support\Str::slug($asignacion->grupo?->nombre_completo ?? 'grupo');
        return response()->download($zipPath, "boletines_{$slug}.zip", ['Content-Type' => 'application/zip'])
                         ->deleteFileAfterSend(true);
    }

    // ── Acta de notas PDF ─────────────────────────────────────────────────
    public function actaPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $esTecnica = $asignacion->area === 'tecnica';
        $periodos  = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')->get();

        if ($esTecnica) {
            $calificaciones = Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->get()->groupBy(fn($c) => $c->matricula_id . '_' . $c->periodo_id);
        } else {
            $calificaciones = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()->keyBy('matricula_id');
        }

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.acta_pdf',
            compact('docente', 'asignacion', 'matriculas', 'calificaciones',
                    'periodos', 'esTecnica', 'schoolYear', 'si', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug(
            ($asignacion->asignatura?->nombre ?? 'materia') . '-' .
            ($asignacion->grupo?->nombre_corto ?? 'grupo')
        );
        return $pdf->download("acta_{$slug}.pdf");
    }

    // ── AJAX: guardar una celda P (nota base) o R (recuperación) ────────────
    public function guardarCeldaAcad(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'matricula_id' => 'required|integer|exists:matriculas,id',
            'campo'        => ['required', 'string', 'regex:/^(comp[1-4]_[pr][1-4]|nota_cc|nota_ce)$/'],
            'valor'        => 'nullable|numeric|min:0|max:100',
        ]);

        $schoolYear = SchoolYear::actual();

        $cal = CalificacionAcademica::firstOrNew([
            'matricula_id'   => $request->matricula_id,
            'asignacion_id'  => $asignacion->id,
            'school_year_id' => $schoolYear?->id,
        ]);

        $campo = $request->campo;
        $valor = $request->valor !== null && $request->valor !== ''
            ? round((float) $request->valor, 2)
            : null;

        $cal->{$campo}       = $valor;
        $cal->publicado      = true;
        $cal->modificado_por = auth()->id();
        $cal->save();

        $cal->recalcularPromedios();
        $cal->refresh();

        return response()->json([
            'ok'   => true,
            'data' => $cal->toAjaxArray(),
        ]);
    }

    public function guardarCalificaciones(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura.resultadosAprendizaje']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';

        if ($esTecnica) {
            // ── Guardar calificaciones técnicas por RA ─────────────────────
            $request->validate([
                'periodo_id' => 'required|integer|exists:periodos,id',
                'notas'      => 'required|array',
            ]);

            $periodoId = (int) $request->periodo_id;

            $ras = $asignacion->asignatura
                ->resultadosAprendizaje()
                ->where('activo', true)
                ->orderBy('numero')
                ->get();
            $numRA = $ras->count() ?: ($asignacion->asignatura->num_ra ?? 3);

            $pesosRA = [];
            $pesosPersonalizados = $asignacion->pesos_ra ?? [];
            foreach ($ras as $ra) {
                $pesosRA[$ra->numero] = $pesosPersonalizados[$ra->numero]
                    ?? $ra->peso
                    ?? round(100 / $numRA, 4);
            }
            if (empty($pesosRA)) {
                for ($i = 1; $i <= $numRA; $i++) {
                    $pesosRA[$i] = $pesosPersonalizados[$i] ?? round(100 / $numRA, 4);
                }
            }

            // Criterios por RA: notas[mat_id][ra1][tp|ex|cc|oh|pd|ec]
            $criteriosInput    = $request->input('criterios', []);
            // Recuperación por RA: rec[mat_id][ra1][practica|exposicion|practica_eval]
            $recuperacionesInput = $request->input('recuperaciones', []);

            foreach ($request->notas as $matriculaId => $vals) {
                $data      = ['modificado_por' => auth()->id()];
                $suma      = 0.0;
                $hayNota   = false;
                $recJson   = [];
                $critJson  = [];

                for ($i = 1; $i <= 10; $i++) {
                    $key  = "ra{$i}";
                    $pMax = $pesosRA[$i] ?? round(100 / $numRA, 4);

                    // ── Criterios por RA (T.P.30 + EX.15 + C.C.10 + O.H.20 + P.D.15 + E.C.10 = 100) ──
                    $crit = $criteriosInput[$matriculaId][$key] ?? [];
                    $critData = null;
                    if (!empty(array_filter($crit, fn($v) => $v !== '' && $v !== null))) {
                        $tp = isset($crit['tp']) && $crit['tp'] !== '' ? min((float)$crit['tp'], 30) : null;
                        $ex = isset($crit['ex']) && $crit['ex'] !== '' ? min((float)$crit['ex'], 15) : null;
                        $cc = isset($crit['cc']) && $crit['cc'] !== '' ? min((float)$crit['cc'], 10) : null;
                        $oh = isset($crit['oh']) && $crit['oh'] !== '' ? min((float)$crit['oh'], 20) : null;
                        $pd = isset($crit['pd']) && $crit['pd'] !== '' ? min((float)$crit['pd'], 15) : null;
                        $ec = isset($crit['ec']) && $crit['ec'] !== '' ? min((float)$crit['ec'], 10) : null;
                        $cfCrit = ($tp ?? 0) + ($ex ?? 0) + ($cc ?? 0) + ($oh ?? 0) + ($pd ?? 0) + ($ec ?? 0);
                        $critData = compact('tp', 'ex', 'cc', 'oh', 'pd', 'ec') + ['cf' => round($cfCrit, 2)];
                    }
                    $critJson[$i] = $critData;

                    // ── Nota bruta del RA ──
                    // Si hay criterios, la nota bruta se calcula de ellos (sobre 100 → escalada a pMax)
                    // Si no, se usa el input directo (ra1, ra2…)
                    if ($critData !== null) {
                        $raw = round($critData['cf'] / 100 * $pMax, 2);
                    } else {
                        $raw = isset($vals[$key]) && $vals[$key] !== '' ? (float) $vals[$key] : null;
                    }

                    // ── Recuperación estructurada (5TOA): Práctica 25 + Exposición 25 + Práctica Eval 50 ──
                    $rec = $recuperacionesInput[$matriculaId][$key] ?? [];
                    $recData = null;
                    if (!empty(array_filter($rec, fn($v) => $v !== '' && $v !== null))) {
                        $rPractica     = isset($rec['practica'])     && $rec['practica']     !== '' ? min((float)$rec['practica'], 25)     : null;
                        $rExposicion   = isset($rec['exposicion'])   && $rec['exposicion']   !== '' ? min((float)$rec['exposicion'], 25)   : null;
                        $rPracticaEval = isset($rec['practica_eval'])&& $rec['practica_eval']!== '' ? min((float)$rec['practica_eval'], 50): null;
                        // nota_rec: suma de los 3 componentes (sobre 100)
                        $notaRec = ($rPractica ?? 0) + ($rExposicion ?? 0) + ($rPracticaEval ?? 0);
                        // CF recuperación = 50% nota_acumulada (raw/pMax*100) + 50% nota_rec
                        $notaAcum = ($raw !== null) ? round($raw / $pMax * 100, 2) : 0;
                        $cfRec    = round(0.5 * $notaAcum + 0.5 * $notaRec, 2);
                        $cfRecEscalada = round($cfRec / 100 * $pMax, 2);
                        $recData  = [
                            'practica'     => $rPractica,
                            'exposicion'   => $rExposicion,
                            'practica_eval'=> $rPracticaEval,
                            'nota_rec'     => round($notaRec, 2),
                            'nota_acum'    => $notaAcum,
                            'cf'           => $cfRec,
                            'cf_escalada'  => $cfRecEscalada,
                        ];
                    }
                    $recJson[$i] = $recData;

                    // ── Nota efectiva del RA ──
                    // Si hay recuperación y mejora el resultado, usar la nota escalada de recuperación
                    $efectiva = null;
                    if ($raw !== null) {
                        $efectiva = $raw;
                        if ($recData !== null && $recData['cf_escalada'] > $raw) {
                            $efectiva = min($recData['cf_escalada'], $pMax);
                        }
                    }

                    $data[$key] = $raw;

                    if ($efectiva !== null && $i <= $numRA) {
                        $suma    += $efectiva;
                        $hayNota  = true;
                    }
                }

                $data['criterios_ra']    = $critJson;
                $data['recuperaciones_ra'] = $recJson;
                $data['nota_final'] = $hayNota ? round($suma, 2) : null;
                $data['publicado']  = true;

                Calificacion::updateOrCreate(
                    [
                        'matricula_id'  => $matriculaId,
                        'asignacion_id' => $asignacion->id,
                        'periodo_id'    => $periodoId,
                    ],
                    $data
                );
            }

            Cache::forget("portal_docente_{$docente->id}_asignaciones_{$schoolYear?->id}");

            return redirect()
                ->to(route('portal.docente.calificaciones', $asignacion) . '?periodo_id=' . $periodoId)
                ->with('success', 'Calificaciones del período guardadas correctamente.');
        }

        // ── Guardar calificaciones académicas P1–P4 (recuperación por período) ──
        $request->validate([
            'notas'      => 'required|array',
            'notas.*.p1' => 'nullable|numeric|min:0|max:100',
            'notas.*.p2' => 'nullable|numeric|min:0|max:100',
            'notas.*.p3' => 'nullable|numeric|min:0|max:100',
            'notas.*.p4' => 'nullable|numeric|min:0|max:100',
        ]);

        // rec_per[mat_id][p1][0], rec_per[mat_id][p1][1], …
        $recPerInput = $request->input('rec_per', []);

        foreach ($request->notas as $matriculaId => $vals) {
            $periodos = ['p1', 'p2', 'p3', 'p4'];
            $recAcad  = [];   // {p1: [r1,r2,...], p2: [...], ...}
            $finales  = [];   // nota final de cada período

            foreach ($periodos as $pk) {
                $grade = isset($vals[$pk]) && $vals[$pk] !== '' ? (float) $vals[$pk] : null;

                if ($grade === null) {
                    $finales[$pk] = null;
                    continue;
                }

                // Recuperaciones para este período
                $rawRecs = $recPerInput[$matriculaId][$pk] ?? [];
                $savedRecs = [];
                $acum = $grade;

                if ($grade < 70) {
                    foreach ($rawRecs as $rv) {
                        $rv = ($rv !== '' && $rv !== null) ? (float)$rv : null;
                        if ($rv === null) break;
                        $maxRec = max(0, 100 - $acum);
                        $rv     = min($rv, $maxRec);
                        $savedRecs[] = $rv;
                        $acum = min($acum + $rv, 100);
                        if ($acum >= 70) break;
                    }
                }

                $recAcad[$pk]  = !empty($savedRecs) ? $savedRecs : null;
                $finales[$pk]  = round($acum, 2);
            }

            // Nota final = promedio de los períodos que tienen nota
            $periodosConNota = array_filter($finales, fn($v) => $v !== null);
            $notaFinal = count($periodosConNota)
                ? round(array_sum($periodosConNota) / count($periodosConNota), 2)
                : null;

            $situacion = $notaFinal !== null ? ($notaFinal >= 70 ? 'A' : 'R') : null;

            // Usar las notas finales de cada período (después de recuperaciones aplicadas)
            $fp1 = $finales['p1'] ?? null;
            $fp2 = $finales['p2'] ?? null;
            $fp3 = $finales['p3'] ?? null;
            $fp4 = $finales['p4'] ?? null;

            CalificacionAcademica::updateOrCreate(
                [
                    'matricula_id'   => $matriculaId,
                    'asignacion_id'  => $asignacion->id,
                    'school_year_id' => $schoolYear?->id,
                ],
                [
                    // comp_X_pN = nota final del período N (post-recuperación)
                    'comp1_p1' => $fp1, 'comp2_p1' => $fp1, 'comp3_p1' => $fp1, 'comp4_p1' => $fp1,
                    'comp1_p2' => $fp2, 'comp2_p2' => $fp2, 'comp3_p2' => $fp2, 'comp4_p2' => $fp2,
                    'comp1_p3' => $fp3, 'comp2_p3' => $fp3, 'comp3_p3' => $fp3, 'comp4_p3' => $fp3,
                    'comp1_p4' => $fp4, 'comp2_p4' => $fp4, 'comp3_p4' => $fp4, 'comp4_p4' => $fp4,
                    'prom_comp1' => $fp1, 'prom_comp2' => $fp2,
                    'prom_comp3' => $fp3, 'prom_comp4' => $fp4,
                    'recuperaciones_acad' => array_filter($recAcad) ?: null,
                    'nota_final'          => $notaFinal,
                    'situacion'           => $situacion,
                    'publicado'           => true,
                    'modificado_por'      => auth()->id(),
                ]
            );
        }

        Cache::forget("portal_docente_{$docente->id}_asignaciones_{$schoolYear?->id}");

        return redirect()
            ->route('portal.docente.calificaciones', $asignacion)
            ->with('success', 'Calificaciones guardadas correctamente.');
    }

    // ── Boletines ────────────────────────────────────────────────────────
    public function boletines(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')
            ->get();

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')
            ->get();

        if ($esTecnica) {
            // Notas RA: una fila por matricula × período → agrupar por matricula_id
            $periodoIds     = $periodos->pluck('id');
            $calificaciones = Calificacion::where('asignacion_id', $asignacion->id)
                ->when($periodoIds->isNotEmpty(), fn($q) => $q->whereIn('periodo_id', $periodoIds))
                ->get()
                ->groupBy('matricula_id')          // [matricula_id => [Calificacion, ...]]
                ->map(fn($rows) => $rows->keyBy('periodo_id')); // [matricula_id => [periodo_id => Calificacion]]
        } else {
            // Notas académicas: una fila por matricula
            $calificaciones = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->keyBy('matricula_id');
        }

        // Estadísticas rápidas
        $statsMateria = [];
        if ($esTecnica) {
            $notas = Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->whereNotNull('nota_final')
                ->pluck('nota_final');
        } else {
            $notas = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->whereNotNull('nota_final')
                ->pluck('nota_final');
        }

        if ($notas->isNotEmpty()) {
            $statsMateria = [
                'promedio'   => round($notas->avg(), 1),
                'aprobados'  => $notas->filter(fn($n) => $n >= 70)->count(),
                'reprobados' => $notas->filter(fn($n) => $n < 70)->count(),
                'max'        => round($notas->max(), 1),
                'min'        => round($notas->min(), 1),
                'total'      => $notas->count(),
            ];
            $statsMateria['tasa'] = $statsMateria['total'] > 0
                ? round($statsMateria['aprobados'] / $statsMateria['total'] * 100, 1)
                : 0;
        }

        return view('portal.docente.boletines', compact(
            'docente', 'asignacion', 'matriculas', 'calificaciones',
            'schoolYear', 'periodos', 'esTecnica', 'statsMateria'
        ));
    }

    public function verBoletin(Asignacion $asignacion, Matricula $matricula)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($matricula->grupo_id !== $asignacion->grupo_id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion']);

        $schoolYear = SchoolYear::actual();

        // All of this docente's asignaciones for this group
        $misAsignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->sortBy(fn($a) => $a->asignatura?->nombre);

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')
            ->get();

        // Notas académicas (una fila por asignacion)
        $califAcadMap = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->keyBy('asignacion_id');

        // Notas técnicas RA (una fila por asignacion × período)
        // La tabla calificaciones no tiene school_year_id; filtramos por periodo_ids del año
        $periodoIds = $periodos->pluck('id');
        $califTecMap = Calificacion::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
            ->when($periodoIds->isNotEmpty(), fn($q) => $q->whereIn('periodo_id', $periodoIds))
            ->get()
            ->groupBy('asignacion_id');

        $tablaNotas = [];
        foreach ($misAsignaciones as $asi) {
            $esTecnicaAsi = $asi->area === 'tecnica';
            $periodosData = [];
            $notasValidas = [];

            if ($esTecnicaAsi) {
                // Una calificacion por período → leer nota_final de cada período
                $calsPorPeriodo = $califTecMap->get($asi->id, collect())->keyBy('periodo_id');

                foreach ($periodos as $p) {
                    $calP = $calsPorPeriodo->get($p->id);
                    $notaPeriodo = $calP?->nota_final;
                    $periodosData[$p->id] = $notaPeriodo;
                    if ($notaPeriodo !== null) $notasValidas[] = $notaPeriodo;
                }

                $promedio   = count($notasValidas)
                    ? round(array_sum($notasValidas) / count($notasValidas), 2)
                    : null;
                $situacion  = $promedio !== null ? ($promedio >= 70 ? 'A' : 'R') : null;
            } else {
                $cal = $califAcadMap->get($asi->id);

                foreach ($periodos as $p) {
                    $n    = $p->numero;
                    $vals = [];
                    // Promedio de las 4 competencias para este período, usando CF (P + RP)
                    for ($ci = 1; $ci <= 4; $ci++) {
                        $cv = $cal?->{"avg_comp{$ci}_p{$n}"};
                        if ($cv === null) {
                            $pb = $cal?->{"comp{$ci}_p{$n}"};
                            if ($pb !== null) {
                                $rv  = $cal?->{"comp{$ci}_r{$n}"};
                                $pb  = (float) $pb;
                                $cv  = ($rv !== null && $pb < 70)
                                    ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2)
                                    : round($pb, 2);
                            }
                        }
                        if ($cv !== null) $vals[] = (float) $cv;
                    }
                    $periodosData[$p->id] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
                }

                // Promedio final: usar la mejor nota disponible (extraordinaria > completiva > formativa)
                $promedio  = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                $situacion = $cal?->situacion;
            }

            $tablaNotas[] = [
                'asignatura' => $asi->asignatura?->nombre ?? '—',
                'esTecnica'  => $esTecnicaAsi,
                'periodos'   => $periodosData,
                'promedio'   => $promedio,
                'situacion'  => $situacion,
            ];
        }

        return view('portal.docente.boletin_ver', compact(
            'docente', 'asignacion', 'matricula', 'tablaNotas', 'periodos', 'schoolYear'
        ));
    }

    // ── PDF del boletín ───────────────────────────────────────────────────
    public function pdfBoletin(Asignacion $asignacion, Matricula $matricula)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($matricula->grupo_id !== $asignacion->grupo_id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion']);

        $schoolYear = SchoolYear::actual();

        $misAsignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->sortBy(fn($a) => $a->asignatura?->nombre);

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')
            ->get();

        $califAcadMap = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get()
            ->keyBy('asignacion_id');

        $periodoIds  = $periodos->pluck('id');
        $califTecMap = Calificacion::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $misAsignaciones->pluck('id'))
            ->when($periodoIds->isNotEmpty(), fn($q) => $q->whereIn('periodo_id', $periodoIds))
            ->get()
            ->groupBy('asignacion_id');

        $tablaNotas = [];
        foreach ($misAsignaciones as $asi) {
            $esTecnicaAsi = $asi->area === 'tecnica';
            $periodosData = [];
            $notasValidas = [];

            if ($esTecnicaAsi) {
                $calsPorPeriodo = $califTecMap->get($asi->id, collect())->keyBy('periodo_id');
                foreach ($periodos as $p) {
                    $notaPeriodo = $calsPorPeriodo->get($p->id)?->nota_final;
                    $periodosData[$p->id] = $notaPeriodo;
                    if ($notaPeriodo !== null) $notasValidas[] = $notaPeriodo;
                }
                $promedio  = count($notasValidas) ? round(array_sum($notasValidas) / count($notasValidas), 2) : null;
                $situacion = $promedio !== null ? ($promedio >= 70 ? 'A' : 'R') : null;
            } else {
                $cal = $califAcadMap->get($asi->id);
                foreach ($periodos as $p) {
                    $n = $p->numero;
                    $vals = [];
                    for ($ci = 1; $ci <= 4; $ci++) {
                        $cv = $cal?->{"avg_comp{$ci}_p{$n}"};
                        if ($cv === null) {
                            $pb = $cal?->{"comp{$ci}_p{$n}"};
                            if ($pb !== null) {
                                $rv = $cal?->{"comp{$ci}_r{$n}"};
                                $pb = (float) $pb;
                                $cv = ($rv !== null && $pb < 70)
                                    ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2)
                                    : round($pb, 2);
                            }
                        }
                        if ($cv !== null) $vals[] = (float) $cv;
                    }
                    $periodosData[$p->id] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
                }
                $promedio  = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                $situacion = $cal?->situacion;
            }

            $tablaNotas[] = [
                'asignatura' => $asi->asignatura?->nombre ?? '—',
                'esTecnica'  => $esTecnicaAsi,
                'periodos'   => $periodosData,
                'promedio'   => $promedio,
                'situacion'  => $situacion,
            ];
        }

        $boletinConfig = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $data = compact('matricula', 'periodos', 'tablaNotas', 'schoolYear', 'boletinConfig');
        $data['asistencias'] = collect();   // El docente solo ve sus materias

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)
            ->setPaper('letter', 'portrait');

        $apellidos = \Illuminate\Support\Str::slug($matricula->estudiante->apellidos ?? 'estudiante');
        $filename  = "boletin_{$apellidos}.pdf";

        return $pdf->download($filename);
    }

    // ── Observaciones ────────────────────────────────────────────────────
    public function observaciones(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $periodo = Periodo::where('school_year_id', $schoolYear?->id)->where('activo', true)->first();

        $observaciones = Observacion::with('estudiante')
            ->where('docente_id', $docente->id)
            ->where('asignacion_id', $asignacion->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('estudiante_id');

        return view('portal.docente.observaciones', compact(
            'docente', 'asignacion', 'matriculas', 'periodo', 'observaciones'
        ));
    }

    public function observacionesPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();

        $observaciones = Observacion::with(['estudiante', 'periodo'])
            ->where('docente_id', $docente->id)
            ->where('asignacion_id', $asignacion->id)
            ->orderBy('created_at')
            ->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.observaciones_pdf', compact(
            'docente', 'asignacion', 'observaciones', 'schoolYear', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('Observaciones_' . \Str::slug($asignacion->asignatura?->nombre ?? 'asig') . '.pdf');
    }

    public function observacionesExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        $observaciones = Observacion::with(['estudiante', 'periodo'])
            ->where('docente_id', $docente->id)
            ->where('asignacion_id', $asignacion->id)
            ->orderBy('created_at')
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Observaciones');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Observaciones — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Estudiante', 'Tipo', 'Observación', 'Fecha'];
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
            $ws->setCellValue("B{$row}", $obs->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", ucfirst($obs->tipo ?? '—'));
            $ws->setCellValue("D{$row}", $obs->texto ?? '—');
            $ws->setCellValue("E{$row}", $obs->created_at?->format('d/m/Y') ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        $ws->getColumnDimension('D')->setWidth(60);
        foreach (['A', 'B', 'C', 'E'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'obs');
        $filename = "observaciones_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function guardarObservacion(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo'          => 'required|in:academica,conductual,positiva,general',
            'texto'         => 'required|string|min:10|max:1000',
            'privada'       => 'boolean',
        ]);

        $obs = Observacion::create([
            'docente_id'    => $docente->id,
            'estudiante_id' => $request->estudiante_id,
            'asignacion_id' => $asignacion->id,
            'tipo'          => $request->tipo,
            'texto'         => $request->texto,
            'privada'       => $request->boolean('privada'),
        ]);

        // Notificar al estudiante y representante (si la observación no es privada)
        if (! $obs->privada) {
            $this->notificarObservacion($obs, $asignacion);
        }

        return response()->json(['ok' => true, 'id' => $obs->id]);
    }

    // ── Marcar notificaciones leídas ─────────────────────────────────────
    public function marcarTodasLeidas()
    {
        Notificacion::where('user_id', auth()->id())->noLeidas()
            ->update(['leida' => true, 'leida_en' => now()]);

        return response()->json(['ok' => true]);
    }

    public function notificaciones()
    {
        $notificaciones = Notificacion::where('user_id', auth()->id())
            ->latest()->paginate(30);
        Notificacion::where('user_id', auth()->id())
            ->noLeidas()->update(['leida' => true, 'leida_en' => now()]);
        return view('portal.notificaciones', compact('notificaciones'));
    }

    // ── Recursos por materia ─────────────────────────────────────────────
    public function recursos(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $schoolYear = SchoolYear::actual();
        $recursos   = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->orderBy('orden')->orderByDesc('created_at')
            ->get();

        return view('portal.docente.recursos', compact('asignacion', 'schoolYear', 'recursos'));
    }

    public function recursosPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $recursos = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->orderBy('orden')->orderByDesc('created_at')->get();

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.recursos_pdf', compact(
            'docente', 'asignacion', 'recursos', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('Recursos_' . \Str::slug($asignacion->asignatura?->nombre ?? 'materia') . '.pdf');
    }

    public function recursosExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);

        $recursos = RecursoMateria::where('asignacion_id', $asignacion->id)
            ->orderBy('orden')->orderByDesc('created_at')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Recursos');

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? '') . ' — ' . ($asignacion->grupo?->nombre_completo ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Tipo', 'Descripción', 'URL/Archivo', 'Publicado'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($recursos as $i => $rec) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $rec->titulo ?? '—');
            $ws->setCellValue("C{$row}", ucfirst($rec->tipo ?? '—'));
            $ws->setCellValue("D{$row}", $rec->descripcion ?? '—');
            $ws->setCellValue("E{$row}", $rec->url ?? $rec->archivo_nombre ?? '—');
            $ws->setCellValue("F{$row}", $rec->publicado ? 'Sí' : 'No');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        $ws->getColumnDimension('D')->setWidth(40);
        $ws->getColumnDimension('E')->setWidth(50);
        foreach (['A', 'B', 'C', 'F'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'recursos');
        $filename = "recursos_{$slug}.xlsx";

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function guardarRecurso(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'titulo'      => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:500',
            'tipo'        => 'required|in:enlace,video,documento,imagen,otro',
            'url'         => 'nullable|url|max:1000',
            'archivo'     => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip',
        ]);

        $archivoPath   = null;
        $archivoNombre = null;

        if ($request->hasFile('archivo')) {
            $file          = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoPath   = $file->store("recursos/{$asignacion->id}", 'public');
        }

        $schoolYear = SchoolYear::actual();

        RecursoMateria::create([
            'asignacion_id'  => $asignacion->id,
            'school_year_id' => $schoolYear?->id,
            'created_by'     => auth()->id(),
            'titulo'         => $request->titulo,
            'descripcion'    => $request->descripcion,
            'tipo'           => $request->tipo,
            'url'            => $request->url,
            'archivo_path'   => $archivoPath,
            'archivo_nombre' => $archivoNombre,
            'publicado'      => $request->boolean('publicado', true),
        ]);

        return back()->with('success', 'Recurso agregado correctamente.');
    }

    public function eliminarRecurso(Asignacion $asignacion, RecursoMateria $recurso)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($recurso->asignacion_id !== $asignacion->id) abort(403);

        if ($recurso->archivo_path) {
            Storage::disk('public')->delete($recurso->archivo_path);
        }

        $recurso->delete();

        return back()->with('success', 'Recurso eliminado.');
    }

    public function toggleRecurso(Asignacion $asignacion, RecursoMateria $recurso)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);
        if ($recurso->asignacion_id !== $asignacion->id) abort(403);

        $eraInactivo = !$recurso->publicado;
        $recurso->update(['publicado' => !$recurso->publicado]);

        // Notificar estudiantes del grupo cuando se publica un recurso nuevo
        if ($eraInactivo && $recurso->publicado) {
            $this->notificarRecursoPublicado($asignacion, $recurso);
        }

        return response()->json(['ok' => true, 'publicado' => $recurso->publicado]);
    }

    private function notificarRecursoPublicado(Asignacion $asignacion, RecursoMateria $recurso): void
    {
        try {
            $schoolYear = SchoolYear::actual();
            if (!$schoolYear) return;

            $userIds = Matricula::with('estudiante')
                ->where('grupo_id', $asignacion->grupo_id)
                ->where('school_year_id', $schoolYear->id)
                ->where('estado', 'activa')
                ->get()
                ->filter(fn($m) => $m->estudiante?->user_id)
                ->pluck('estudiante.user_id')
                ->unique()->values()->toArray();

            if (!empty($userIds)) {
                Notificacion::enviarA(
                    $userIds,
                    'recursos',
                    'Nuevo recurso disponible',
                    "Tu docente compartió: \"{$recurso->titulo}\" en {$asignacion->asignatura?->nombre}."
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error notif. recurso: ' . $e->getMessage());
        }
    }

    // ── Helpers privados ─────────────────────────────────────────────────

    private function calcularRendimiento($asignaciones, $schoolYear, int $syId): array
    {
        $resultado = [];

        if (! $schoolYear) return $resultado;

        // Periodos del año escolar
        $periodos   = Periodo::where('school_year_id', $syId)->orderBy('numero')->get();
        $periodoIds = $periodos->pluck('id');

        foreach ($asignaciones as $asig) {
            $esTecnica = ($asig->area ?? '') === 'tecnica';

            // Total de matriculados en este grupo/year
            $totalMat = Matricula::where('grupo_id', $asig->grupo_id)
                ->where('school_year_id', $syId)
                ->where('estado', 'activa')
                ->count();

            if ($totalMat === 0) {
                $resultado[$asig->id] = [
                    'aprobados'   => 0,
                    'reprobados'  => 0,
                    'sin_nota'    => 0,
                    'total'       => 0,
                    'promedio'    => null,
                    'pct_asist'   => null,
                    'labels'      => ['Aprobados', 'Reprobados', 'Sin nota'],
                    'data'        => [0, 0, 0],
                    'colors'      => ['#22c55e', '#ef4444', '#94a3b8'],
                ];
                continue;
            }

            $aprobados  = 0;
            $reprobados = 0;
            $sinNota    = 0;
            $sumaNotas  = 0;
            $cntNotas   = 0;
            $umbral     = 65;

            if ($esTecnica) {
                // Técnica: agrupar Calificacion por matricula y sacar promedio de nota_final por período
                $cals = Calificacion::where('asignacion_id', $asig->id)
                    ->when($periodoIds->isNotEmpty(), fn($q) => $q->whereIn('periodo_id', $periodoIds))
                    ->get()
                    ->groupBy('matricula_id');

                // Matriculas del grupo
                $matIds = Matricula::where('grupo_id', $asig->grupo_id)
                    ->where('school_year_id', $syId)->where('estado', 'activa')
                    ->pluck('id');

                foreach ($matIds as $mid) {
                    $rows = $cals->get($mid, collect());
                    $notas = $rows->pluck('nota_final')->filter(fn($v) => $v !== null);
                    if ($notas->isEmpty()) {
                        $sinNota++;
                    } else {
                        $nf = round($notas->avg());
                        $sumaNotas += $nf;
                        $cntNotas++;
                        $nf >= $umbral ? $aprobados++ : $reprobados++;
                    }
                }
            } else {
                // Académica: CalificacionAcademica, una fila por alumno
                $cals = CalificacionAcademica::where('asignacion_id', $asig->id)
                    ->where('school_year_id', $syId)
                    ->get()
                    ->keyBy('matricula_id');

                $matIds = Matricula::where('grupo_id', $asig->grupo_id)
                    ->where('school_year_id', $syId)->where('estado', 'activa')
                    ->pluck('id');

                foreach ($matIds as $mid) {
                    $cal = $cals->get($mid);
                    if (! $cal || $cal->nota_final === null) {
                        $sinNota++;
                    } else {
                        $nf = (int) round($cal->nota_final);
                        $sumaNotas += $nf;
                        $cntNotas++;
                        $nf >= $umbral ? $aprobados++ : $reprobados++;
                    }
                }
            }

            // % asistencia promedio del grupo en esta asignación
            // (asistencias no tiene school_year_id — se filtra solo por asignacion_id)
            $asistencias = Asistencia::where('asignacion_id', $asig->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN estado = "presente" THEN 1 ELSE 0 END) as presentes')
                ->first();

            $pctAsist = null;
            if ($asistencias && $asistencias->total > 0) {
                $pctAsist = round(($asistencias->presentes / $asistencias->total) * 100);
            }

            $resultado[$asig->id] = [
                'aprobados'  => $aprobados,
                'reprobados' => $reprobados,
                'sin_nota'   => $sinNota,
                'total'      => $totalMat,
                'promedio'   => $cntNotas > 0 ? round($sumaNotas / $cntNotas, 1) : null,
                'pct_asist'  => $pctAsist,
                'labels'     => ['Aprobados', 'Reprobados', 'Sin nota'],
                'data'       => [$aprobados, $reprobados, $sinNota],
                'colors'     => ['#22c55e', '#ef4444', '#94a3b8'],
            ];
        }

        return $resultado;
    }

    // ── Mis planificaciones ──────────────────────────────────────────────
    public function misPlanificaciones()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $asignacionIds = $asignaciones->pluck('id');

        $planificaciones = \App\Models\Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'raItems', 'actividades'])
            ->whereIn('asignacion_id', $asignacionIds)
            ->latest()
            ->get()
            ->groupBy('asignacion_id');

        return view('portal.docente.mis_planificaciones', compact(
            'docente', 'schoolYear', 'asignaciones', 'planificaciones'
        ));
    }

    // ── PDF de mis planificaciones ───────────────────────────────────────
    public function misPlanificacionesPdf()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $planificaciones = \App\Models\Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'raItems', 'actividades'])
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('asignacion_id');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.mis_planificaciones_pdf', compact(
            'docente', 'schoolYear', 'asignaciones', 'planificaciones', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('mis_planificaciones_' . now()->format('Ymd') . '.pdf');
    }

    // ── Excel de mis planificaciones ─────────────────────────────────────
    public function misPlanificacionesExcel()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $planificaciones = \App\Models\Planificacion::with(['asignacion.asignatura', 'asignacion.grupo', 'raItems', 'actividades'])
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->latest()
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planificaciones');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Mis Planificaciones — ' . $docente->nombre_completo . ' · ' . ($schoolYear?->nombre ?? ''));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Asignatura', 'Grupo', 'Módulo / Título', 'R.A.', 'Actividades'];
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
            $sheet->setCellValue('C' . $row, $plan->asignacion?->grupo?->nombre_completo);
            $sheet->setCellValue('D' . $row, $plan->titulo ?? $plan->modulo);
            $sheet->setCellValue('E' . $row, $plan->raItems?->count() ?? 0);
            $sheet->setCellValue('F' . $row, $plan->actividades?->count() ?? 0);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (['A'=>5,'B'=>25,'C'=>18,'D'=>38,'E'=>8,'F'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'mis_planificaciones_' . now()->format('Ymd') . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Horario personal PDF ──────────────────────────────────────────────
    // ── Página de horario (vista web) ──────────────────────────────────
    public function horario()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        [$gridHorario, $franjasHorario, $horarioActivo, $diasConfig] = $this->cargarHorario($docente, $schoolYear);

        $asignaciones = $schoolYear
            ? \App\Models\Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
                ->where('docente_id', $docente->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->get()
            : collect();

        return view('portal.docente.horario', compact(
            'docente', 'schoolYear',
            'gridHorario', 'franjasHorario', 'horarioActivo', 'diasConfig',
            'asignaciones'
        ));
    }

    public function horarioPdf()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        [$grid, $franjas, $horario, $dias] = $this->cargarHorario($docente, $schoolYear);

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.horario_pdf',
            compact('docente', 'schoolYear', 'grid', 'franjas', 'dias', 'si', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($docente->nombre_completo ?? 'docente');
        return $pdf->download("horario_{$slug}.pdf");
    }

    // ── Horario personal Excel ────────────────────────────────────────────
    public function horarioExcel()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        [$grid, $franjas, $horario, $dias] = $this->cargarHorario($docente, $schoolYear);

        if (! $horario || empty($grid)) abort(404, 'Horario no disponible.');

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

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
        $ws->setCellValue('A2', 'Horario — ' . $docente->nombre_completo . ' — ' . ($schoolYear?->nombre ?? ''));
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

        foreach ($franjas as $j => $franja) {
            $row = $j + 5;
            $ws->setCellValue("A{$row}", ($franja->hora_inicio ?? '') . '-' . ($franja->hora_fin ?? ''));
            foreach ($dias as $k => $dia) {
                $col    = chr(66 + $k);
                $bloque = $grid[$dia][$franja->id] ?? null;
                $texto  = $bloque ? (($bloque->asignatura?->nombre ?? '—') . "\n" . ($bloque->grupo?->nombre_completo ?? '')) : '—';
                $ws->setCellValue("{$col}{$row}", $texto);
                $ws->getStyle("{$col}{$row}")->getAlignment()->setWrapText(true);
                if ($j % 2 === 1) {
                    $ws->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
                }
            }
            if ($j % 2 === 1) {
                $ws->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
            $ws->getRowDimension($row)->setRowHeight(30);
        }

        foreach (range('A', $lastCol) as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'hor_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($docente->nombre_completo ?? 'docente');
        return response()->download($tmp, "horario_{$slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Mis Estadísticas Personales ──────────────────────────────────────
    public function misEstadisticas()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        // Asignaciones activas del docente
        $asignaciones = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
            ->get();

        // Total de estudiantes únicos en sus grupos
        $grupoIds = $asignaciones->pluck('grupo_id')->unique()->values();
        $totalEstudiantes = $grupoIds->isNotEmpty()
            ? Matricula::whereIn('grupo_id', $grupoIds)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
                ->count()
            : 0;

        // Promedio de calificaciones y % asistencia por asignación
        $estadisticasPorAsignacion = [];
        foreach ($asignaciones as $asig) {
            $esTecnica = ($asig->area ?? '') === 'tecnica';

            // Promedio de nota_final publicadas
            if ($esTecnica) {
                $notas = Calificacion::where('asignacion_id', $asig->id)
                    ->whereNotNull('nota_final')
                    ->pluck('nota_final');
            } else {
                $notas = CalificacionAcademica::where('asignacion_id', $asig->id)
                    ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
                    ->whereNotNull('nota_final')
                    ->pluck('nota_final');
            }
            $promedio = $notas->count() ? round($notas->avg(), 1) : null;

            // % de asistencia del grupo en esta asignación
            $totalAsist = Asistencia::where('asignacion_id', $asig->id)->count();
            $presentes  = Asistencia::where('asignacion_id', $asig->id)
                ->whereIn('estado', ['presente', 'tardanza'])->count();
            $pctAsist = $totalAsist > 0 ? round($presentes / $totalAsist * 100, 1) : null;

            $estadisticasPorAsignacion[] = [
                'asignatura' => $asig->asignatura?->nombre ?? '—',
                'grupo'      => $asig->grupo?->nombre_completo ?? '—',
                'promedio'   => $promedio,
                'pct_asist'  => $pctAsist,
                'total_asist'=> $totalAsist,
            ];
        }

        // % de asistencia promedio general de sus grupos
        $totalAsistGlobal = Asistencia::whereIn('asignacion_id', $asignaciones->pluck('id'))->count();
        $presentesGlobal  = Asistencia::whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('estado', ['presente', 'tardanza'])->count();
        $pctAsistGlobal = $totalAsistGlobal > 0 ? round($presentesGlobal / $totalAsistGlobal * 100, 1) : null;

        // Planes de clase y planificaciones creadas
        $totalPlanificaciones = \App\Models\Planificacion::whereIn('asignacion_id', $asignaciones->pluck('id'))->count();
        $totalPlanesClase     = \App\Models\PlanClase::whereIn('asignacion_id', $asignaciones->pluck('id'))->count();

        // Datos para Chart.js (promedio por asignación)
        $chartLabels = collect($estadisticasPorAsignacion)
            ->map(fn($e) => \Illuminate\Support\Str::limit($e['asignatura'], 20))
            ->values()->toJson();
        $chartData   = collect($estadisticasPorAsignacion)
            ->map(fn($e) => $e['promedio'] ?? 0)
            ->values()->toJson();

        return view('portal.docente.mis_estadisticas', compact(
            'docente', 'schoolYear', 'asignaciones',
            'totalEstudiantes', 'estadisticasPorAsignacion',
            'pctAsistGlobal', 'totalPlanificaciones', 'totalPlanesClase',
            'chartLabels', 'chartData'
        ));
    }

    // ── Mis Estudiantes (vista global docente) ──────────────────────────
    public function misEstudiantes(Request $request)
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();
        $syId       = $schoolYear?->id ?? 0;

        // Todas las asignaciones activas del docente con relaciones necesarias
        $asignaciones = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
            ->get();

        $asignacionIds = $asignaciones->pluck('id');
        $grupoIds      = $asignaciones->pluck('grupo_id')->unique()->values();

        // Filtro opcional por asignacion_id o grupo_id
        $filtroAsignacion = $request->integer('asignacion_id');
        $filtroGrupo      = $request->integer('grupo_id');
        $filtroBusqueda   = trim($request->input('q', ''));

        // Matriculas activas de todos sus grupos
        $matriculas = Matricula::with('estudiante')
            ->whereIn('grupo_id', $grupoIds)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
            ->when($filtroGrupo, fn($q) => $q->where('grupo_id', $filtroGrupo))
            ->get();

        // Si se filtra por asignación concreta, conservar solo los del grupo de esa asignación
        if ($filtroAsignacion) {
            $asig = $asignaciones->firstWhere('id', $filtroAsignacion);
            if ($asig) {
                $matriculas = $matriculas->where('grupo_id', $asig->grupo_id)->values();
            }
        }

        // Filtro de búsqueda por nombre
        if ($filtroBusqueda !== '') {
            $lower = mb_strtolower($filtroBusqueda);
            $matriculas = $matriculas->filter(function ($m) use ($lower) {
                $nombre = mb_strtolower($m->estudiante?->nombres . ' ' . $m->estudiante?->apellidos);
                return str_contains($nombre, $lower);
            })->values();
        }

        // Pre-cargar calificaciones y asistencias en bulk para evitar N+1
        $matriculaIds = $matriculas->pluck('id');

        // Calificaciones académicas (segundo ciclo)
        $califAcad = CalificacionAcademica::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignacionIds)
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $syId))
            ->whereNotNull('nota_final')
            ->get()
            ->groupBy('matricula_id');

        // Calificaciones técnicas
        $califTec = Calificacion::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignacionIds)
            ->whereNotNull('nota_final')
            ->get()
            ->groupBy('matricula_id');

        // Asistencias
        $asistencias = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignacionIds)
            ->get()
            ->groupBy('matricula_id');

        // Alertas activas por estudiante (referencia_tipo = Matricula o Estudiante)
        $alertasEstudiante = \App\Models\AlertaSistema::whereIn('referencia_id', $matriculaIds)
            ->where('referencia_tipo', 'matricula')
            ->where('leida', false)
            ->vigentes()
            ->get()
            ->groupBy('referencia_id');

        // Enriquecer cada matrícula
        $matriculas = $matriculas->map(function ($m) use (
            $califAcad, $califTec, $asistencias, $alertasEstudiante, $asignaciones
        ) {
            // Grupo del estudiante
            $asig = $asignaciones->firstWhere('grupo_id', $m->grupo_id);
            $m->_grupo = $asig?->grupo;

            // Promedio de notas — combinar académicas y técnicas
            $notasAcad = $califAcad->get($m->id, collect())->pluck('nota_final');
            $notasTec  = $califTec->get($m->id, collect())->pluck('nota_final');
            $todasNotas = $notasAcad->concat($notasTec)->filter()->values();
            $m->_promedio = $todasNotas->count() ? round($todasNotas->avg(), 1) : null;

            // % asistencia global sobre todas las asignaciones del docente
            $asistEst   = $asistencias->get($m->id, collect());
            $total      = $asistEst->count();
            $presentes  = $asistEst->whereIn('estado', ['presente', 'tardanza'])->count();
            $m->_asist  = $total > 0 ? round($presentes / $total * 100, 1) : null;

            // Alertas activas
            $m->_alertas = $alertasEstudiante->get($m->id, collect());

            // Semáforo: rojo < 65 o asist < 70 | amarillo 65–74 o asist 70–79 | verde >= 75
            $nota  = $m->_promedio;
            $asist = $m->_asist;

            if (($nota !== null && $nota < 65) || ($asist !== null && $asist < 70)) {
                $m->_semaforo = 'rojo';
            } elseif (($nota !== null && $nota < 75) || ($asist !== null && $asist < 80)) {
                $m->_semaforo = 'amarillo';
            } else {
                $m->_semaforo = 'verde';
            }

            return $m;
        });

        // Ordenar: rojo primero, luego amarillo, verde
        $orden = ['rojo' => 0, 'amarillo' => 1, 'verde' => 2];
        $matriculas = $matriculas->sortBy(fn($m) => $orden[$m->_semaforo] . ($m->estudiante?->apellidos ?? ''))->values();

        return view('portal.docente.mis_estudiantes', compact(
            'docente', 'schoolYear', 'asignaciones', 'matriculas',
            'filtroAsignacion', 'filtroGrupo', 'filtroBusqueda'
        ));
    }

    private function cargarHorario(Docente $docente, $schoolYear): array
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

                $dias = \App\Models\ConfigInstitucional::get('horario_dias', $dias);
            }
        }

        return [$grid, $franjas, $horario, $dias];
    }

    private function notificarAusencia(int $matriculaId, Asignacion $asignacion, string $fecha): void
    {
        try {
            $matricula = Matricula::with(['estudiante.representantes.user'])->find($matriculaId);
            if (! $matricula) return;

            $nombreEstudiante = $matricula->estudiante->nombre_completo;
            $nombreMateria    = $asignacion->asignatura?->nombre ?? 'una materia';
            $mensaje = "Se registró una ausencia de {$nombreEstudiante} en {$nombreMateria} el {$fecha}.";

            // Notificar a representantes (interno + WhatsApp)
            foreach ($matricula->estudiante->representantes as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar(
                        $rep->user_id,
                        'ausencia',
                        'Ausencia registrada',
                        $mensaje,
                        ['estudiante_id' => $matricula->estudiante_id, 'fecha' => $fecha]
                    );
                }
                // WhatsApp al teléfono del representante
                if (!empty($rep->telefono)) {
                    \App\Services\WhatsAppService::sendAbsence(
                        $rep->telefono,
                        $nombreEstudiante,
                        $nombreMateria,
                        $fecha
                    );
                }
            }

            // Notificar al estudiante si tiene cuenta
            if ($matricula->estudiante->user_id) {
                Notificacion::enviar(
                    $matricula->estudiante->user_id,
                    'ausencia',
                    'Ausencia registrada',
                    "Se registró tu ausencia en {$nombreMateria} el {$fecha}.",
                    ['fecha' => $fecha]
                );
            }
        } catch (\Throwable) {}
    }

    // ── Alerta de asistencia crítica ─────────────────────────────────────
    private function verificarAlertasAsistencia(array $matriculaIds, Asignacion $asignacion): void
    {
        try {
            foreach ($matriculaIds as $matriculaId) {
                $registros = Asistencia::where('matricula_id', $matriculaId)
                    ->where('asignacion_id', $asignacion->id)->get();

                $total = $registros->count();
                if ($total < 5) continue;

                $presentes = $registros->whereIn('estado', ['presente', 'tardanza', 'justificado'])->count();
                $pct       = round($presentes / $total * 100, 1);

                if ($pct < 75 && $pct >= 70) {
                    $matricula = Matricula::with(['estudiante.representantes'])->find($matriculaId);
                    if (! $matricula) continue;

                    $est       = $matricula->estudiante;
                    $asignName = $asignacion->asignatura->nombre ?? 'una asignatura';

                    \App\Models\AlertaSistema::firstOrCreate(
                        ['tipo' => 'asistencia_critica', 'referencia_tipo' => 'matricula', 'referencia_id' => $matriculaId],
                        [
                            'titulo'  => 'Asistencia Crítica: ' . ($est->nombre_completo ?? ''),
                            'mensaje' => "{$est->nombre_completo} tiene {$pct}% de asistencia en {$asignName}. Mínimo: 75%.",
                            'nivel'   => 'warning',
                            'leida'   => false,
                        ]
                    );

                    foreach ($est->representantes as $rep) {
                        if ($rep->user_id) {
                            Notificacion::enviarA(
                                [$rep->user_id],
                                'asistencia',
                                'Alerta de Asistencia',
                                "{$est->nombre_completo} tiene {$pct}% de asistencia en {$asignName}. Requerido mínimo: 75%.",
                                ['matricula_id' => $matriculaId]
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error en alerta asistencia: ' . $e->getMessage());
        }
    }

    // ── Plantilla CSV: Calificaciones ────────────────────────────────────
    // ── Exportar calificaciones reales a Excel ────────────────────────────
    public function exportarCalificacionesExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Calificaciones');

        // Título
        $ws->setCellValue('A1', 'Calificaciones — ' . $asignacion->asignatura?->nombre);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->setCellValue('A2', $asignacion->grupo?->grado?->nombre . ' ' . $asignacion->grupo?->seccion?->nombre . ' · Docente: ' . $docente->nombre_completo);

        // Headers
        $headers = ['#', 'Matrícula', 'Apellidos', 'Nombre'];
        if ($esTecnica) {
            foreach ($periodos as $p) { $headers[] = 'P' . $p->numero . ' Nota Final'; }
        } else {
            foreach ($periodos as $p) {
                $n = $p->numero;
                foreach (range(1, 4) as $ci) { $headers[] = "P{$n} C{$ci}"; }
                $headers[] = "P{$n} Prom";
            }
        }
        $headers[] = 'Promedio Final';
        $headers[] = 'Situación';

        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$col}4", $h);
            $ws->getStyle("{$col}4")->getFont()->setBold(true);
            $ws->getStyle("{$col}4")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle("{$col}4")->getFont()->getColor()->setRGB('ffffff');
        }

        if ($esTecnica) {
            $calMap = Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->get()->groupBy(fn($c) => $c->matricula_id . '_' . $c->periodo_id);
        } else {
            $calMap = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()->keyBy('matricula_id');
        }

        foreach ($matriculas as $i => $mat) {
            $row = $i + 5;
            $est = $mat->estudiante;
            $col = 1;
            $ws->setCellValueByColumnAndRow($col++, $row, $i + 1);
            $ws->setCellValueByColumnAndRow($col++, $row, $est?->matricula ?? '');
            $ws->setCellValueByColumnAndRow($col++, $row, $est?->apellidos ?? $est?->apellido ?? '');
            $ws->setCellValueByColumnAndRow($col++, $row, $est?->nombres   ?? $est?->nombre   ?? '');

            $notasParaPromedio = [];
            if ($esTecnica) {
                foreach ($periodos as $p) {
                    $key  = $mat->id . '_' . $p->id;
                    $nota = $calMap[$key]?->first()?->nota_final ?? null;
                    $ws->setCellValueByColumnAndRow($col++, $row, $nota ?? '');
                    if ($nota !== null) $notasParaPromedio[] = $nota;
                }
            } else {
                $cal = $calMap[$mat->id] ?? null;
                foreach ($periodos as $p) {
                    $n = $p->numero; $pVals = [];
                    for ($ci = 1; $ci <= 4; $ci++) {
                        $v = $cal?->{"comp{$ci}_p{$n}"};
                        $ws->setCellValueByColumnAndRow($col++, $row, $v ?? '');
                        if ($v !== null) $pVals[] = (float)$v;
                    }
                    $prom = $pVals ? round(array_sum($pVals) / count($pVals), 2) : null;
                    $ws->setCellValueByColumnAndRow($col++, $row, $prom ?? '');
                    if ($prom !== null) $notasParaPromedio[] = $prom;
                }
            }

            $promFinal = count($notasParaPromedio) ? round(array_sum($notasParaPromedio) / count($notasParaPromedio), 2) : null;
            $sit = $promFinal !== null ? ($promFinal >= 70 ? 'Aprobado' : 'Reprobado') : '';
            $ws->setCellValueByColumnAndRow($col++, $row, $promFinal ?? '');
            $ws->setCellValueByColumnAndRow($col, $row, $sit);

            if ($sit === 'Aprobado') {
                $sitCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $ws->getStyle("{$sitCol}{$row}")->getFont()->getColor()->setRGB('065f46');
            } elseif ($sit === 'Reprobado') {
                $sitCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $ws->getStyle("{$sitCol}{$row}")->getFont()->getColor()->setRGB('991b1b');
            }
        }

        foreach (range(1, count($headers)) as $ci) {
            $ws->getColumnDimensionByColumn($ci)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug   = Str::slug(($asignacion->asignatura?->nombre ?? 'notas') . '-' . ($asignacion->grupo?->nombre_corto ?? ''));
        $filename = "calificaciones_{$slug}.xlsx";

        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportarCalificacionesPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $periodos = \App\Models\Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')->get();

        if ($esTecnica) {
            $calMap = \App\Models\Calificacion::where('asignacion_id', $asignacion->id)
                ->whereIn('periodo_id', $periodos->pluck('id'))
                ->get()->groupBy(fn($c) => $c->matricula_id . '_' . $c->periodo_id);
        } else {
            $calMap = \App\Models\CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()->keyBy('matricula_id');
        }

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.docente.calificaciones_pdf', compact(
            'asignacion', 'docente', 'matriculas', 'periodos', 'calMap', 'esTecnica', 'inst', 'schoolYear'
        ))->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($asignacion->asignatura?->nombre ?? 'calificaciones');
        return $pdf->download("calificaciones_{$slug}.pdf");
    }

    public function descargarPlantillaCalificaciones(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura.resultadosAprendizaje', 'grupo']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        if ($esTecnica) {
            $ras   = $asignacion->asignatura->resultadosAprendizaje()->where('activo', true)->orderBy('numero')->get();
            $numRA = $ras->count() ?: ($asignacion->asignatura->num_ra ?? 3);
            $raCols = array_map(fn($n) => "ra{$n}", range(1, $numRA));
            $headers = array_merge(['numero_matricula', 'cedula', 'nombres', 'apellidos', 'periodo'], $raCols);
            $rows = $matriculas->map(fn($m) => array_merge([
                $m->numero_matricula ?? '',
                $m->estudiante?->cedula ?? '',
                $m->estudiante?->nombres ?? '',
                $m->estudiante?->apellidos ?? '',
                1,
            ], array_fill(0, $numRA, '')))->toArray();
            if (empty($rows)) {
                $rows[] = array_merge(['2024-00001', '001-0000000-0', 'Juan', 'Pérez', 1], array_fill(0, $numRA, 85));
            }
        } else {
            $headers = ['numero_matricula', 'cedula', 'nombres', 'apellidos', 'p1', 'p2', 'p3', 'p4'];
            $rows = $matriculas->map(fn($m) => [
                $m->numero_matricula ?? '', $m->estudiante?->cedula ?? '',
                $m->estudiante?->nombres ?? '', $m->estudiante?->apellidos ?? '',
                '', '', '', '',
            ])->toArray();
            if (empty($rows)) {
                $rows[] = ['2024-00001', '001-0000000-0', 'Juan', 'Pérez', 85, 90, 88, 92];
            }
        }

        $slug = Str::slug($asignacion->asignatura?->nombre ?? 'notas');
        return $this->generarCsvResponse($headers, $rows, "plantilla_calificaciones_{$slug}");
    }

    // ── Importar CSV: Calificaciones ─────────────────────────────────────
    public function importarCalificaciones(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'archivo'    => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'periodo_id' => 'nullable|exists:periodos,id',
        ]);

        $asignacion->load(['asignatura.resultadosAprendizaje', 'grupo']);
        $schoolYear = SchoolYear::actual();
        $esTecnica  = $asignacion->area === 'tecnica';
        $periodoId  = $request->periodo_id ?: null;

        $rows = $this->leerArchivoImport($request->file('archivo'));

        $matPorNum    = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->with('estudiante')->get()->keyBy('numero_matricula');
        $matPorCedula = $matPorNum->groupBy(fn($m) => $m->estudiante?->cedula ?? '');

        $importados = 0; $omitidos = 0; $errores = [];

        if ($esTecnica) {
            $ras   = $asignacion->asignatura->resultadosAprendizaje()->where('activo', true)->orderBy('numero')->get();
            $numRA = $ras->count() ?: ($asignacion->asignatura->num_ra ?? 3);
            $pesosRA = [];
            $pesosPersonalizados = $asignacion->pesos_ra ?? [];
            foreach ($ras as $ra) $pesosRA[$ra->numero] = $pesosPersonalizados[$ra->numero] ?? $ra->peso ?? round(100 / $numRA, 4);
            if (empty($pesosRA)) for ($i = 1; $i <= $numRA; $i++) $pesosRA[$i] = $pesosPersonalizados[$i] ?? round(100 / $numRA, 4);
        }

        foreach ($rows as $idx => $row) {
            $linea = $idx + 2;
            $mat   = $matPorNum->get(trim($row['numero_matricula'] ?? ''))
                  ?? $matPorCedula->get(trim($row['cedula'] ?? ''))?->first();
            if (! $mat) {
                $errores[] = "Fila {$linea}: estudiante no encontrado.";
                $omitidos++; continue;
            }

            if ($esTecnica) {
                $pNum = (int) trim($row['periodo'] ?? 1);
                $pId  = $periodoId ?: Periodo::where('school_year_id', $schoolYear?->id)
                    ->where('numero', $pNum)->value('id');
                if (! $pId) { $errores[] = "Fila {$linea}: período {$pNum} no encontrado."; $omitidos++; continue; }

                $data = ['modificado_por' => auth()->id()];
                $suma = 0.0; $hayNota = false;
                for ($i = 1; $i <= $numRA; $i++) {
                    $v = $this->parseNota($row["ra{$i}"] ?? '');
                    $data["ra{$i}"] = $v;
                    if ($v !== null) {
                        $pMax = $pesosRA[$i] ?? round(100 / $numRA, 4);
                        $suma += min($v, $pMax);
                        $hayNota = true;
                    }
                }
                $data['nota_final'] = $hayNota ? round($suma, 2) : null;

                Calificacion::updateOrCreate(
                    ['matricula_id' => $mat->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $pId],
                    $data
                );
            } else {
                $p1 = $this->parseNota($row['p1'] ?? ''); $p2 = $this->parseNota($row['p2'] ?? '');
                $p3 = $this->parseNota($row['p3'] ?? ''); $p4 = $this->parseNota($row['p4'] ?? '');
                $filled = array_filter([$p1, $p2, $p3, $p4], fn($v) => $v !== null);
                if (empty($filled)) { $errores[] = "Fila {$linea}: sin notas válidas."; $omitidos++; continue; }
                $nf = round(array_sum($filled) / count($filled), 2);

                CalificacionAcademica::updateOrCreate(
                    ['matricula_id' => $mat->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $schoolYear?->id],
                    [
                        'comp1_p1' => $p1, 'comp2_p1' => $p1, 'comp3_p1' => $p1, 'comp4_p1' => $p1,
                        'comp1_p2' => $p2, 'comp2_p2' => $p2, 'comp3_p2' => $p2, 'comp4_p2' => $p2,
                        'comp1_p3' => $p3, 'comp2_p3' => $p3, 'comp3_p3' => $p3, 'comp4_p3' => $p3,
                        'comp1_p4' => $p4, 'comp2_p4' => $p4, 'comp3_p4' => $p4, 'comp4_p4' => $p4,
                        'prom_comp1' => $p1, 'prom_comp2' => $p2, 'prom_comp3' => $p3, 'prom_comp4' => $p4,
                        'nota_final' => $nf, 'situacion' => $nf >= 70 ? 'A' : 'R',
                        'publicado'  => true,
                        'modificado_por' => auth()->id(),
                    ]
                );
            }
            $importados++;
        }

        Cache::forget("portal_docente_{$docente->id}_asignaciones_{$schoolYear?->id}");
        $msg = "Se importaron {$importados} nota(s).";
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";
        $url = route('portal.docente.calificaciones', $asignacion) . ($periodoId ? "?periodo_id={$periodoId}" : '');

        return redirect($url)->with('success', $msg)->with('errores_import', $errores);
    }

    // ── Plantilla CSV: Asistencia ────────────────────────────────────────
    public function descargarPlantillaAsistencia(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo']);
        $schoolYear = SchoolYear::actual();
        $fecha = $request->input('fecha', now()->format('Y-m-d'));

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $headers = ['numero_matricula', 'cedula', 'nombres', 'apellidos', 'fecha', 'estado'];
        $rows = $matriculas->map(fn($m) => [
            $m->numero_matricula ?? '', $m->estudiante?->cedula ?? '',
            $m->estudiante?->nombres ?? '', $m->estudiante?->apellidos ?? '',
            $fecha, 'presente',
        ])->toArray();
        if (empty($rows)) {
            $rows[] = ['2024-00001', '001-0000000-0', 'Juan', 'Pérez', $fecha, 'presente'];
        }

        $slug = Str::slug($asignacion->asignatura?->nombre ?? 'asistencia');
        return $this->generarCsvResponse($headers, $rows, "plantilla_asistencia_{$slug}_{$fecha}");
    }

    // ── Exportar asistencia completa a Excel ──────────────────────────────
    public function exportarAsistenciaExcel(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        // Todas las fechas únicas con registros
        $registros = \App\Models\Asistencia::where('asignacion_id', $asignacion->id)
            ->orderBy('fecha')
            ->get();

        $fechas = $registros->pluck('fecha')->unique()->sort()->values();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Asistencia');

        // Título
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($fechas->count() + 4);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValue('A1', 'Asistencia — ' . $asignacion->asignatura?->nombre . ' — ' . $asignacion->grupo?->grado?->nombre . ' ' . $asignacion->grupo?->seccion?->nombre);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Headers
        $ws->setCellValue('A3', '#');
        $ws->setCellValue('B3', 'Matrícula');
        $ws->setCellValue('C3', 'Apellidos');
        $ws->setCellValue('D3', 'Nombre');
        foreach ($fechas as $i => $f) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 5);
            $ws->setCellValue("{$col}3", \Carbon\Carbon::parse($f)->format('d/m'));
            $ws->getStyle("{$col}3")->getFont()->setBold(true)->setSize(8);
        }
        // Columna totales
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($fechas->count() + 5);
        $ws->setCellValue("{$totalCol}3", 'P/T');

        // Estilo header
        $ws->getStyle("A3:{$totalCol}3")->getFont()->setBold(true);
        $ws->getStyle("A3:{$totalCol}3")->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setRGB('1e3a6e');
        $ws->getStyle("A3:{$totalCol}3")->getFont()->getColor()->setRGB('ffffff');

        // Mapa matricula_id → fecha → estado
        $mapa = $registros->groupBy('matricula_id')->map(
            fn($rows) => $rows->keyBy(fn($r) => \Carbon\Carbon::parse($r->fecha)->format('Y-m-d'))
        );

        foreach ($matriculas as $i => $mat) {
            $row  = $i + 4;
            $est  = $mat->estudiante;
            $presentes = 0; $total = $fechas->count();

            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $est?->matricula ?? '');
            $ws->setCellValue("C{$row}", $est?->apellidos ?? $est?->apellido ?? '');
            $ws->setCellValue("D{$row}", $est?->nombres   ?? $est?->nombre   ?? '');

            foreach ($fechas as $j => $f) {
                $col    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 5);
                $estado = $mapa[$mat->id][\Carbon\Carbon::parse($f)->format('Y-m-d')]?->estado ?? '—';
                $letra  = match($estado) { 'presente' => 'P', 'ausente' => 'A', 'tardanza' => 'T', default => '—' };
                $ws->setCellValue("{$col}{$row}", $letra);
                if ($letra !== 'A') $presentes++;

                $color = match($letra) { 'P' => 'd1fae5', 'A' => 'fee2e2', 'T' => 'fef3c7', default => 'f3f4f6' };
                $ws->getStyle("{$col}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB($color);
            }

            $ws->setCellValue("{$totalCol}{$row}", "{$presentes}/{$total}");
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:D{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f8faff');
            }
        }

        foreach (range(1, $fechas->count() + 5) as $ci) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $slug     = Str::slug($asignacion->asignatura?->nombre ?? 'asistencia');
        $filename = "asistencia_{$slug}.xlsx";

        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Exportar Asistencia PDF ──────────────────────────────────────────
    public function exportarAsistenciaPdf(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $registros = \App\Models\Asistencia::where('asignacion_id', $asignacion->id)
            ->orderBy('fecha')->get();

        $fechas = $registros->pluck('fecha')->unique()->sort()->values();

        $mapa = $registros->groupBy('matricula_id')->map(
            fn($rows) => $rows->keyBy(fn($r) => \Carbon\Carbon::parse($r->fecha)->format('Y-m-d'))
        );

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.asistencia_pdf',
            compact('asignacion', 'matriculas', 'fechas', 'mapa', 'inst', 'schoolYear')
        )->setPaper('letter', 'landscape');

        $slug = Str::slug($asignacion->asignatura?->nombre ?? 'asistencia');
        return $pdf->download("asistencia_{$slug}.pdf");
    }

    // ── Importar CSV: Asistencia ─────────────────────────────────────────
    public function importarAsistencia(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate(['archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120']);

        $asignacion->load(['grupo']);
        $schoolYear = SchoolYear::actual();
        $rows = $this->leerArchivoImport($request->file('archivo'));

        $matPorNum    = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->with('estudiante')->get()->keyBy('numero_matricula');
        $matPorCedula = $matPorNum->groupBy(fn($m) => $m->estudiante?->cedula ?? '');

        $mapaEstados = ['tarde' => 'tardanza', 'excusa' => 'justificado'];
        $importados = 0; $omitidos = 0; $errores = [];

        foreach ($rows as $idx => $row) {
            $linea = $idx + 2;
            $mat   = $matPorNum->get(trim($row['numero_matricula'] ?? ''))
                  ?? $matPorCedula->get(trim($row['cedula'] ?? ''))?->first();
            if (! $mat) { $errores[] = "Fila {$linea}: estudiante no encontrado."; $omitidos++; continue; }

            try {
                $fecha = \Carbon\Carbon::parse(trim($row['fecha'] ?? ''))->format('Y-m-d');
            } catch (\Exception) {
                $errores[] = "Fila {$linea}: fecha inválida '{$row['fecha']}'."; $omitidos++; continue;
            }

            $estado = strtolower(trim($row['estado'] ?? 'presente'));
            $estado = $mapaEstados[$estado] ?? $estado;
            if (! in_array($estado, ['presente', 'ausente', 'tardanza', 'justificado', 'retiro'])) {
                $estado = 'presente';
            }

            Asistencia::updateOrCreate(
                ['asignacion_id' => $asignacion->id, 'matricula_id' => $mat->id, 'fecha' => $fecha],
                ['estado' => $estado, 'registrado_por' => auth()->id()]
            );

            if ($estado === 'ausente') $this->notificarAusencia($mat->id, $asignacion, $fecha);
            $importados++;
        }

        $msg = "Se importaron {$importados} registro(s) de asistencia.";
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        return redirect()->route('portal.docente.asistencia', $asignacion)
            ->with('success', $msg)->with('errores_import', $errores);
    }

    // ── Helpers internos ─────────────────────────────────────────────────
    private function generarCsvResponse(array $headers, array $rows, string $nombre): \Illuminate\Http\Response
    {
        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $row)) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '.csv"',
        ]);
    }

    private function leerArchivoImport($archivo): array
    {
        $ext = strtolower($archivo->getClientOriginalExtension());
        $rows = [];
        if (in_array($ext, ['xlsx', 'xls'])) {
            $sheet  = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname())
                        ->getActiveSheet()->toArray(null, true, true, false);
            $header = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), ''));
            }
        } else {
            $raw = file_get_contents($archivo->getPathname());
            if (($enc = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true)) && $enc !== 'UTF-8') {
                $raw = mb_convert_encoding($raw, 'UTF-8', $enc);
            }
            $lines  = array_values(array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")))));
            $delim  = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
            $header = array_map('strtolower', array_map('trim', str_getcsv($lines[0] ?? '', $delim)));
            foreach (array_slice($lines, 1) as $line) {
                if (trim($line) === '') continue;
                $rows[] = array_combine($header, array_pad(str_getcsv($line, $delim), count($header), ''));
            }
        }
        return $rows;
    }

    private function parseNota($val): ?float
    {
        $v = trim((string)$val);
        return ($v !== '' && is_numeric($v)) ? min(100, max(0, (float)$v)) : null;
    }

    private function notificarObservacion(Observacion $obs, Asignacion $asignacion): void
    {
        try {
            $estudiante = $obs->estudiante()->with('representantes.user')->first();
            if (! $estudiante) return;

            $tipoInfo = $obs->tipo_info;
            $titulo   = "Nueva observación: {$tipoInfo['label']}";
            $mensaje  = "El docente registró una observación en la materia {$asignacion->asignatura?->nombre}.";

            foreach ($estudiante->representantes as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar($rep->user_id, 'observacion', $titulo, $mensaje, ['observacion_id' => $obs->id]);
                }
            }

            if ($estudiante->user_id) {
                Notificacion::enviar($estudiante->user_id, 'observacion', $titulo, $mensaje, ['observacion_id' => $obs->id]);
            }
        } catch (\Throwable) {}
    }

    // ── Guardar pesos RA personalizados del docente ──────────────────────
    public function guardarPesosRa(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $numRA = $asignacion->asignatura->num_ra ?? 0;
        if ($numRA < 1) {
            return response()->json(['error' => 'Esta asignatura no tiene RAs configurados.'], 422);
        }

        $request->validate([
            'pesos'   => 'required|array',
            'pesos.*' => 'required|numeric|min:0|max:100',
        ]);

        $pesos = collect($request->pesos)->map(fn($v) => (float) $v);
        $suma  = $pesos->sum();

        if (abs($suma - 100) > 0.5) {
            return response()->json([
                'error' => "Los puntos deben sumar 100 (actualmente suman {$suma}).",
            ], 422);
        }

        // Guardar solo los RAs válidos (1..num_ra)
        $pesosIndexados = [];
        for ($i = 1; $i <= $numRA; $i++) {
            $pesosIndexados[$i] = $pesos->get($i) ?? round(100 / $numRA, 4);
        }

        $asignacion->update(['pesos_ra' => $pesosIndexados]);

        // Invalidar caché del portal
        $schoolYear = SchoolYear::actual();
        if ($schoolYear) {
            Cache::forget("portal_docente_{$docente->id}_asignaciones_{$schoolYear->id}");
        }

        return response()->json(['success' => true, 'pesos' => $pesosIndexados]);
    }
}
