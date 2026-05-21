<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarioAcademico;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarioController extends Controller
{
    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();
        $roles      = $user->getRoleNames()->toArray();
        $rolMap     = [
            'Docente'                   => 'docentes',
            'Coordinador Académico'     => 'coordinadores',
            'Coordinador Primer Ciclo'  => 'coordinadores',
            'Coordinador Segundo Ciclo' => 'coordinadores',
            'Personal Administrativo'   => 'administrativos',
        ];
        $aplica = collect($roles)->map(fn($r) => $rolMap[$r] ?? 'todos')->first() ?? 'todos';

        $eventos = CalendarioAcademico::when($schoolYear, fn($q) => $q->delAnio($schoolYear->id))
            ->paraRol($aplica)
            ->where('activo', true)
            ->orderBy('fecha_inicio')
            ->get();

        $proximos = $eventos->filter(fn($e) => $e->fecha_inicio >= now()->toDateString())
            ->take(5);

        return view('admin.calendario.index', compact('eventos', 'proximos', 'schoolYear'));
    }

    public function api(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $user   = Auth::user();
        $roles  = $user->getRoleNames()->toArray();
        $rolMap = [
            'Docente'                   => 'docentes',
            'Coordinador Académico'     => 'coordinadores',
            'Coordinador Primer Ciclo'  => 'coordinadores',
            'Coordinador Segundo Ciclo' => 'coordinadores',
            'Personal Administrativo'   => 'administrativos',
        ];
        $aplica = collect($roles)->map(fn($r) => $rolMap[$r] ?? 'todos')->first() ?? 'todos';

        $eventos = CalendarioAcademico::when($schoolYear, fn($q) => $q->delAnio($schoolYear->id))
            ->paraRol($aplica)
            ->where('activo', true)
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'title'       => $e->titulo,
                'start'       => $e->fecha_inicio->toDateString(),
                'end'         => $e->fecha_fin?->addDay()->toDateString(),
                'color'       => $e->color,
                'extendedProps' => [
                    'id'          => $e->id,
                    'tipo'        => \App\Models\CalendarioAcademico::tiposLabels()[$e->tipo] ?? $e->tipo,
                    'descripcion' => $e->descripcion,
                ],
            ]);

        return response()->json($eventos);
    }

    public function create()
    {
        $schoolYear = SchoolYear::actual();
        $periodos   = $this->getPeriodos($schoolYear);
        $tipos      = CalendarioAcademico::tiposLabels();

        return view('admin.calendario.create', compact('schoolYear', 'periodos', 'tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:' . implode(',', array_keys(CalendarioAcademico::tiposLabels())),
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio'  => 'nullable|date_format:H:i',
            'color'        => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'aplica_a'     => 'required|in:todos,docentes,estudiantes,coordinadores,administrativos',
            'periodo_id'   => 'nullable|exists:periodos,id',
        ]);

        $schoolYear = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
        $data['school_year_id'] = $schoolYear->id;
        $data['creado_por']     = Auth::id();
        $data['activo']         = true;

        CalendarioAcademico::create($data);

        return redirect()->route('admin.calendario.index')
            ->with('success', 'Evento agregado al calendario.');
    }

    public function edit(CalendarioAcademico $evento)
    {
        $schoolYear = SchoolYear::actual();
        $periodos   = $this->getPeriodos($schoolYear);
        $tipos = CalendarioAcademico::tiposLabels();

        return view('admin.calendario.edit', compact('evento', 'schoolYear', 'periodos', 'tipos'));
    }

    public function update(Request $request, CalendarioAcademico $evento)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:' . implode(',', array_keys(CalendarioAcademico::tiposLabels())),
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio'  => 'nullable|date_format:H:i',
            'color'        => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'aplica_a'     => 'required|in:todos,docentes,estudiantes,coordinadores,administrativos',
            'periodo_id'   => 'nullable|exists:periodos,id',
            'activo'       => 'boolean',
        ]);

        $evento->update($data);

        return redirect()->route('admin.calendario.index')
            ->with('success', 'Evento actualizado.');
    }

    public function destroy(CalendarioAcademico $evento)
    {
        $evento->delete();
        return redirect()->route('admin.calendario.index')
            ->with('success', 'Evento eliminado.');
    }

    // ── Exportar calendario PDF ───────────────────────────────────────────
    // ── Exportar calendario Excel ────────────────────────────────────────
    public function excel()
    {
        $schoolYear = SchoolYear::actual();

        $eventos = CalendarioAcademico::when($schoolYear, fn($q) => $q->delAnio($schoolYear->id))
            ->where('activo', true)
            ->orderBy('fecha_inicio')
            ->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Calendario');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'CALENDARIO ACADÉMICO — ' . ($schoolYear?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Título', 'Descripción', 'Tipo', 'Aplica a', 'Inicio', 'Fin'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:G2')->applyFromArray($hdrStyle);

        foreach ($eventos as $i => $ev) {
            $row = $i + 3;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $ev->titulo ?? '');
            $sheet->setCellValue("C{$row}", $ev->descripcion ?? '');
            $sheet->setCellValue("D{$row}", ucfirst($ev->tipo ?? ''));
            $sheet->setCellValue("E{$row}", ucfirst($ev->aplica_a ?? 'todos'));
            $sheet->setCellValue("F{$row}", $ev->fecha_inicio ? \Carbon\Carbon::parse($ev->fecha_inicio)->format('d/m/Y') : '');
            $sheet->setCellValue("G{$row}", $ev->fecha_fin   ? \Carbon\Carbon::parse($ev->fecha_fin)->format('d/m/Y')   : '');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'cal_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'calendario_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function pdf()
    {
        $schoolYear = SchoolYear::actual();

        $eventos = CalendarioAcademico::when($schoolYear, fn($q) => $q->delAnio($schoolYear->id))
            ->where('activo', true)
            ->orderBy('fecha_inicio')
            ->get();

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.calendario.calendario_pdf',
            compact('eventos', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('calendario_academico_' . now()->format('Ymd') . '.pdf');
    }
}
