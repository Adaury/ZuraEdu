<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Traits\NormalizesFileEncoding;
use App\Models\Asignacion;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PlanifAnualController extends Controller
{
    use HasDocenteContext;
    use NormalizesFileEncoding;

    /** Columnas del CSV de unidades, en el orden en que se leen/escriben. */
    private const COLUMNAS_UNIDAD = [
        'numero', 'titulo', 'periodo', 'semanas', 'fecha_inicio', 'fecha_fin',
        'objetivos', 'competencias', 'indicadores', 'contenidos',
        'estrategias', 'recursos', 'evaluacion',
    ];

    public function index(Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $planes = PlanifAnual::with(['unidades'])
            ->where('asignacion_id', $asignacion->id)
            ->where('docente_id', $docente->id)
            ->latest()
            ->get();

        return view('portal.docente.planif_anual.index', compact(
            'docente', 'asignacion', 'planes', 'schoolYear'
        ));
    }

    public function store(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate(['titulo' => 'required|string|max:200']);

        $plan = PlanifAnual::create([
            'docente_id'     => $docente->id,
            'asignacion_id'  => $asignacion->id,
            'school_year_id' => SchoolYear::actual()?->id,
            'titulo'         => $request->titulo,
            'descripcion'    => $request->descripcion,
        ]);

        return redirect()->route('portal.docente.planif-anual.show', [$asignacion, $plan]);
    }

    public function show(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id || $plan->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $plan->load('unidades');
        $competencias = PlanifUnidad::COMPETENCIAS;

        return view('portal.docente.planif_anual.show', compact(
            'docente', 'asignacion', 'plan', 'competencias'
        ));
    }

    public function updatePlan(Request $request, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $plan->update($request->only(['titulo', 'descripcion']));
        return response()->json(['ok' => true]);
    }

    public function destroy(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $plan->delete();
        return redirect()->route('portal.docente.planif-anual.index', $asignacion)
            ->with('success', 'Plan eliminado.');
    }

    public function storeUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $siguiente = ($plan->unidades()->max('numero') ?? 0) + 1;

        $unidad = PlanifUnidad::create([
            'planif_anual_id' => $plan->id,
            'numero'          => $siguiente,
            'titulo'          => $request->input('titulo', "Unidad {$siguiente}"),
            'periodo'         => $request->input('periodo'),
        ]);

        return response()->json(['ok' => true, 'unidad' => $unidad]);
    }

    public function updateUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $unidad->update($request->only([
            'titulo', 'periodo', 'semanas', 'objetivos', 'competencias',
            'indicadores', 'contenidos', 'estrategias', 'recursos',
            'evaluacion', 'fecha_inicio', 'fecha_fin',
        ]));

        return response()->json(['ok' => true]);
    }

    public function destroyUnidad(Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $unidad->delete();

        // Renumerar
        $plan->unidades()->orderBy('numero')->each(function ($u, $i) {
            $u->update(['numero' => $i + 1]);
        });

        return response()->json(['ok' => true]);
    }

    public function moverUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id || $unidad->planif_anual_id !== $plan->id) abort(403);

        $dir = $request->input('dir'); // 'up' | 'down'
        $unidades = $plan->unidades()->orderBy('numero')->get();
        $idx = $unidades->search(fn($u) => $u->id === $unidad->id);

        if ($dir === 'up' && $idx > 0) {
            $prev = $unidades[$idx - 1];
            [$unidad->numero, $prev->numero] = [$prev->numero, $unidad->numero];
            $unidad->save(); $prev->save();
        } elseif ($dir === 'down' && $idx < $unidades->count() - 1) {
            $next = $unidades[$idx + 1];
            [$unidad->numero, $next->numero] = [$next->numero, $unidad->numero];
            $unidad->save(); $next->save();
        }

        return response()->json(['ok' => true]);
    }

    public function pdf(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $plan->load('unidades');

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $plan->school_year_id ? \App\Models\BoletinConfig::getOrCreate($plan->school_year_id) : null;
        $schoolYear = $plan->schoolYear;
        $competencias = PlanifUnidad::COMPETENCIAS;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.planif_anual_pdf',
            compact('docente', 'asignacion', 'plan', 'si', 'config', 'schoolYear', 'competencias')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($plan->titulo);
        return $pdf->download("planificacion_{$slug}.pdf");
    }

    /**
     * Plantilla CSV en blanco (1 fila de ejemplo) con las columnas de una
     * unidad -- a diferencia de Planes de Clase (documento único con
     * momentos fijos), aquí el contenido es naturalmente tabular (varias
     * unidades por plan), así que sigue el mismo patrón de plantilla+import
     * que ya existe en Asistencia/Calificaciones, en vez de un PDF en
     * blanco para llenar a mano.
     */
    public function plantilla(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $filaEjemplo = [
            1, 'Números racionales', 'P1', 4, '2025-08-25', '2025-09-19',
            'Que el estudiante identifique y opere con números racionales.',
            'Comunicativa;Pensamiento Lógico y Resolución de Problemas',
            'Resuelve operaciones combinadas con números racionales.',
            'Suma, resta, multiplicación y división de fracciones y decimales.',
            'Aprendizaje Colaborativo;Descubrimiento e Indagación',
            'Libro de texto, fichas de trabajo, calculadora.',
            'Prueba escrita + trabajo en equipo.',
        ];

        return $this->generarCsvResponse(self::COLUMNAS_UNIDAD, [$filaEjemplo], 'plantilla_planificacion_anual');
    }

    /** Exporta las unidades YA guardadas del plan a CSV, para editarlas fuera y volver a subirlas. */
    public function exportarUnidades(Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $filas = $plan->unidades()->orderBy('numero')->get()->map(fn (PlanifUnidad $u) => [
            $u->numero, $u->titulo, $u->periodo, $u->semanas,
            optional($u->fecha_inicio)->format('Y-m-d'), optional($u->fecha_fin)->format('Y-m-d'),
            $u->objetivos, implode(';', $u->competencias ?? []), $u->indicadores,
            $u->contenidos, $u->estrategias, $u->recursos, $u->evaluacion,
        ])->all();

        $slug = \Illuminate\Support\Str::slug($plan->titulo);
        return $this->generarCsvResponse(self::COLUMNAS_UNIDAD, $filas, "unidades_{$slug}");
    }

    /**
     * Importa unidades desde CSV/Excel: una fila con `numero` que ya existe
     * en el plan actualiza esa unidad, `numero` nuevo (o vacío) crea una.
     * Mismo patrón que PortalDocenteController::importarCalificaciones().
     */
    public function importarUnidades(Request $request, Asignacion $asignacion, PlanifAnual $plan)
    {
        $docente = $this->getDocente();
        if ($plan->docente_id !== $docente->id) abort(403);

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $rows = $this->leerArchivoImportGenerico($request->file('archivo'));
        $existentesPorNumero = $plan->unidades()->get()->keyBy('numero');
        $siguienteNumero = ($plan->unidades()->max('numero') ?? 0) + 1;

        $importadas = 0;
        foreach ($rows as $row) {
            $numero = trim($row['numero'] ?? '') !== '' ? (int) $row['numero'] : null;
            $titulo = trim($row['titulo'] ?? '');
            if ($titulo === '') continue;

            $competencias = array_values(array_filter(array_map('trim', explode(';', $row['competencias'] ?? ''))));
            $competencias = array_values(array_intersect($competencias, PlanifUnidad::COMPETENCIAS));

            $data = [
                'titulo'       => $titulo,
                'periodo'      => $row['periodo'] ?? null,
                'semanas'      => trim($row['semanas'] ?? '') !== '' ? (int) $row['semanas'] : null,
                'fecha_inicio' => trim($row['fecha_inicio'] ?? '') !== '' ? $row['fecha_inicio'] : null,
                'fecha_fin'    => trim($row['fecha_fin'] ?? '') !== '' ? $row['fecha_fin'] : null,
                'objetivos'    => $row['objetivos'] ?? null,
                'competencias' => $competencias,
                'indicadores'  => $row['indicadores'] ?? null,
                'contenidos'   => $row['contenidos'] ?? null,
                'estrategias'  => $row['estrategias'] ?? null,
                'recursos'     => $row['recursos'] ?? null,
                'evaluacion'   => $row['evaluacion'] ?? null,
            ];

            $unidadExistente = $numero ? $existentesPorNumero->get($numero) : null;
            if ($unidadExistente) {
                $unidadExistente->update($data);
            } else {
                $data['planif_anual_id'] = $plan->id;
                $data['numero'] = $numero ?: $siguienteNumero++;
                PlanifUnidad::create($data);
            }
            $importadas++;
        }

        return redirect()->route('portal.docente.planif-anual.show', [$asignacion, $plan])
            ->with('success', "{$importadas} unidad(es) importada(s) correctamente.");
    }

    private function generarCsvResponse(array $headers, array $rows, string $nombre): \Illuminate\Http\Response
    {
        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '.csv"',
        ]);
    }

    private function leerArchivoImportGenerico($archivo): array
    {
        $ext  = strtolower($archivo->getClientOriginalExtension());
        $rows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $sheet  = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname())
                        ->getActiveSheet()->toArray(null, true, true, false);
            $header = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), ''));
            }
            return $rows;
        }

        $raw    = $this->normalizeToUtf8(file_get_contents($archivo->getPathname()));
        $lines  = array_values(array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")))));
        $delim  = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $header = array_map('strtolower', array_map('trim', str_getcsv($lines[0] ?? '', $delim)));

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') continue;
            $cols   = str_getcsv($line, $delim);
            $rows[] = array_combine($header, array_pad($cols, count($header), ''));
        }

        return $rows;
    }
}
