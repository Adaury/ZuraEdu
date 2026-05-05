<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Grupo;
use App\Models\ResultadoAprendizaje;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Asignatura::query();

        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%');
        }

        $asignaturas = $query->orderBy('nombre')->paginate(15)->withQueryString();

        return view('admin.asignaturas.index', compact('asignaturas'));
    }

    public function create()
    {
        $familias = \App\Models\FamiliaProfesional::activas()->orderBy('nombre')->get();
        $areas    = Area::where('activo', true)->orderBy('tipo')->orderBy('nombre')->get();
        return view('admin.asignaturas.create', compact('familias', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'           => 'required|string|max:150',
            'codigo'           => 'nullable|string|max:20',
            'descripcion'      => 'nullable|string',
            'area'             => 'required|in:academica,tecnica',
            'familia_id'       => 'nullable|exists:familias_profesionales,id',
            'horas_semanales'  => 'nullable|integer|min:1|max:20',
            'num_ra'           => 'nullable|integer|min:0|max:10',
            'color'            => 'nullable|string|max:7',
            'activo'           => 'boolean',
        ]);

        $asignatura = Asignatura::create([
            'nombre'          => $request->nombre,
            'codigo'          => $request->codigo,
            'descripcion'     => $request->descripcion,
            'area'            => $request->area,
            'area_id'         => $request->area_id ?: null,
            'familia_id'      => $request->area === 'tecnica' ? $request->familia_id : null,
            'horas_semanales' => $request->horas_semanales ?? 4,
            'num_ra'          => $request->num_ra ?? 0,
            'color'           => $request->color ?? '#1e3a6e',
            'activo'          => $request->boolean('activo'),
            'es_basica'       => $request->boolean('es_basica'),
        ]);

        // Si es básica, asignarla automáticamente a todos los grupos activos del año actual
        $asignadas = 0;
        if ($request->boolean('es_basica')) {
            $schoolYear = SchoolYear::actual();
            if ($schoolYear) {
                $grupos = Grupo::where('school_year_id', $schoolYear->id)->where('activo', true)->get();
                foreach ($grupos as $grupo) {
                    $existe = Asignacion::where('school_year_id', $schoolYear->id)
                        ->where('grupo_id', $grupo->id)
                        ->where('asignatura_id', $asignatura->id)
                        ->exists();
                    if ($existe) continue;
                    Asignacion::create([
                        'school_year_id'  => $schoolYear->id,
                        'grupo_id'        => $grupo->id,
                        'asignatura_id'   => $asignatura->id,
                        'docente_id'      => null,
                        'area'            => $asignatura->area,
                        'tipo_evaluacion' => $asignatura->area === 'tecnica' ? 'ra' : 'indicadores_logro',
                        'activo'          => true,
                    ]);
                    $asignadas++;
                }
            }
        }

        $msg = 'Materia "' . $asignatura->nombre . '" creada correctamente.';
        if ($asignadas > 0) {
            $msg .= " Asignada automáticamente a {$asignadas} grupos.";
        }

        if ($request->input('redirect_to') === 'familias') {
            return redirect()->route('admin.familias.index')->with('success', $msg);
        }

        return redirect()->route('admin.asignaturas.index')->with('success', $msg);
    }

    public function edit(Asignatura $asignatura)
    {
        $ras      = $asignatura->resultadosAprendizaje()->get();
        $familias = \App\Models\FamiliaProfesional::activas()->orderBy('nombre')->get();
        $areas    = Area::where('activo', true)->orderBy('tipo')->orderBy('nombre')->get();
        return view('admin.asignaturas.edit', compact('asignatura', 'ras', 'familias', 'areas'));
    }

    public function guardarRas(Request $request, Asignatura $asignatura)
    {
        $numRa = (int) ($asignatura->num_ra ?? 0);
        if ($numRa < 1) {
            return response()->json(['error' => 'Esta asignatura no tiene RAs configurados.'], 422);
        }

        $request->validate([
            'ras'              => 'required|array',
            'ras.*.numero'     => 'required|integer|min:1|max:10',
            'ras.*.descripcion'=> 'nullable|string|max:255',
            'ras.*.peso'       => 'nullable|numeric|min:0|max:100',
        ]);

        $pesos = collect($request->ras)->pluck('peso')->filter(fn($p) => $p !== null && $p !== '');
        if ($pesos->count() > 0) {
            $suma = $pesos->sum();
            if (abs($suma - 100) > 0.5) {
                return response()->json(['error' => "La suma de los pesos debe ser 100% (actual: {$suma}%)."], 422);
            }
        }

        foreach ($request->ras as $raData) {
            ResultadoAprendizaje::updateOrCreate(
                ['asignatura_id' => $asignatura->id, 'numero' => $raData['numero']],
                [
                    'descripcion' => $raData['descripcion'] ?? null,
                    'peso'        => isset($raData['peso']) && $raData['peso'] !== '' ? $raData['peso'] : null,
                    'activo'      => true,
                ]
            );
        }

        // Remove RAs that exceed num_ra
        ResultadoAprendizaje::where('asignatura_id', $asignatura->id)
            ->where('numero', '>', $numRa)
            ->delete();

        $rasActualizados = $asignatura->resultadosAprendizaje()->get()
            ->mapWithKeys(fn($ra) => [$ra->numero => $ra->peso]);

        return response()->json(['success' => true, 'pesos' => $rasActualizados]);
    }

    public function update(Request $request, Asignatura $asignatura)
    {
        $request->validate([
            'nombre'           => 'required|string|max:150',
            'codigo'           => 'nullable|string|max:20',
            'descripcion'      => 'nullable|string',
            'area'             => 'required|in:academica,tecnica',
            'familia_id'       => 'nullable|exists:familias_profesionales,id',
            'horas_semanales'  => 'nullable|integer|min:1|max:20',
            'num_ra'           => 'nullable|integer|min:0|max:10',
            'color'            => 'nullable|string|max:7',
            'activo'           => 'boolean',
        ]);

        $asignatura->update([
            'nombre'          => $request->nombre,
            'codigo'          => $request->codigo,
            'descripcion'     => $request->descripcion,
            'area'            => $request->area,
            'area_id'         => $request->area_id ?: null,
            'familia_id'      => $request->area === 'tecnica' ? $request->familia_id : null,
            'horas_semanales' => $request->horas_semanales,
            'num_ra'          => $request->num_ra ?? 0,
            'color'           => $request->color ?? $asignatura->color,
            'activo'          => $request->boolean('activo'),
            'es_basica'       => $request->boolean('es_basica'),
        ]);

        return redirect()->route('admin.asignaturas.index')
            ->with('success', 'Asignatura actualizada correctamente.');
    }

    public function destroy(Asignatura $asignatura)
    {
        if ($asignatura->asignaciones()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la asignatura porque tiene asignaciones activas.');
        }

        $asignatura->delete();

        return redirect()->route('admin.asignaturas.index')
            ->with('success', 'Asignatura eliminada correctamente.');
    }

    public function listaPdf(Request $request)
    {
        $asignaturas = Asignatura::with(['familia', 'asignaciones'])
            ->orderBy('nombre')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.asignaturas.lista_pdf',
            compact('asignaturas', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('asignaturas_' . now()->format('Ymd') . '.pdf');
    }

    public function listaExcel(Request $request)
    {
        $asignaturas = Asignatura::with(['familia', 'asignaciones'])
            ->orderBy('nombre')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Asignaturas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Lista de Asignaturas — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Código', 'Área', 'Familia Profesional', 'Horas Semanales', 'Asignaciones'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($asignaturas as $i => $asig) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $asig->nombre);
            $ws->setCellValue("C{$row}", $asig->codigo ?? '—');
            $ws->setCellValue("D{$row}", $asig->area === 'tecnica' ? 'Técnica' : 'Académica');
            $ws->setCellValue("E{$row}", $asig->familia?->nombre ?? '—');
            $ws->setCellValue("F{$row}", $asig->horas_semanales ?? '—');
            $ws->setCellValue("G{$row}", $asig->asignaciones->count());
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f0f6ff');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'asig_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'asignaturas_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
