<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\BoletinConfig;
use App\Models\BoletinObservacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\EvaluacionIndicador;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Promocion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoletinController extends Controller
{
    // ── Helper: determine if the current user can see all subjects ─────────
    private function puedeVerTodo(): bool
    {
        $user = auth()->user();
        return $user->hasAnyRole([
            'Administrador',
            'Director',
            'Coordinador Académico',
            'Coordinador Primer Ciclo',
            'Coordinador Segundo Ciclo',
            'Secretaría',
            'Secretaria Docente',
            'Personal Administrativo',
            'Encargado de Área',
        ]);
    }

    // ── Helper: get the Docente record for the auth user ──────────────────
    private function docenteActual(): ?Docente
    {
        $user = auth()->user();
        if ($user->hasRole('Docente')) {
            return Docente::where('user_id', $user->id)->first()
                ?? Docente::where('email', $user->email)->first();
        }
        return null;
    }

    // ── Helper: get asignacion IDs that the docente teaches (current year) ─
    private function asignacionesDocente(Docente $docente, int $schoolYearId): \Illuminate\Support\Collection
    {
        return Asignacion::where('docente_id', $docente->id)
            ->where('school_year_id', $schoolYearId)
            ->where('activo', true)
            ->pluck('id');
    }

    // ── Index: selection screen ────────────────────────────────────────────
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $ciclo = $request->query('ciclo');   // '1' | '2' | null
        $area  = $request->query('area');    // 'academica' | 'tecnica' | null

        $gruposQuery = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('grado');

        if ($ciclo === '1') {
            $gruposQuery->whereHas('grado', fn ($q) => $q->where('ciclo', 'primer_ciclo'));
        } elseif ($ciclo === '2') {
            $gruposQuery->whereHas('grado', fn ($q) => $q->where('ciclo', 'segundo_ciclo'));
        }

        $grupos = $gruposQuery
            ->get()
            ->sortBy(fn ($g) => [$g->grado->orden ?? 99, $g->seccion->nombre ?? '']);

        $periodos = $this->getPeriodos($schoolYear);

        $esDocente     = ! $this->puedeVerTodo();
        $docenteActual = $this->docenteActual();

        // Etiqueta para mostrar en la vista
        $cicloLabel = match(true) {
            $ciclo === '1'  => 'Primer Ciclo (1ro – 3ro)',
            $ciclo === '2' && $area === 'academica' => 'Segundo Ciclo – Área Académica (4to – 6to)',
            $ciclo === '2' && $area === 'tecnica'   => 'Segundo Ciclo – Área Técnica (4to – 6to)',
            $ciclo === '2'  => 'Segundo Ciclo (4to – 6to)',
            default         => 'Todos los Ciclos',
        };

        return view('admin.boletines.index', compact(
            'grupos', 'periodos', 'schoolYear',
            'esDocente', 'docenteActual',
            'ciclo', 'cicloLabel'
        ));
    }

    // ── Calcular ranking del estudiante en su grupo ───────────────────────
    private function calcularRanking(Matricula $matricula, Periodo $periodo): array
    {
        $schoolYear = SchoolYear::actual();

        // Todas las matrículas activas del mismo grupo
        $matriculaIds = Matricula::where('grupo_id', $matricula->grupo_id)
            ->where('school_year_id', $matricula->school_year_id ?? $schoolYear?->id)
            ->where('estado', 'activa')
            ->pluck('id');

        $total = $matriculaIds->count();
        if ($total <= 1) return ['puesto' => 1, 'total' => $total, 'percentil' => 100];

        // Calcular promedio general por matrícula
        $promedios = [];
        $allCalAc  = CalificacionAcademica::whereIn('matricula_id', $matriculaIds)
            ->where('school_year_id', $matricula->school_year_id ?? $schoolYear?->id)
            ->get()->groupBy('matricula_id');

        $allCalLeg = Calificacion::whereIn('matricula_id', $matriculaIds)
            ->where('periodo_id', $periodo->id)
            ->get()->groupBy('matricula_id');

        foreach ($matriculaIds as $mid) {
            $notas = [];
            if ($allCalAc->has($mid)) {
                foreach ($allCalAc[$mid] as $ca) {
                    if ($ca->nota_final !== null) $notas[] = (float) $ca->nota_final;
                }
            } elseif ($allCalLeg->has($mid)) {
                foreach ($allCalLeg[$mid] as $cal) {
                    if ($cal->nota_final !== null) $notas[] = (float) $cal->nota_final;
                }
            }
            $promedios[$mid] = count($notas) ? round(array_sum($notas) / count($notas), 2) : 0;
        }

        arsort($promedios);
        $posicion = array_search($matricula->id, array_keys($promedios));
        $puesto   = $posicion !== false ? $posicion + 1 : null;
        $percentil = $puesto ? round((($total - $puesto) / ($total - 1)) * 100) : null;

        return ['puesto' => $puesto, 'total' => $total, 'percentil' => $percentil];
    }

    // ── Progreso respecto al período anterior ─────────────────────────────
    private function calcularProgreso(array $tablaNotas, Periodo $periodo, $periodos): array
    {
        $prevPeriodo = $periodos->where('numero', $periodo->numero - 1)->first();
        if (! $prevPeriodo) return [];

        $progreso = [];
        foreach ($tablaNotas as $row) {
            $notaActual = $row['periodos'][$periodo->id]?->nota_final ?? null;
            $notaAnterior = $row['periodos'][$prevPeriodo->id]?->nota_final ?? null;

            if ($notaActual !== null && $notaAnterior !== null) {
                $diff = round($notaActual - $notaAnterior, 2);
                $progreso[$row['asignacion']->id] = [
                    'diff'      => $diff,
                    'direccion' => $diff > 0 ? 'sube' : ($diff < 0 ? 'baja' : 'igual'),
                ];
            }
        }
        return $progreso;
    }

    // ── Build shared boletin data (used by both web + PDF) ────────────────
    private function buildBoletinData(Matricula $matricula, Periodo $periodo): array
    {
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion', 'grupo.schoolYear', 'grupo.tutor']);

        $schoolYear    = $matricula->grupo->schoolYear ?? SchoolYear::actual();
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        // All periods of the school year ordered (cached)
        $periodos = $this->getPeriodos($schoolYear);

        // Determine role-based filtering
        $puedeVerTodo  = $this->puedeVerTodo();
        $docente       = $this->docenteActual();
        $vistaDocente  = ! $puedeVerTodo && $docente !== null;

        // All active asignaciones for this group (sorted by subject name)
        $asignacionesQuery = \App\Models\Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $matricula->grupo_id)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->where('activo', true);

        // If docente: restrict to their own asignaciones with published grades
        if ($vistaDocente) {
            $asignacionesQuery->where('docente_id', $docente->id);
        }

        $asignaciones = $asignacionesQuery->get()
            ->sortBy(fn ($a) => $a->asignatura->nombre ?? '');

        // Build grade matrix: [asignacion_id => [periodo_id => {nota_final, publicado}|null]]
        $allCalIds = $asignaciones->pluck('id');

        // Source 1: calificaciones_academicas (MINERD formato — una fila por asignacion/año)
        $calAcMap = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $allCalIds)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->get()
            ->keyBy('asignacion_id');

        // Source 2: calificaciones (formato legado — una fila por asignacion/periodo)
        $allPeriodoIds = $periodos->pluck('id');
        $calLegacyMap = Calificacion::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $allCalIds)
            ->whereIn('periodo_id', $allPeriodoIds)
            ->get()
            ->groupBy(fn ($c) => $c->asignacion_id . '_' . $c->periodo_id);

        $tablaNotas = [];
        foreach ($asignaciones as $asi) {
            $row = [
                'asignacion' => $asi,
                'asignatura' => $asi->asignatura?->nombre ?? '—',
                'docente'    => optional($asi->docente)->nombre_completo,
                'periodos'   => [],
                'promedio'   => null,
                'indicador'  => null,
                'publicado'  => false,
            ];

            $calAc        = $calAcMap->get($asi->id);
            $notasValidas = [];

            foreach ($periodos as $p) {
                $notaPeriodo = null;
                $publicadoPeriodo = false;

                if ($calAc) {
                    // calificaciones_academicas: calcular nota del período N
                    // promedio de los 4 componentes del período (comp1_pN … comp4_pN)
                    $n = $p->numero;
                    $componentes = array_filter([
                        $calAc->{"comp1_p{$n}"} ?? null,
                        $calAc->{"comp2_p{$n}"} ?? null,
                        $calAc->{"comp3_p{$n}"} ?? null,
                        $calAc->{"comp4_p{$n}"} ?? null,
                    ], fn ($v) => $v !== null && $v !== '');

                    if (count($componentes) > 0) {
                        $notaPeriodo = round(array_sum($componentes) / count($componentes), 2);
                    }
                    $publicadoPeriodo = (bool) $calAc->publicado;
                } else {
                    // formato legado
                    $cal = $calLegacyMap->get($asi->id . '_' . $p->id)?->first();
                    if ($cal && $cal->nota_final !== null) {
                        $notaPeriodo      = (float) $cal->nota_final;
                        $publicadoPeriodo = (bool) $cal->publicado;
                    }
                }

                $row['periodos'][$p->id] = $notaPeriodo !== null
                    ? (object) ['nota_final' => $notaPeriodo, 'publicado' => $publicadoPeriodo]
                    : null;

                if ($notaPeriodo !== null) {
                    $notasValidas[] = $notaPeriodo;
                }
                if ($publicadoPeriodo) {
                    $row['publicado'] = true;
                }
            }

            // Usar nota_final de calificaciones_academicas si existe, sino calcular promedio
            if ($calAc && $calAc->nota_final !== null) {
                $row['promedio'] = (float) $calAc->nota_final;
            } elseif (count($notasValidas)) {
                $row['promedio'] = round(array_sum($notasValidas) / count($notasValidas), 2);
            }

            if ($row['promedio'] !== null) {
                $p = $row['promedio'];
                $row['indicador'] = $p >= 90 ? 'Excelente'
                    : ($p >= 75 ? 'Bueno'
                    : ($p >= 60 ? 'En proceso' : 'Insuficiente'));
            }

            if ($vistaDocente && ! $row['publicado']) {
                continue;
            }
            $tablaNotas[] = $row;
        }

        // Annual overall average
        $promAnuales    = collect($tablaNotas)->pluck('promedio')->filter()->values();
        $promedioGeneral = $promAnuales->count() ? round($promAnuales->avg(), 2) : null;

        // Attendance per period — single query, filter in PHP per period
        $asistenciaPorPeriodo = [];
        $asistenciaTotales    = ['presente' => 0, 'ausente' => 0, 'tardanza' => 0,
                                  'justificado' => 0, 'tarde' => 0, 'excusa' => 0, 'retiro' => 0, 'total' => 0];

        $fechaMin = $periodos->whereNotNull('fecha_inicio')->min('fecha_inicio') ?? '1900-01-01';
        $fechaMax = $periodos->whereNotNull('fecha_fin')->max('fecha_fin') ?? '2100-12-31';
        $todasAsistencias = Asistencia::where('matricula_id', $matricula->id)
            ->whereBetween('fecha', [$fechaMin, $fechaMax])
            ->get();

        foreach ($periodos as $p) {
            $asis = ($p->fecha_inicio && $p->fecha_fin)
                ? $todasAsistencias->filter(
                    fn ($a) => $a->fecha >= $p->fecha_inicio && $a->fecha <= $p->fecha_fin
                )
                : $todasAsistencias;

            $total    = $asis->count();
            $presente = $asis->whereIn('estado', ['presente'])->count();
            $ausente  = $asis->whereIn('estado', ['ausente'])->count();
            $tardanza = $asis->whereIn('estado', ['tardanza', 'tarde'])->count();
            $excusa   = $asis->whereIn('estado', ['justificado', 'excusa'])->count();
            $retiro   = $asis->whereIn('estado', ['retiro'])->count();

            $asistenciaPorPeriodo[$p->id] = [
                'total'       => $total,
                'presente'    => $presente,
                'ausente'     => $ausente,
                'tardanza'    => $tardanza,
                'justificado' => $excusa,
                'retiro'      => $retiro,
                'pct'         => $total > 0
                    ? round((($presente + $tardanza + $excusa) / $total) * 100, 1)
                    : null,
            ];

            $asistenciaTotales['total']       += $total;
            $asistenciaTotales['presente']    += $presente;
            $asistenciaTotales['ausente']     += $ausente;
            $asistenciaTotales['tardanza']    += $tardanza;
            $asistenciaTotales['justificado'] += $excusa;
            $asistenciaTotales['retiro']      += $retiro;
        }

        $totalDias = $asistenciaTotales['total'];
        $asistenciaTotales['pct'] = $totalDias > 0
            ? round((($asistenciaTotales['presente'] + $asistenciaTotales['tardanza'] + $asistenciaTotales['justificado']) / $totalDias) * 100, 1)
            : null;

        // Learning indicators for current period
        $evaluaciones = EvaluacionIndicador::with('indicador')
            ->where('matricula_id', $matricula->id)
            ->where('periodo_id', $periodo->id)
            ->get();

        // Observations from current period grades (legacy field)
        $observacionesList = Calificacion::with('asignacion.asignatura')
            ->where('matricula_id', $matricula->id)
            ->where('periodo_id', $periodo->id)
            ->whereNotNull('observaciones')
            ->where('observaciones', '!=', '')
            ->get();

        // Structured observations (BoletinObservacion) — grouped by tipo
        // Includes period-specific + general (periodo_id = null) observations
        $boletinObservaciones = BoletinObservacion::with('docente')
            ->where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->where(function ($q) use ($periodo) {
                $q->where('periodo_id', $periodo->id)
                  ->orWhereNull('periodo_id');
            })
            ->orderByRaw("FIELD(tipo, 'academica','conducta','sugerencia','general')")
            ->get()
            ->groupBy('tipo');

        // Promotion status for this student in the active school year
        $promocion = Promocion::where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->first();

        // Ranking y progreso
        $rankingGrupo = $this->calcularRanking($matricula, $periodo);
        $progreso     = $this->calcularProgreso($tablaNotas, $periodo, $periodos);

        return compact(
            'matricula', 'periodo', 'periodos', 'schoolYear', 'boletinConfig',
            'tablaNotas', 'promedioGeneral',
            'asistenciaPorPeriodo', 'asistenciaTotales',
            'evaluaciones', 'observacionesList',
            'boletinObservaciones', 'promocion',
            'vistaDocente', 'rankingGrupo', 'progreso'
        );
    }

    // ── Ver estudiante: full boletin web preview ───────────────────────────
    public function verEstudiante(Matricula $matricula, Periodo $periodo)
    {
        // Docentes can only view boletines of their own groups
        if (! $this->puedeVerTodo()) {
            $docente = $this->docenteActual();
            if ($docente) {
                $schoolYear = SchoolYear::actual();
                $grupoIds   = Asignacion::where('docente_id', $docente->id)
                    ->where('school_year_id', $schoolYear?->id ?? 0)
                    ->where('activo', true)
                    ->pluck('grupo_id')
                    ->unique();

                if (! $grupoIds->contains($matricula->grupo_id)) {
                    abort(403, 'No tienes acceso al boletín de este estudiante.');
                }
            }
        }

        $data = $this->buildBoletinData($matricula, $periodo);
        return view('admin.boletines.ver', $data);
    }

    // ── PDF download ───────────────────────────────────────────────────────
    public function pdf(Matricula $matricula, Periodo $periodo)
    {
        // Same access control as verEstudiante
        if (! $this->puedeVerTodo()) {
            $docente = $this->docenteActual();
            if ($docente) {
                $schoolYear = SchoolYear::actual();
                $grupoIds   = Asignacion::where('docente_id', $docente->id)
                    ->where('school_year_id', $schoolYear?->id ?? 0)
                    ->where('activo', true)
                    ->pluck('grupo_id')
                    ->unique();

                if (! $grupoIds->contains($matricula->grupo_id)) {
                    abort(403, 'No tienes acceso al boletín de este estudiante.');
                }
            }
        }

        $data = $this->buildBoletinData($matricula, $periodo);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)
            ->setPaper('letter', 'portrait');

        $apellidos = $matricula->estudiante->apellidos ?? 'estudiante';
        $filename  = 'boletin_' . \Illuminate\Support\Str::slug($apellidos) . '_' . $periodo->numero . '.pdf';

        return $pdf->download($filename);
    }

    // ── Grupo: all boletines for a grupo in a periodo ─────────────────────
    public function grupo(Request $request)
    {
        $request->validate([
            'grupo_id'   => 'required|exists:grupos,id',
            'periodo_id' => 'required|exists:periodos,id',
        ]);

        $schoolYear = SchoolYear::actual();
        $periodo    = Periodo::findOrFail($request->periodo_id);
        $grupo      = Grupo::with(['grado', 'seccion'])->findOrFail($request->grupo_id);

        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        // Filtrar por año escolar activo para excluir estudiantes de años anteriores
        $matriculas = $grupo->matriculas()
            ->activas()
            ->delAnio($schoolYear->id)
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        // Bulk load — 2 queries instead of 2×N
        $matriculaIds = $matriculas->pluck('id');

        // Notas desde calificaciones_academicas
        $allCalAc = CalificacionAcademica::with(['asignacion.asignatura'])
            ->whereIn('matricula_id', $matriculaIds)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->groupBy('matricula_id');

        // Fallback: calificaciones formato legado
        $allCalificaciones = Calificacion::with(['asignacion.asignatura'])
            ->whereIn('matricula_id', $matriculaIds)
            ->where('periodo_id', $periodo->id)
            ->get()
            ->groupBy('matricula_id');

        $allAsistencias = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->whereBetween('fecha', [
                $periodo->fecha_inicio ?? '1900-01-01',
                $periodo->fecha_fin    ?? '2100-12-31',
            ])
            ->get()
            ->groupBy('matricula_id');

        $toIndicador = fn (?float $n) => $n === null ? null
            : ($n >= 90 ? 'Excelente' : ($n >= 75 ? 'Bueno' : ($n >= 60 ? 'En proceso' : 'Insuficiente')));

        $boletines = [];
        foreach ($matriculas as $matricula) {
            $calAcRows  = $allCalAc->get($matricula->id) ?? collect();
            $calLegRows = $allCalificaciones->get($matricula->id) ?? collect();
            $asistencias = $allAsistencias->get($matricula->id) ?? collect();

            // Normalizar a estructura común: [asignatura, nota_final, indicador]
            $notas = [];
            if ($calAcRows->isNotEmpty()) {
                foreach ($calAcRows as $calAc) {
                    $nf = $calAc->nota_final !== null ? (float) $calAc->nota_final : null;
                    $notas[] = [
                        'asignatura' => $calAc->asignacion?->asignatura?->nombre ?? '—',
                        'nota_final' => $nf,
                        'indicador'  => $toIndicador($nf),
                    ];
                }
            } else {
                foreach ($calLegRows as $cal) {
                    $nf = $cal->nota_final !== null ? (float) $cal->nota_final : null;
                    $notas[] = [
                        'asignatura' => $cal->asignacion?->asignatura?->nombre ?? '—',
                        'nota_final' => $nf,
                        'indicador'  => $toIndicador($nf),
                    ];
                }
            }
            usort($notas, fn ($a, $b) => strcmp($a['asignatura'], $b['asignatura']));

            $notasFinales = collect($notas)->pluck('nota_final')->filter();

            $boletines[$matricula->id] = [
                'matricula'       => $matricula,
                'notas'           => $notas,
                'promedioGeneral' => $notasFinales->count() > 0 ? round($notasFinales->avg(), 2) : null,
                'asistencia'      => [
                    'presente'    => $asistencias->where('estado', 'presente')->count(),
                    'ausente'     => $asistencias->where('estado', 'ausente')->count(),
                    'tardanza'    => $asistencias->whereIn('estado', ['tardanza', 'tarde'])->count(),
                    'justificado' => $asistencias->whereIn('estado', ['justificado', 'excusa'])->count(),
                    'total'       => $asistencias->count(),
                ],
            ];
        }

        return view('admin.boletines.grupo', compact(
            'grupo', 'periodo', 'schoolYear', 'boletinConfig', 'matriculas', 'boletines'
        ));
    }

    // ── Exportar todos los boletines de un grupo como ZIP ─────────────────
    public function zipGrupo(Request $request)
    {
        $request->validate([
            'grupo_id'   => 'required|exists:grupos,id',
            'periodo_id' => 'required|exists:periodos,id',
        ]);

        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $grupo   = Grupo::with(['grado', 'seccion', 'schoolYear'])->findOrFail($request->grupo_id);
        $periodo = Periodo::findOrFail($request->periodo_id);

        $matriculas = $grupo->matriculas()->activas()->with('estudiante')->orderBy('numero_orden')->get();

        // Advertencia preventiva si el grupo es muy grande
        if ($matriculas->count() > 60) {
            return back()->with('error',
                "El grupo tiene {$matriculas->count()} estudiantes. El límite para exportación ZIP es 60. " .
                'Exporta por secciones o genera los boletines individualmente.'
            );
        }

        // Precargar relaciones del grupo UNA sola vez; buildBoletinData las verá ya cargadas
        // en cada $matricula, evitando N cargas idénticas del mismo grupo dentro del loop.
        $grupo->loadMissing(['grado', 'seccion', 'schoolYear', 'tutor']);
        foreach ($matriculas as $matricula) {
            $matricula->setRelation('grupo', $grupo);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'boletines_') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        foreach ($matriculas as $matricula) {
            try {
                $data       = $this->buildBoletinData($matricula, $periodo);
                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf', $data)
                    ->setPaper('letter', 'portrait')
                    ->output();
                $nombre = Str::slug($matricula->estudiante->apellidos . ' ' . $matricula->estudiante->nombres);
                $zip->addFromString("boletin_{$nombre}.pdf", $pdfContent);
            } catch (\Exception $e) {
                // Skip student if boletin fails; continue with others
            }
        }

        $zip->close();

        $nombreArchivo = 'boletines_' . Str::slug($grupo->nombre_completo) . '_P' . $periodo->numero . '.zip';

        return response()->download($zipPath, $nombreArchivo, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF Anual (todos los períodos en un solo documento) ───────────────
    public function pdfAnual(Matricula $matricula)
    {
        if (! $this->puedeVerTodo()) {
            $docente = $this->docenteActual();
            if ($docente) {
                $schoolYear = SchoolYear::actual();
                $grupoIds   = Asignacion::where('docente_id', $docente->id)
                    ->where('school_year_id', $schoolYear?->id ?? 0)
                    ->where('activo', true)
                    ->pluck('grupo_id')->unique();
                if (! $grupoIds->contains($matricula->grupo_id)) {
                    abort(403);
                }
            }
        }

        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion', 'grupo.schoolYear', 'grupo.tutor']);
        $schoolYear    = $matricula->grupo->schoolYear ?? SchoolYear::actual();
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        $periodos = $this->getPeriodos($schoolYear);

        // Construir tabla anual con todas las notas de todos los períodos
        $asignaciones = \App\Models\Asignacion::with(['asignatura', 'docente'])
            ->where('grupo_id', $matricula->grupo_id)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->where('activo', true)
            ->get()->sortBy(fn ($a) => $a->asignatura->nombre ?? '');

        $allCalIds = $asignaciones->pluck('id');

        $calAcMap = CalificacionAcademica::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $allCalIds)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->get()->keyBy('asignacion_id');

        $calLegacyMap = Calificacion::where('matricula_id', $matricula->id)
            ->whereIn('asignacion_id', $allCalIds)
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()->groupBy(fn ($c) => $c->asignacion_id . '_' . $c->periodo_id);

        $tablaAnual = [];
        foreach ($asignaciones as $asi) {
            $row = ['asignatura' => $asi->asignatura?->nombre ?? '—', 'periodos' => [], 'final' => null, 'indicador' => null];
            $calAc = $calAcMap->get($asi->id);
            $notasValidas = [];

            foreach ($periodos as $p) {
                $nota = null;
                if ($calAc) {
                    $n = $p->numero;
                    $comps = array_filter([
                        $calAc->{"comp1_p{$n}"} ?? null, $calAc->{"comp2_p{$n}"} ?? null,
                        $calAc->{"comp3_p{$n}"} ?? null, $calAc->{"comp4_p{$n}"} ?? null,
                    ], fn ($v) => $v !== null && $v !== '');
                    if (count($comps)) $nota = round(array_sum($comps) / count($comps), 2);
                } else {
                    $cal = $calLegacyMap->get($asi->id . '_' . $p->id)?->first();
                    if ($cal && $cal->nota_final !== null) $nota = (float) $cal->nota_final;
                }
                $row['periodos'][$p->id] = $nota;
                if ($nota !== null) $notasValidas[] = $nota;
            }

            if ($calAc && $calAc->nota_final !== null) {
                $row['final'] = (float) $calAc->nota_final;
            } elseif (count($notasValidas)) {
                $row['final'] = round(array_sum($notasValidas) / count($notasValidas), 2);
            }

            if ($row['final'] !== null) {
                $f = $row['final'];
                $row['indicador'] = $f >= 90 ? 'Excelente' : ($f >= 75 ? 'Bueno' : ($f >= 60 ? 'En proceso' : 'Insuficiente'));
            }
            $tablaAnual[] = $row;
        }

        $promedioAnual = collect($tablaAnual)->pluck('final')->filter();
        $promedioAnual = $promedioAnual->count() ? round($promedioAnual->avg(), 2) : null;

        $promocion     = Promocion::where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear?->id ?? 0)->first();

        $rankingGrupo  = $this->calcularRanking($matricula, $periodos->last() ?? new Periodo());

        $asistenciaTotales = $this->calcularAsistenciaTotales($matricula, $periodos);

        $boletinObservaciones = BoletinObservacion::with('docente')
            ->where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear?->id ?? 0)
            ->whereNull('periodo_id')
            ->orderByRaw("FIELD(tipo,'academica','conducta','sugerencia','general')")
            ->get()->groupBy('tipo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.boletines.pdf_anual', compact(
            'matricula', 'periodos', 'schoolYear', 'boletinConfig',
            'tablaAnual', 'promedioAnual', 'promocion', 'rankingGrupo',
            'asistenciaTotales', 'boletinObservaciones'
        ))->setPaper('letter', 'landscape');

        $apellidos = Str::slug($matricula->estudiante->apellidos ?? 'estudiante');
        return $pdf->download("boletin_anual_{$apellidos}.pdf");
    }

    // ── Helper: totales asistencia para todos los períodos ─────────────────
    private function calcularAsistenciaTotales(Matricula $matricula, $periodos): array
    {
        $fechaMin = $periodos->whereNotNull('fecha_inicio')->min('fecha_inicio') ?? '1900-01-01';
        $fechaMax = $periodos->whereNotNull('fecha_fin')->max('fecha_fin') ?? '2100-12-31';
        $asist = Asistencia::where('matricula_id', $matricula->id)
            ->whereBetween('fecha', [$fechaMin, $fechaMax])->get();
        $total = $asist->count();
        $presente  = $asist->where('estado', 'presente')->count();
        $ausente   = $asist->where('estado', 'ausente')->count();
        $tardanza  = $asist->whereIn('estado', ['tardanza', 'tarde'])->count();
        $excusa    = $asist->whereIn('estado', ['justificado', 'excusa'])->count();
        return [
            'total' => $total, 'presente' => $presente, 'ausente' => $ausente,
            'tardanza' => $tardanza, 'justificado' => $excusa,
            'pct' => $total > 0 ? round((($presente + $tardanza + $excusa) / $total) * 100, 1) : null,
        ];
    }

    // ── Guardar observación desde la vista del boletín ────────────────────
    public function guardarObservacion(Request $request, Matricula $matricula, Periodo $periodo)
    {
        abort_unless($this->puedeVerTodo(), 403);

        $data = $request->validate([
            'tipo'      => 'required|in:academica,conducta,sugerencia,general',
            'contenido' => 'required|string|max:1000',
        ]);

        $schoolYear = SchoolYear::actual();
        $docente    = Docente::where('user_id', auth()->id())->first();

        BoletinObservacion::create([
            'matricula_id'  => $matricula->id,
            'school_year_id'=> $schoolYear?->id,
            'periodo_id'    => $periodo->id,
            'tipo'          => $data['tipo'],
            'contenido'     => $data['contenido'],
            'docente_id'    => $docente?->id,
        ]);

        return back()->with('success', 'Observación guardada correctamente.');
    }

    // ── Eliminar observación ──────────────────────────────────────────────
    public function eliminarObservacion(BoletinObservacion $observacion)
    {
        abort_unless($this->puedeVerTodo(), 403);
        $observacion->delete();
        return back()->with('success', 'Observación eliminada.');
    }

    /**
     * Expone buildBoletinData como método público para uso desde otros controladores
     * (p. ej. CierreAnoController::boletinesMasivos).
     */
    public function buildBoletinDataPublic(Matricula $matricula, Periodo $periodo): array
    {
        return $this->buildBoletinData($matricula, $periodo);
    }
}
