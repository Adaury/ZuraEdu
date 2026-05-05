<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    public function index()
    {
        $schoolYear = SchoolYear::actual();

        $asignaciones = Asignacion::with(['docente', 'grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('school_year_id', $schoolYear?->id)
            ->get()
            ->sortBy(fn ($a) => $a->grupo->grado->nivel . $a->grupo->seccion->nombre . $a->asignatura->nombre);

        return view('admin.asignaciones.index', compact('asignaciones', 'schoolYear'));
    }

    public function create(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $docentes   = Docente::activos()->orderBy('apellidos')->get();
        $grupos     = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();
        $asignaturas    = Asignatura::activas()->orderBy('nombre')->get();
        $preDocenteId   = $request->integer('docente_id') ?: null;
        $preAsignaturaId = $request->integer('asignatura_id') ?: null;

        return view('admin.asignaciones.create', compact('docentes', 'grupos', 'asignaturas', 'schoolYear', 'preDocenteId', 'preAsignaturaId'));
    }

    public function store(Request $request)
    {
        // Normalizar: aceptar asignatura_id (singular) o asignaturas[] (múltiple)
        if ($request->filled('asignatura_id') && !$request->has('asignaturas')) {
            $request->merge(['asignaturas' => [$request->input('asignatura_id')]]);
        }

        $request->validate([
            'school_year_id'  => 'required|exists:school_years,id',
            'grupo_id'        => 'required|exists:grupos,id',
            'asignaturas'     => 'required|array|min:1',
            'asignaturas.*'   => 'exists:asignaturas,id',
            'docente_id'      => 'nullable|exists:docentes,id',
            'area'            => 'required|in:academica,tecnica',
            'tipo_evaluacion' => 'required|in:componentes,ra,indicadores_logro,competencias',
        ]);

        $schoolYearId   = $request->input('school_year_id');
        $grupoId        = $request->input('grupo_id');
        $docenteId      = $request->input('docente_id') ?: null;
        $area           = $request->input('area');
        $tipoEvaluacion = $request->input('tipo_evaluacion');
        $asignaturas    = $request->input('asignaturas', []);

        // Determinar en qué grupos se creará la asignación
        $grupoIds = [$grupoId];

        if ($request->boolean('todas_secciones')) {
            $gradoId = Grupo::find($grupoId)?->grado_id;
            if ($gradoId) {
                $grupoIds = Grupo::where('school_year_id', $schoolYearId)
                    ->where('grado_id', $gradoId)
                    ->pluck('id')
                    ->toArray();
            }
        }

        $creadas   = 0;
        $omitidas  = 0;
        $gruposAfectados = 0;

        foreach ($grupoIds as $gid) {
            $creadasEnGrupo = 0;
            foreach ($asignaturas as $asigId) {
                // Si ya existe una asignación sin docente (auto-creada como básica),
                // actualizarla con el docente en vez de omitirla o duplicarla.
                $sinDocente = Asignacion::where('school_year_id', $schoolYearId)
                    ->where('grupo_id', $gid)
                    ->where('asignatura_id', $asigId)
                    ->whereNull('docente_id')
                    ->first();

                if ($sinDocente) {
                    $sinDocente->update([
                        'docente_id'      => $docenteId,
                        'area'            => $area,
                        'tipo_evaluacion' => $tipoEvaluacion,
                        'activo'          => true,
                    ]);
                    $creadas++;
                    $creadasEnGrupo++;
                    continue;
                }

                // Si ya existe con cualquier docente, omitir
                $existe = Asignacion::where('school_year_id', $schoolYearId)
                    ->where('grupo_id', $gid)
                    ->where('asignatura_id', $asigId)
                    ->exists();

                if ($existe) { $omitidas++; continue; }

                Asignacion::create([
                    'school_year_id'  => $schoolYearId,
                    'grupo_id'        => $gid,
                    'asignatura_id'   => $asigId,
                    'docente_id'      => $docenteId,
                    'area'            => $area,
                    'tipo_evaluacion' => $tipoEvaluacion,
                    'activo'          => true,
                ]);
                $creadas++;
                $creadasEnGrupo++;
            }
            if ($creadasEnGrupo > 0) $gruposAfectados++;
        }

        if ($gruposAfectados > 1) {
            $msg = "{$creadas} asignatura(s) agregadas a {$gruposAfectados} secciones del mismo grado.";
        } else {
            $msg = $creadas === 1 ? '1 asignatura agregada al grupo.' : "{$creadas} asignaturas agregadas al grupo.";
        }
        if ($omitidas > 0) $msg .= " {$omitidas} ya existían y fueron omitidas.";

        if ($request->filled('redirect_grupo_id')) {
            return redirect()->route('admin.grupos.show', $request->input('redirect_grupo_id'))
                ->with('success', $msg);
        }

        return redirect()->route('admin.asignaciones.index')->with('success', $msg);
    }

    public function asignarDocente(Request $request, Asignacion $asignacion)
    {
        $request->validate([
            'docente_id' => 'required|exists:docentes,id',
        ]);

        $asignacion->update(['docente_id' => $request->docente_id, 'activo' => true]);

        return back()->with('success', 'Docente asignado correctamente a ' . $asignacion->asignatura->nombre . '.');
    }

    public function destroy(Asignacion $asignacione)
    {
        if ($asignacione->calificaciones()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la asignación porque tiene calificaciones registradas.');
        }

        $grupoId = $asignacione->grupo_id;
        $asignacione->delete();

        if (request()->filled('redirect_grupo_id')) {
            return redirect()->route('admin.grupos.show', request()->input('redirect_grupo_id'))
                ->with('success', 'Asignatura eliminada del grupo.');
        }

        return redirect()->route('admin.asignaciones.index')
            ->with('success', 'Asignación eliminada exitosamente.');
    }

    // ── Lista de asignaciones Excel ───────────────────────────────────────
    public function listaExcel()
    {
        $sy = SchoolYear::actual();

        $asignaciones = Asignacion::with(['docente', 'grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('school_year_id', $sy?->id)
            ->get()
            ->sortBy(fn($a) => ($a->grupo->grado->nivel ?? 0) . ($a->grupo->seccion->nombre ?? '') . ($a->asignatura->nombre ?? ''));

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Asignaciones');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'ASIGNACIONES — ' . ($sy?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Asignatura', 'Área', 'Grupo', 'Docente', 'Estado'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:F2')->applyFromArray($hdrStyle);

        foreach ($asignaciones as $i => $a) {
            $row = $i + 3;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $a->asignatura?->nombre ?? '');
            $sheet->setCellValue("C{$row}", ucfirst($a->area ?? ''));
            $sheet->setCellValue("D{$row}", ($a->grupo?->grado?->nombre ?? '') . ' ' . ($a->grupo?->seccion?->nombre ?? ''));
            $sheet->setCellValue("E{$row}", $a->docente?->nombre_completo ?? 'Sin asignar');
            $sheet->setCellValue("F{$row}", $a->activo ? 'Activo' : 'Inactivo');

            if (! $a->docente) {
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('dc2626');
                $sheet->getStyle("E{$row}")->getFont()->setItalic(true);
            }
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'asig_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'asignaciones_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista de asignaciones PDF ─────────────────────────────────────────
    public function listaPdf()
    {
        $sy = \App\Models\SchoolYear::actual();

        $asignaciones = \App\Models\Asignacion::with(['docente', 'grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('school_year_id', $sy?->id)
            ->get()
            ->sortBy(fn($a) => ($a->grupo?->grado?->nivel ?? 0) . ($a->grupo?->seccion?->nombre ?? '') . ($a->asignatura?->nombre ?? ''));

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asignaciones.lista_pdf',
            compact('asignaciones', 'sy', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('asignaciones_' . now()->format('Ymd') . '.pdf');
    }
}
