<?php

namespace App\Http\Controllers\Admin;

use App\Events\DashboardActualizado;
use App\Http\Controllers\Controller;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Promocion;
use App\Models\SchoolYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolYearController extends Controller
{
    public function index()
    {
        $schoolYears = SchoolYear::withCount(['grupos', 'periodos'])
            ->orderByDesc('id')
            ->get();

        return view('admin.school_years.index', compact('schoolYears'));
    }

    public function create()
    {
        return view('admin.school_years.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100|unique:school_years,nombre',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ]);

        if ($request->boolean('activo')) {
            SchoolYear::where('activo', true)->update(['activo' => false]);
        }

        $data['activo'] = $request->boolean('activo');
        $schoolYear = SchoolYear::create($data);
        SchoolYear::flushActualCache();

        // Auto-crear los 4 períodos distribuidos en el año escolar
        $this->crearPeriodosAutomaticos($schoolYear);

        return redirect()->route('admin.school-years.index')
            ->with('success', 'Año escolar creado con 4 períodos automáticamente.');
    }

    public function edit(SchoolYear $schoolYear)
    {
        $schoolYear->loadCount(['grupos', 'periodos']);
        return view('admin.school_years.edit', compact('schoolYear'));
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100|unique:school_years,nombre,' . $schoolYear->id,
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ]);

        if ($request->boolean('activo') && ! $schoolYear->activo) {
            SchoolYear::where('activo', true)->update(['activo' => false]);
        }

        $data['activo'] = $request->boolean('activo');
        $schoolYear->update($data);
        SchoolYear::flushActualCache();

        return redirect()->route('admin.school-years.index')
            ->with('success', 'Año escolar actualizado.');
    }

    private function crearPeriodosAutomaticos(SchoolYear $schoolYear): void
    {
        // Si ya tiene períodos, no sobreescribir
        if ($schoolYear->periodos()->count() > 0) {
            return;
        }

        $inicio = Carbon::parse($schoolYear->fecha_inicio);
        $fin    = Carbon::parse($schoolYear->fecha_fin);
        $total  = $inicio->diffInDays($fin);
        $chunk  = (int) floor($total / 4);

        $periodos = [
            ['numero' => 1, 'nombre' => 'Primer Período'],
            ['numero' => 2, 'nombre' => 'Segundo Período'],
            ['numero' => 3, 'nombre' => 'Tercer Período'],
            ['numero' => 4, 'nombre' => 'Cuarto Período'],
        ];

        foreach ($periodos as $i => $p) {
            $pInicio = $inicio->copy()->addDays($i * $chunk);
            $pFin    = ($i === 3)
                ? $fin->copy()
                : $inicio->copy()->addDays(($i + 1) * $chunk - 1);

            Periodo::create([
                'school_year_id' => $schoolYear->id,
                'numero'         => $p['numero'],
                'nombre'         => $p['nombre'],
                'fecha_inicio'   => $pInicio->toDateString(),
                'fecha_fin'      => $pFin->toDateString(),
                'activo'         => $i === 0, // Solo P1 activo al crear
            ]);
        }
    }

    // ── Matrícula Masiva para Nuevo Año ─────────────────────────────────────

    public function matriculaMasivaIndex(SchoolYear $schoolYear)
    {
        // Año anterior al seleccionado
        $anioAnterior = SchoolYear::where('id', '<', $schoolYear->id)
            ->orderByDesc('id')->first();

        if (!$anioAnterior) {
            return redirect()->route('admin.school-years.index')
                ->with('error', 'No hay año escolar anterior para rematricular estudiantes.');
        }

        // Grupos del año actual (destino)
        $gruposNuevoAnio = Grupo::where('school_year_id', $schoolYear->id)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get();

        // Matriculas activas del año anterior agrupadas por grupo/grado
        $matriculasAnteriores = Matricula::with([
            'estudiante',
            'grupo.grado',
            'grupo.seccion',
        ])
            ->where('school_year_id', $anioAnterior->id)
            ->where('estado', 'activa')
            ->orderBy('numero_orden')
            ->get()
            ->groupBy('grupo_id');

        // Grupos del año anterior
        $gruposAnterior = Grupo::where('school_year_id', $anioAnterior->id)
            ->with(['grado', 'seccion'])
            ->orderBy('grado_id')
            ->get()
            ->keyBy('id');

        // Grados disponibles para mapeo automático
        $grados = Grado::orderBy('orden')->get();

        // Estudiantes ya matriculados en el nuevo año (para no duplicar)
        $yaMatriculados = Matricula::where('school_year_id', $schoolYear->id)
            ->pluck('estudiante_id')
            ->toArray();

        // Contar cuántos ya están y cuántos faltan
        $totalPendientes = Matricula::where('school_year_id', $anioAnterior->id)
            ->where('estado', 'activa')
            ->whereNotIn('estudiante_id', $yaMatriculados)
            ->count();

        return view('admin.school_years.matricula_masiva', compact(
            'schoolYear', 'anioAnterior',
            'matriculasAnteriores', 'gruposAnterior', 'gruposNuevoAnio',
            'grados', 'yaMatriculados', 'totalPendientes'
        ));
    }

    public function matriculaMasivaStore(Request $request, SchoolYear $schoolYear)
    {
        $request->validate([
            'matriculas'         => 'required|array|min:1',
            'matriculas.*.estudiante_id' => 'required|exists:estudiantes,id',
            'matriculas.*.grupo_id'      => 'required|exists:grupos,id',
        ]);

        $yaMatriculados = Matricula::where('school_year_id', $schoolYear->id)
            ->pluck('estudiante_id')->toArray();

        // Pre-cargar conteos actuales por grupo — evita 1 COUNT query por alumno en el loop
        $grupoIds = collect($request->matriculas)->pluck('grupo_id')->unique()->map('intval');
        $ordenPorGrupo = Matricula::whereIn('grupo_id', $grupoIds)
            ->groupBy('grupo_id')
            ->selectRaw('grupo_id, COUNT(*) as cnt')
            ->pluck('cnt', 'grupo_id')
            ->toArray();

        $creados   = 0;
        $omitidos  = 0;
        $hoy       = now()->toDateString();

        DB::transaction(function () use ($request, $schoolYear, $yaMatriculados, &$creados, &$omitidos, $hoy, &$ordenPorGrupo) {
            foreach ($request->matriculas as $item) {
                $estudianteId = (int) $item['estudiante_id'];
                $grupoId      = (int) $item['grupo_id'];

                if (in_array($estudianteId, $yaMatriculados)) {
                    $omitidos++;
                    continue;
                }

                $ordenPorGrupo[$grupoId] = ($ordenPorGrupo[$grupoId] ?? 0) + 1;

                Matricula::create([
                    'school_year_id'  => $schoolYear->id,
                    'estudiante_id'   => $estudianteId,
                    'grupo_id'        => $grupoId,
                    'fecha_matricula' => $hoy,
                    'estado'          => 'activa',
                    'numero_orden'    => $ordenPorGrupo[$grupoId],
                ]);

                $yaMatriculados[] = $estudianteId;
                $creados++;
            }
        });

        $msg = "Se matricularon {$creados} estudiante(s) correctamente.";
        if ($omitidos > 0) $msg .= " ({$omitidos} omitidos por ya estar matriculados.)";

        if ($creados > 0) {
            try {
                DashboardActualizado::dispatch(tenant_id() ?? 0, 'nueva_matricula', [
                    'cantidad' => $creados,
                    'bulk'     => true,
                ]);
            } catch (\Throwable) {}
        }

        return redirect()->route('admin.school-years.index')->with('success', $msg);
    }

    public function destroy(SchoolYear $schoolYear)
    {
        if ($schoolYear->grupos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: tiene grupos asociados.');
        }

        $schoolYear->delete();

        return redirect()->route('admin.school-years.index')
            ->with('success', 'Año escolar eliminado.');
    }

    public function listaExcel()
    {
        $schoolYears = SchoolYear::withCount(['grupos', 'periodos'])
            ->orderByDesc('id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Años Escolares');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Años Escolares — Generado: ' . now()->format('d/m/Y'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Nombre', 'Inicio', 'Fin', 'Grupos', 'Períodos', 'Estado'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        foreach ($schoolYears as $idx => $sy) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $sy->nombre);
            $sheet->setCellValue('C' . $row, $sy->fecha_inicio?->format('d/m/Y') ?? '—');
            $sheet->setCellValue('D' . $row, $sy->fecha_fin?->format('d/m/Y') ?? '—');
            $sheet->setCellValue('E' . $row, $sy->grupos_count);
            $sheet->setCellValue('F' . $row, $sy->periodos_count);
            $sheet->setCellValue('G' . $row, $sy->activo ? 'Activo' : 'Inactivo');
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($sy->activo ? 'dcfce7' : 'f3f4f6');
        }

        foreach (['A'=>5,'B'=>25,'C'=>14,'D'=>14,'E'=>10,'F'=>10,'G'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'anos_escolares.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function listaPdf()
    {
        $schoolYears = SchoolYear::withCount(['grupos', 'periodos'])
            ->orderByDesc('id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.school_years.lista_pdf', compact('schoolYears', 'inst'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('anos_escolares.pdf');
    }
}
