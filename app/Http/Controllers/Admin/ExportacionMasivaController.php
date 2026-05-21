<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\BoletinConfig;
use App\Models\CalificacionAcademica;
use App\Models\ConfigInstitucional;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class ExportacionMasivaController extends Controller
{
    // ── Index: formulario de selección ───────────────────────────────────
    public function index()
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('grado')
            ->get()
            ->sortBy(fn ($g) => [$g->grado->orden ?? 99, $g->seccion->nombre ?? ''])
            ->values();

        $periodos = Periodo::where('school_year_id', $schoolYear->id)
            ->orderBy('numero')
            ->get();

        return view('admin.exportacion_masiva.index', compact('grupos', 'periodos', 'schoolYear'));
    }

    // ── POST: generar archivos y devolver ZIP ─────────────────────────────
    public function exportar(Request $request)
    {
        $request->validate([
            'exportar'   => 'required|array|min:1',
            'exportar.*' => 'in:matricula,notas,asistencia',
            'grupo_ids'  => 'nullable|array',
            'grupo_ids.*'=> 'exists:grupos,id',
            'periodo_id' => 'nullable|exists:periodos,id',
        ]);

        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo.');
        }

        $exportar  = $request->input('exportar', []);
        $grupoIds  = $request->input('grupo_ids', []);   // vacío = todos
        $periodoId = $request->input('periodo_id');

        // Cargar grupos seleccionados (o todos del año)
        $gruposQuery = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('grado');

        if (! empty($grupoIds)) {
            $gruposQuery->whereIn('id', $grupoIds);
        }

        $grupos = $gruposQuery->get()
            ->sortBy(fn ($g) => [$g->grado->orden ?? 99, $g->seccion->nombre ?? ''])
            ->values();

        // Período (necesario para notas/actas)
        $periodo = $periodoId
            ? Periodo::find($periodoId)
            : Periodo::where('school_year_id', $schoolYear->id)->orderBy('numero')->first();

        // Crear ZIP temporal
        $zipPath = tempnam(sys_get_temp_dir(), 'exportacion_') . '.zip';
        $zip     = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP temporal.');
        }

        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = BoletinConfig::getOrCreate($schoolYear->id);

        $archivosGenerados = 0;
        $errores           = [];

        // ── 1. Lista de matrícula (Excel / CSV) ───────────────────────────
        if (in_array('matricula', $exportar)) {
            try {
                $csvContent = $this->generarListaMatriculaCsv($schoolYear, $grupos, $inst);
                $zip->addFromString('lista_matricula.csv', $csvContent);
                $archivosGenerados++;
            } catch (\Throwable $e) {
                $errores[] = 'Lista de matrícula: ' . $e->getMessage();
            }
        }

        // ── 2. Notas por grupo (PDF resumen de calificaciones) ────────────
        if (in_array('notas', $exportar) && $periodo) {
            foreach ($grupos as $grupo) {
                try {
                    $pdfContent = $this->generarNotasGrupoPdf($grupo, $schoolYear, $periodo, $inst, $config);
                    $slug       = Str::slug($grupo->nombre_completo);
                    $zip->addFromString("notas_{$slug}.pdf", $pdfContent);
                    $archivosGenerados++;
                } catch (\Throwable $e) {
                    $errores[] = "Notas {$grupo->nombre_completo}: " . $e->getMessage();
                }
            }
        }

        // ── 3. Asistencia por grupo (PDF) ─────────────────────────────────
        if (in_array('asistencia', $exportar)) {
            foreach ($grupos as $grupo) {
                try {
                    $pdfContent = $this->generarAsistenciaGrupoPdf($grupo, $schoolYear, $periodo, $inst, $config);
                    $slug       = Str::slug($grupo->nombre_completo);
                    $zip->addFromString("asistencia_{$slug}.pdf", $pdfContent);
                    $archivosGenerados++;
                } catch (\Throwable $e) {
                    $errores[] = "Asistencia {$grupo->nombre_completo}: " . $e->getMessage();
                }
            }
        }

        $zip->close();

        if ($archivosGenerados === 0) {
            @unlink($zipPath);
            return back()->with('error', 'No se pudo generar ningún archivo. ' . implode(' | ', $errores));
        }

        $nombreZip = 'exportacion_masiva_' . now()->format('Ymd_His') . '.zip';

        return response()->download($zipPath, $nombreZip, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ── Genera CSV con lista de matriculados ──────────────────────────────
    private function generarListaMatriculaCsv(SchoolYear $schoolYear, $grupos, string $inst): string
    {
        $grupoIds = $grupos->pluck('id');

        $matriculas = Matricula::with([
                'estudiante.representantes',
                'grupo.grado',
                'grupo.seccion',
            ])
            ->where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->when($grupoIds->isNotEmpty(), fn ($q) => $q->whereIn('grupo_id', $grupoIds))
            ->orderBy('grupo_id')
            ->orderBy('numero_orden')
            ->get();

        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 para compatibilidad con Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Encabezado
        fputcsv($handle, [
            '#', 'No. Orden', 'Apellidos', 'Nombres', 'No. Matrícula',
            'Cédula', 'Fecha Nac.', 'Grupo', 'Representante', 'Teléfono Rep.',
        ]);

        foreach ($matriculas as $i => $m) {
            $est = $m->estudiante;
            $rep = $est?->representantes?->first();
            $grp = $m->grupo;

            fputcsv($handle, [
                $i + 1,
                $m->numero_orden ?? '',
                $est?->apellidos ?? '',
                $est?->nombres   ?? '',
                $est?->numero_matricula ?? '',
                $est?->cedula    ?? '',
                $est?->fecha_nacimiento
                    ? \Carbon\Carbon::parse($est->fecha_nacimiento)->format('d/m/Y')
                    : '',
                $grp
                    ? trim(($grp->grado->nombre ?? '') . ' ' . ($grp->seccion->nombre ?? ''))
                    : '',
                $rep
                    ? trim(($rep->nombres ?? $rep->nombre ?? '') . ' ' . ($rep->apellidos ?? $rep->apellido ?? ''))
                    : '',
                $rep?->celular ?? $rep?->telefono ?? '',
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    // ── Genera PDF de notas/resumen para un grupo ─────────────────────────
    private function generarNotasGrupoPdf(
        Grupo $grupo,
        SchoolYear $schoolYear,
        Periodo $periodo,
        string $inst,
        ?BoletinConfig $config
    ): string {
        $grupo->loadMissing(['grado', 'seccion']);

        $matriculas   = $grupo->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        $periodos = Periodo::where('school_year_id', $schoolYear->id)
            ->orderBy('numero')
            ->get();

        $asignaciones = Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($a) => $a->asignatura?->nombre ?? '');

        $matriculaIds  = $matriculas->pluck('id');
        $asignacionIds = $asignaciones->pluck('id');
        $periodoIds    = $periodos->pluck('id');

        // Calificaciones académicas (MINERD)
        $calAcMap = CalificacionAcademica::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignacionIds)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->groupBy(fn ($c) => "{$c->matricula_id}_{$c->asignacion_id}");

        // Calificaciones legado
        $calLegacyMap = \App\Models\Calificacion::whereIn('matricula_id', $matriculaIds)
            ->whereIn('asignacion_id', $asignacionIds)
            ->whereIn('periodo_id', $periodoIds)
            ->get()
            ->keyBy(fn ($c) => "{$c->matricula_id}_{$c->asignacion_id}_{$c->periodo_id}");

        // Construir matriz: [matricula_id][asignacion_id][periodo_id] => nota_final|null
        $matrix = [];
        foreach ($matriculas as $m) {
            foreach ($asignaciones as $asi) {
                foreach ($periodos as $p) {
                    $calAc = $calAcMap->get("{$m->id}_{$asi->id}")?->first();
                    if ($calAc) {
                        $n       = $p->numero;
                        $comps   = array_filter([
                            $calAc->{"comp1_p{$n}"} ?? null,
                            $calAc->{"comp2_p{$n}"} ?? null,
                            $calAc->{"comp3_p{$n}"} ?? null,
                            $calAc->{"comp4_p{$n}"} ?? null,
                        ], fn ($v) => $v !== null && $v !== '');

                        $nota = count($comps) > 0
                            ? (object) ['nota_final' => round(array_sum($comps) / count($comps), 2)]
                            : null;
                    } else {
                        $cal  = $calLegacyMap->get("{$m->id}_{$asi->id}_{$p->id}");
                        $nota = $cal ? (object) ['nota_final' => $cal->nota_final] : null;
                    }
                    $matrix[$m->id][$asi->id][$p->id] = $nota;
                }
            }
        }

        return Pdf::loadView('admin.exportacion_masiva.notas_grupo_pdf', compact(
            'grupo', 'schoolYear', 'periodo', 'periodos',
            'matriculas', 'asignaciones', 'matrix', 'inst', 'config'
        ))->setPaper('letter', 'landscape')->output();
    }

    // ── Genera PDF de asistencia general para un grupo ────────────────────
    private function generarAsistenciaGrupoPdf(
        Grupo $grupo,
        SchoolYear $schoolYear,
        ?Periodo $periodo,
        string $inst,
        ?BoletinConfig $config
    ): string {
        $grupo->loadMissing(['grado', 'seccion']);

        $matriculas = $grupo->matriculas()
            ->activas()
            ->with('estudiante')
            ->orderBy('numero_orden')
            ->get();

        $matriculaIds = $matriculas->pluck('id');

        // Rango de fechas: todo el año escolar o período seleccionado
        $fechaMin = $periodo?->fecha_inicio ?? $schoolYear->fecha_inicio ?? '1900-01-01';
        $fechaMax = $periodo?->fecha_fin    ?? $schoolYear->fecha_fin    ?? '2100-12-31';

        $todasAsistencias = Asistencia::whereIn('matricula_id', $matriculaIds)
            ->whereBetween('fecha', [$fechaMin, $fechaMax])
            ->get()
            ->groupBy('matricula_id');

        $stats = [];
        foreach ($matriculas as $m) {
            $asis      = $todasAsistencias->get($m->id, collect());
            $total     = $asis->count();
            $presente  = $asis->whereIn('estado', ['presente'])->count();
            $ausente   = $asis->whereIn('estado', ['ausente'])->count();
            $tarde     = $asis->whereIn('estado', ['tardanza', 'tarde'])->count();
            $excusa    = $asis->whereIn('estado', ['justificado', 'excusa'])->count();
            $efectiva  = $presente + $tarde + $excusa;
            $pct       = $total > 0 ? round(($efectiva / $total) * 100, 1) : null;

            $stats[$m->id] = [
                'matricula'  => $m,
                'presente'   => $presente,
                'ausente'    => $ausente,
                'tarde'      => $tarde,
                'excusa'     => $excusa,
                'total'      => $total,
                'porcentaje' => $pct,
                'alerta'     => $pct !== null && $pct < 75,
            ];
        }

        return Pdf::loadView('admin.exportacion_masiva.asistencia_grupo_pdf', compact(
            'grupo', 'schoolYear', 'periodo', 'stats', 'inst', 'config'
        ))->setPaper('letter', 'portrait')->output();
    }
}
