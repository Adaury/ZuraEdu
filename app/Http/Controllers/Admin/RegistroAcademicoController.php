<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\PreMatricula;
use App\Models\SchoolYear;
use App\Models\ConfigInstitucional;
use App\Models\BoletinConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistroAcademicoController extends Controller
{
    public function dashboard()
    {
        $schoolYear = SchoolYear::activo()->first();

        $totalEstudiantes = Estudiante::where('estado', 'activo')->count();

        $matriculasActivas = Matricula::where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        $prePendientes = PreMatricula::pendientes()->count();

        $matriculasEsteMes = Matricula::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $nuevosEstudiantesEsteMes = Estudiante::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $bajasEsteMes = Matricula::whereIn('estado', ['retirada', 'transferida'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $estudiantesRecientes = Estudiante::with([
            'matriculas' => fn($q) => $q->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->with(['grupo.grado', 'grupo.seccion']),
        ])
        ->orderByDesc('created_at')
        ->limit(8)
        ->get();

        $preMatriculas = PreMatricula::pendientes()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $porGrado = Matricula::select('grados.nombre', DB::raw('count(*) as total'))
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->where('matriculas.estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->groupBy('grados.id', 'grados.nombre', 'grados.nivel')
            ->orderBy('grados.nivel')
            ->get();

        $sinGrupo = Estudiante::where('estado', 'activo')
            ->whereDoesntHave('matriculas', fn($q) => $q->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            )
            ->count();

        return view('admin.registro_academico.dashboard', compact(
            'schoolYear', 'totalEstudiantes', 'matriculasActivas',
            'prePendientes', 'matriculasEsteMes', 'nuevosEstudiantesEsteMes',
            'bajasEsteMes', 'estudiantesRecientes', 'preMatriculas',
            'porGrado', 'sinGrupo'
        ));
    }

    // ── Estudiantes sin grupo ─────────────────────────────────────────────

    public function sinGrupo()
    {
        $schoolYear = SchoolYear::activo()->first();

        $estudiantes = Estudiante::where('estado', 'activo')
            ->whereDoesntHave('matriculas', fn($q) => $q->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            )
            ->orderBy('apellidos')
            ->paginate(30);

        return view('admin.registro_academico.sin_grupo', compact('estudiantes', 'schoolYear'));
    }

    // ── Bajas / Retiros ───────────────────────────────────────────────────

    public function bajas(Request $request)
    {
        $schoolYear = SchoolYear::activo()->first();

        $query = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->whereIn('estado', ['retirada', 'transferida'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->when($request->estado, fn($q, $e) => $q->where('estado', $e))
            ->when($request->buscar, fn($q, $s) => $q->whereHas('estudiante',
                fn($e) => $e->where('nombres', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
                    ->orWhere('cedula', 'like', "%{$s}%")
            ))
            ->orderByDesc('fecha_baja')
            ->orderByDesc('updated_at');

        $matriculas = $query->paginate(25)->withQueryString();

        $totalBajas       = Matricula::where('estado', 'retirada')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))->count();
        $totalTransferidas = Matricula::where('estado', 'transferida')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))->count();

        return view('admin.registro_academico.bajas', compact(
            'matriculas', 'schoolYear', 'totalBajas', 'totalTransferidas'
        ));
    }

    public function formBaja(Matricula $matricula)
    {
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion', 'schoolYear']);
        return view('admin.registro_academico.form_baja', compact('matricula'));
    }

    public function registrarBaja(Request $request, Matricula $matricula)
    {
        $data = $request->validate([
            'tipo'        => 'required|in:retirada,transferida',
            'fecha_baja'  => 'required|date|before_or_equal:today',
            'motivo_baja' => 'required|string|max:1000',
            'institucion_traslado' => 'nullable|string|max:255',
        ], [
            'tipo.required'        => 'Selecciona el tipo de baja.',
            'fecha_baja.required'  => 'La fecha de baja es obligatoria.',
            'motivo_baja.required' => 'El motivo es obligatorio.',
        ]);

        $matricula->update([
            'estado'               => $data['tipo'],
            'fecha_baja'           => $data['fecha_baja'],
            'motivo_baja'          => $data['motivo_baja'],
            'institucion_traslado' => $data['institucion_traslado'] ?? null,
        ]);

        $matricula->estudiante->update(['estado' => 'inactivo']);

        return redirect()->route('admin.registro-academico.bajas')
            ->with('success', "Baja registrada para {$matricula->estudiante->nombre_completo}.");
    }

    public function reactivar(Matricula $matricula)
    {
        $matricula->update([
            'estado'               => 'activa',
            'fecha_baja'           => null,
            'motivo_baja'          => null,
            'institucion_traslado' => null,
        ]);

        $matricula->estudiante->update(['estado' => 'activo']);

        return back()->with('success', "Matrícula de {$matricula->estudiante->nombre_completo} reactivada.");
    }

    // ── Traslados entre instituciones ─────────────────────────────────────

    public function traslados(Request $request)
    {
        $schoolYear = SchoolYear::activo()->first();

        $query = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('estado', 'transferida')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->when($request->buscar, fn($q, $s) => $q->whereHas('estudiante',
                fn($e) => $e->where('nombres', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
            ))
            ->orderByDesc('fecha_baja');

        $matriculas = $query->paginate(25)->withQueryString();

        return view('admin.registro_academico.traslados', compact('matriculas', 'schoolYear'));
    }

    public function formTraslado(Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::activo()->first();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->with(['grupo.grado', 'grupo.seccion'])
            ->first();

        if (! $matricula) {
            return redirect()->route('admin.registro-academico.traslados')
                ->with('error', 'Este estudiante no tiene matrícula activa en el año en curso.');
        }

        return view('admin.registro_academico.form_traslado', compact('estudiante', 'matricula'));
    }

    public function registrarTraslado(Request $request, Estudiante $estudiante)
    {
        $data = $request->validate([
            'institucion_traslado' => 'required|string|max:255',
            'fecha_baja'           => 'required|date|before_or_equal:today',
            'motivo_baja'          => 'nullable|string|max:1000',
        ], [
            'institucion_traslado.required' => 'El nombre de la institución destino es obligatorio.',
            'fecha_baja.required'           => 'La fecha del traslado es obligatoria.',
        ]);

        $schoolYear = SchoolYear::activo()->first();

        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->first();

        if (! $matricula) {
            return back()->with('error', 'Sin matrícula activa para procesar el traslado.');
        }

        $matricula->update([
            'estado'               => 'transferida',
            'fecha_baja'           => $data['fecha_baja'],
            'motivo_baja'          => $data['motivo_baja'] ?? 'Traslado a otra institución',
            'institucion_traslado' => $data['institucion_traslado'],
        ]);

        $estudiante->update(['estado' => 'inactivo']);

        return redirect()->route('admin.registro-academico.traslados')
            ->with('success', "Traslado registrado para {$estudiante->nombre_completo}.");
    }

    // ── Reporte Consolidado ───────────────────────────────────────────────

    public function reporteConsolidado(Request $request)
    {
        $schoolYears = SchoolYear::orderByDesc('id')->get();
        $schoolYear  = $request->year_id
            ? SchoolYear::find($request->year_id)
            : SchoolYear::activo()->first();

        $reporte = Matricula::select(
                'grados.id as grado_id',
                'grados.nombre as grado',
                'grados.nivel',
                'secciones.nombre as seccion',
                'grupos.id as grupo_id',
                DB::raw('COUNT(matriculas.id) as total'),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'M' THEN 1 ELSE 0 END) as masculino"),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'F' THEN 1 ELSE 0 END) as femenino"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'activa' THEN 1 ELSE 0 END) as activos"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'retirada' THEN 1 ELSE 0 END) as retirados"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'transferida' THEN 1 ELSE 0 END) as transferidos")
            )
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->join('estudiantes', 'estudiantes.id', '=', 'matriculas.estudiante_id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->groupBy('grados.id', 'grados.nombre', 'grados.nivel', 'secciones.nombre', 'grupos.id')
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->get()
            ->groupBy('grado');

        $totales = [
            'total'        => $reporte->flatten()->sum('total'),
            'masculino'    => $reporte->flatten()->sum('masculino'),
            'femenino'     => $reporte->flatten()->sum('femenino'),
            'activos'      => $reporte->flatten()->sum('activos'),
            'retirados'    => $reporte->flatten()->sum('retirados'),
            'transferidos' => $reporte->flatten()->sum('transferidos'),
        ];

        return view('admin.registro_academico.reporte_consolidado', compact(
            'reporte', 'totales', 'schoolYear', 'schoolYears'
        ));
    }

    public function reporteConsolidadoPdf(Request $request)
    {
        $schoolYear = $request->year_id
            ? SchoolYear::find($request->year_id)
            : SchoolYear::activo()->first();

        $reporte = Matricula::select(
                'grados.nombre as grado',
                'grados.nivel',
                'secciones.nombre as seccion',
                DB::raw('COUNT(matriculas.id) as total'),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'M' THEN 1 ELSE 0 END) as masculino"),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'F' THEN 1 ELSE 0 END) as femenino"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'activa' THEN 1 ELSE 0 END) as activos"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'retirada' THEN 1 ELSE 0 END) as retirados"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'transferida' THEN 1 ELSE 0 END) as transferidos")
            )
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->join('estudiantes', 'estudiantes.id', '=', 'matriculas.estudiante_id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->groupBy('grados.id', 'grados.nombre', 'grados.nivel', 'secciones.nombre', 'grupos.id')
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->get()
            ->groupBy('grado');

        $totales = [
            'total'        => $reporte->flatten()->sum('total'),
            'masculino'    => $reporte->flatten()->sum('masculino'),
            'femenino'     => $reporte->flatten()->sum('femenino'),
            'activos'      => $reporte->flatten()->sum('activos'),
            'retirados'    => $reporte->flatten()->sum('retirados'),
            'transferidos' => $reporte->flatten()->sum('transferidos'),
        ];

        $si  = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir = ConfigInstitucional::get('nombre_director', '');
        $cod = ConfigInstitucional::get('codigo_centro', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.registro_academico.reporte_consolidado_pdf',
            compact('reporte', 'totales', 'schoolYear', 'si', 'dir', 'cod')
        )->setPaper('letter', 'landscape');

        $year = $schoolYear?->nombre ?? now()->year;
        return $pdf->download("reporte_matriculacion_{$year}.pdf");
    }

    public function reporteConsolidadoExcel(Request $request)
    {
        $schoolYear = $request->year_id
            ? SchoolYear::find($request->year_id)
            : SchoolYear::activo()->first();

        $filas = Matricula::select(
                'grados.nombre as grado',
                'secciones.nombre as seccion',
                DB::raw('COUNT(matriculas.id) as total'),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'M' THEN 1 ELSE 0 END) as masculino"),
                DB::raw("SUM(CASE WHEN estudiantes.sexo = 'F' THEN 1 ELSE 0 END) as femenino"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'activa' THEN 1 ELSE 0 END) as activos"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'retirada' THEN 1 ELSE 0 END) as retirados"),
                DB::raw("SUM(CASE WHEN matriculas.estado = 'transferida' THEN 1 ELSE 0 END) as transferidos")
            )
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->join('estudiantes', 'estudiantes.id', '=', 'matriculas.estudiante_id')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->groupBy('grados.id', 'grados.nombre', 'grados.nivel', 'secciones.nombre', 'grupos.id')
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->get();

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Consolidado');

        $hdr = ['font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']]];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', $inst . ' — Reporte de Matrícula — ' . ($schoolYear?->nombre ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['Grado', 'Sección', 'Total', 'Masculino', 'Femenino', 'Activos', 'Retirados', 'Transferidos'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdr);

        foreach ($filas as $i => $f) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $f->grado);
            $ws->setCellValue("B{$row}", $f->seccion);
            $ws->setCellValue("C{$row}", $f->total);
            $ws->setCellValue("D{$row}", $f->masculino);
            $ws->setCellValue("E{$row}", $f->femenino);
            $ws->setCellValue("F{$row}", $f->activos);
            $ws->setCellValue("G{$row}", $f->retirados);
            $ws->setCellValue("H{$row}", $f->transferidos);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        $totalRow = $filas->count() + 4;
        $ws->setCellValue("A{$totalRow}", 'TOTAL');
        $ws->setCellValue("C{$totalRow}", $filas->sum('total'));
        $ws->setCellValue("D{$totalRow}", $filas->sum('masculino'));
        $ws->setCellValue("E{$totalRow}", $filas->sum('femenino'));
        $ws->setCellValue("F{$totalRow}", $filas->sum('activos'));
        $ws->setCellValue("G{$totalRow}", $filas->sum('retirados'));
        $ws->setCellValue("H{$totalRow}", $filas->sum('transferidos'));
        $ws->getStyle("A{$totalRow}:H{$totalRow}")->getFont()->setBold(true);

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rpt_') . '.xlsx';
        $writer->save($tmp);

        $year = $schoolYear?->nombre ?? now()->year;
        return response()->download($tmp, "reporte_matriculacion_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
