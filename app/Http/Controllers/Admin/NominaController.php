<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\NominaEmpleado;
use App\Models\Notificacion;
use App\Models\PagoNomina;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NominaController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $buscar    = $request->input('buscar');
        $filtroMes = $request->input('mes', now()->format('Y-m'));
        $estado    = $request->input('estado'); // pagado | pendiente

        $empleados = NominaEmpleado::with(['user', 'pagos' => fn($q) => $q->where('mes', $filtroMes)])
            ->when($buscar, fn($q) => $q->whereHas('user', fn($u) =>
                $u->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
            ))
            ->when($estado === 'pagado', fn($q) => $q->whereHas('pagos',
                fn($p) => $p->where('mes', $filtroMes)->where('pagado', true)
            ))
            ->when($estado === 'pendiente', fn($q) => $q->whereDoesntHave('pagos',
                fn($p) => $p->where('mes', $filtroMes)->where('pagado', true)
            ))
            ->orderByDesc('activo')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        // Stats del mes
        $allPagos = PagoNomina::where('mes', $filtroMes)->get();
        $statsMes = [
            'total_bruto'  => $allPagos->sum('salario_bruto'),
            'total_neto'   => $allPagos->sum('salario_neto'),
            'total_deduc'  => $allPagos->sum('deducciones'),
            'pagados'      => $allPagos->where('pagado', true)->count(),
            'pendientes'   => NominaEmpleado::activos()->count() - $allPagos->where('pagado', true)->count(),
        ];

        $nominaAgg   = NominaEmpleado::activos()->selectRaw('COUNT(*) as total, SUM(salario_base) as suma')->first();
        $totalNomina    = $nominaAgg->suma  ?? 0;
        $totalEmpleados = $nominaAgg->total ?? 0;

        return view('admin.nomina.index', compact(
            'empleados', 'buscar', 'filtroMes', 'estado',
            'totalNomina', 'totalEmpleados', 'statsMes'
        ));
    }

    // ── Show (perfil del empleado + historial) ─────────────────────────────
    public function show(NominaEmpleado $nomina)
    {
        $nomina->load('user');
        $historial = PagoNomina::where('nomina_empleado_id', $nomina->id)
            ->orderByDesc('mes')
            ->limit(24)
            ->get();

        return view('admin.nomina.show', compact('nomina', 'historial'));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create()
    {
        $usuarios = User::whereDoesntHave('nominaEmpleado')
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        return view('admin.nomina.create', compact('usuarios'));
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required|exists:users,id|unique:nomina_empleados,user_id',
            'cargo'          => 'required|string|max:120',
            'cedula'         => 'nullable|string|max:20',
            'cuenta_bancaria'=> 'nullable|string|max:30',
            'banco'          => 'nullable|string|max:60',
            'salario_base'   => 'required|numeric|min:0',
            'tss_porcentaje' => 'nullable|numeric|min:0|max:20',
            'exento_isr'     => 'boolean',
            'tipo_contrato'  => 'required|in:fijo,temporal,hora',
            'horas_semana'   => 'nullable|integer|min:1|max:168',
            'fecha_ingreso'  => 'required|date',
            'activo'         => 'boolean',
            'notas'          => 'nullable|string|max:1000',
        ]);

        $data['activo']      = $request->boolean('activo', true);
        $data['exento_isr']  = $request->boolean('exento_isr');
        $data['tss_porcentaje'] = $data['tss_porcentaje'] ?? 3.04;

        NominaEmpleado::create($data);

        return redirect()->route('admin.nomina.index')
            ->with('success', 'Empleado registrado en nómina correctamente.');
    }

    // ── Edit ───────────────────────────────────────────────────────────────
    public function edit(NominaEmpleado $nomina)
    {
        $nomina->load('user');
        return view('admin.nomina.create', compact('nomina'));
    }

    // ── Update ─────────────────────────────────────────────────────────────
    public function update(Request $request, NominaEmpleado $nomina)
    {
        $data = $request->validate([
            'cargo'          => 'required|string|max:120',
            'cedula'         => 'nullable|string|max:20',
            'cuenta_bancaria'=> 'nullable|string|max:30',
            'banco'          => 'nullable|string|max:60',
            'salario_base'   => 'required|numeric|min:0',
            'tss_porcentaje' => 'nullable|numeric|min:0|max:20',
            'exento_isr'     => 'boolean',
            'tipo_contrato'  => 'required|in:fijo,temporal,hora',
            'horas_semana'   => 'nullable|integer|min:1|max:168',
            'fecha_ingreso'  => 'required|date',
            'activo'         => 'boolean',
            'notas'          => 'nullable|string|max:1000',
        ]);

        $data['activo']      = $request->boolean('activo');
        $data['exento_isr']  = $request->boolean('exento_isr');
        $data['tss_porcentaje'] = $data['tss_porcentaje'] ?? 3.04;

        $nomina->update($data);

        return redirect()->route('admin.nomina.index')
            ->with('success', 'Registro de nómina actualizado correctamente.');
    }

    // ── Destroy ────────────────────────────────────────────────────────────
    public function destroy(NominaEmpleado $nomina)
    {
        $nomina->delete();
        return redirect()->route('admin.nomina.index')
            ->with('success', 'Empleado eliminado de la nómina.');
    }

    // ── Procesar nómina del mes ────────────────────────────────────────────
    public function procesarMes(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $empleados = NominaEmpleado::activos()->with('user')->get();
        $creados   = 0;

        foreach ($empleados as $emp) {
            $tss     = $emp->calcularTSS();
            $isr     = $emp->calcularISR();
            $dedTotal = $tss + $isr;
            $neto    = $emp->salario_base - $dedTotal;

            PagoNomina::firstOrCreate(
                ['nomina_empleado_id' => $emp->id, 'mes' => $mes],
                [
                    'salario_bruto' => $emp->salario_base,
                    'desc_tss'      => $tss,
                    'desc_isr'      => $isr,
                    'desc_otros'    => 0,
                    'deducciones'   => $dedTotal,
                    'salario_neto'  => $neto,
                    'pagado'        => false,
                ]
            );
            $creados++;
        }

        return back()->with('success', "Nómina de {$this->mesLabel($mes)} procesada para {$creados} empleado(s).");
    }

    // ── Procesar pago solo para un empleado ───────────────────────────────
    public function procesarSolo(Request $request, NominaEmpleado $nomina)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $tss      = $nomina->calcularTSS();
        $isr      = $nomina->calcularISR();
        $dedTotal = $tss + $isr;

        PagoNomina::firstOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            [
                'salario_bruto' => $nomina->salario_base,
                'desc_tss'      => $tss,
                'desc_isr'      => $isr,
                'desc_otros'    => 0,
                'deducciones'   => $dedTotal,
                'salario_neto'  => $nomina->salario_base - $dedTotal,
                'pagado'        => false,
            ]
        );

        return back()->with('success', "Pago de {$this->mesLabel($mes)} generado para {$nomina->user->name}.");
    }

    // ── Guardar pago individual (editar deducciones/bonos) ─────────────────
    public function guardarPago(Request $request, NominaEmpleado $nomina)
    {
        $mes = $request->input('mes', now()->format('Y-m'));

        $data = $request->validate([
            'salario_bruto'  => 'required|numeric|min:0',
            'horas_extra'    => 'nullable|numeric|min:0',
            'bonificacion'   => 'nullable|numeric|min:0',
            'otros_ingresos' => 'nullable|numeric|min:0',
            'desc_tss'       => 'nullable|numeric|min:0',
            'desc_isr'       => 'nullable|numeric|min:0',
            'desc_otros'     => 'nullable|numeric|min:0',
            'notas_deducciones' => 'nullable|string|max:500',
        ]);

        $totalIngresos  = $data['salario_bruto']
            + ($data['horas_extra'] ?? 0)
            + ($data['bonificacion'] ?? 0)
            + ($data['otros_ingresos'] ?? 0);
        $totalDeduc = ($data['desc_tss'] ?? 0) + ($data['desc_isr'] ?? 0) + ($data['desc_otros'] ?? 0);
        $neto       = $totalIngresos - $totalDeduc;

        PagoNomina::updateOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            array_merge($data, ['deducciones' => $totalDeduc, 'salario_neto' => $neto])
        );

        return back()->with('success', 'Pago actualizado correctamente.');
    }

    // ── Marcar como pagado ─────────────────────────────────────────────────
    public function marcarPagado(Request $request, NominaEmpleado $nomina)
    {
        $mes = $request->input('mes', now()->format('Y-m'));

        $data = $request->validate([
            'metodo_pago'     => 'nullable|string|max:50',
            'referencia_pago' => 'nullable|string|max:100',
        ]);

        $tss  = $nomina->calcularTSS();
        $isr  = $nomina->calcularISR();
        $pago = PagoNomina::firstOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            [
                'salario_bruto' => $nomina->salario_base,
                'desc_tss'      => $tss,
                'desc_isr'      => $isr,
                'deducciones'   => $tss + $isr,
                'salario_neto'  => $nomina->salario_base - $tss - $isr,
            ]
        );

        $pago->update([
            'pagado'          => true,
            'fecha_pago'      => now()->toDateString(),
            'pagado_por'      => auth()->id(),
            'metodo_pago'     => $data['metodo_pago'] ?? null,
            'referencia_pago' => $data['referencia_pago'] ?? null,
        ]);

        try {
            $nomina->load('user');
            if ($nomina->user_id) {
                $neto     = number_format($pago->salario_neto, 2);
                $mesLabel = \Carbon\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y');
                Notificacion::enviar(
                    $nomina->user_id,
                    'general',
                    '💰 Pago de nómina procesado',
                    "Tu salario correspondiente a {$mesLabel} (neto: {$neto}) ha sido procesado."
                );
            }
        } catch (\Throwable) {}

        return back()->with('success', 'Pago registrado correctamente para '.$nomina->user->name.'.');
    }

    // ── Marcar todos como pagados en el mes ────────────────────────────────
    public function marcarTodosPagados(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $count = PagoNomina::where('mes', $mes)->where('pagado', false)->update([
            'pagado'     => true,
            'fecha_pago' => now()->toDateString(),
            'pagado_por' => auth()->id(),
        ]);
        return back()->with('success', "Se marcaron {$count} pago(s) como realizados.");
    }

    // ── PDF del recibo ─────────────────────────────────────────────────────
    public function reciboPdf(Request $request, NominaEmpleado $nomina)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $nomina->load('user');

        $tss  = $nomina->calcularTSS();
        $isr  = $nomina->calcularISR();
        $pago = PagoNomina::firstOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            [
                'salario_bruto' => $nomina->salario_base,
                'desc_tss'      => $tss,
                'desc_isr'      => $isr,
                'deducciones'   => $tss + $isr,
                'salario_neto'  => $nomina->salario_base - $tss - $isr,
                'pagado'        => false,
            ]
        );

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('nombre_director', '');
        $mon  = 'RD$';

        $pdf = Pdf::loadView('admin.nomina.recibo_pdf', compact('nomina', 'pago', 'inst', 'dir', 'mon'))
            ->setPaper('letter', 'portrait');

        $nombre = 'recibo_nomina_' . Str::slug($nomina->user->name ?? 'empleado') . '_' . $mes . '.pdf';
        return $pdf->download($nombre);
    }

    // ── Excel de nómina del mes ────────────────────────────────────────────
    public function excel(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $empleados = NominaEmpleado::with(['user', 'pagos' => fn($q) => $q->where('mes', $mes)])
            ->activos()->orderBy('id')->get();

        $mesLabel = $this->mesLabel($mes);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Nómina');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:Q1');
        $ws->setCellValue('A1', 'NÓMINA DE EMPLEADOS — ' . strtoupper($mesLabel));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $cols = ['#','Nombre','Cédula','Cargo','Contrato',
                 'Salario Bruto','Horas Extra','Bonificación','Otros Ingresos',
                 'TSS','ISR','Otras Ded.','Total Ded.',
                 'Salario Neto','Estado Pago','Fecha Pago','Método'];
        foreach ($cols as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:Q3')->applyFromArray($hdrStyle);

        foreach ($empleados as $i => $emp) {
            $row = $i + 4;
            $p   = $emp->pagos->first();
            $pagado = $p && $p->pagado;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $emp->user->name ?? '—');
            $ws->setCellValue("C{$row}", $emp->cedula ?? '—');
            $ws->setCellValue("D{$row}", $emp->cargo);
            $ws->setCellValue("E{$row}", $emp->tipo_contrato_label ?? $emp->tipo_contrato);
            $ws->setCellValue("F{$row}", $p?->salario_bruto ?? $emp->salario_base);
            $ws->setCellValue("G{$row}", $p?->horas_extra ?? 0);
            $ws->setCellValue("H{$row}", $p?->bonificacion ?? 0);
            $ws->setCellValue("I{$row}", $p?->otros_ingresos ?? 0);
            $ws->setCellValue("J{$row}", $p?->desc_tss ?? $emp->calcularTSS());
            $ws->setCellValue("K{$row}", $p?->desc_isr ?? $emp->calcularISR());
            $ws->setCellValue("L{$row}", $p?->desc_otros ?? 0);
            $ws->setCellValue("M{$row}", $p?->deducciones ?? ($emp->calcularTSS() + $emp->calcularISR()));
            $ws->setCellValue("N{$row}", $p?->salario_neto ?? ($emp->salario_base - $emp->calcularTSS() - $emp->calcularISR()));
            $ws->setCellValue("O{$row}", $pagado ? 'Pagado' : 'Pendiente');
            $ws->setCellValue("P{$row}", $p?->fecha_pago?->format('d/m/Y') ?? '—');
            $ws->setCellValue("Q{$row}", $p?->metodo_pago ?? '—');

            $bg = $pagado ? 'd1fae5' : 'fef9c3';
            $ws->getStyle("A{$row}:Q{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        }

        // Fila de totales
        $totRow = $empleados->count() + 4;
        $pagos  = $empleados->map(fn($e) => $e->pagos->first())->filter();
        $ws->setCellValue("B{$totRow}", 'TOTALES');
        $ws->setCellValue("F{$totRow}", $pagos->sum('salario_bruto'));
        $ws->setCellValue("J{$totRow}", $pagos->sum('desc_tss'));
        $ws->setCellValue("K{$totRow}", $pagos->sum('desc_isr'));
        $ws->setCellValue("L{$totRow}", $pagos->sum('desc_otros'));
        $ws->setCellValue("M{$totRow}", $pagos->sum('deducciones'));
        $ws->setCellValue("N{$totRow}", $pagos->sum('salario_neto'));
        $ws->getStyle("A{$totRow}:Q{$totRow}")->getFont()->setBold(true);
        $ws->getStyle("A{$totRow}:Q{$totRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('e0e7ff');

        foreach (range('A', 'Q') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'nom_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'nomina_' . $mes . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF de nómina mensual ──────────────────────────────────────────────
    public function nominaPdf(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $empleados = NominaEmpleado::with(['user', 'pagos' => fn($q) => $q->where('mes', $mes)])
            ->activos()->orderBy('id')->get();

        $mesLabel = $this->mesLabel($mes);
        $inst     = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir      = ConfigInstitucional::get('nombre_director', '');

        $totales = [
            'bruto'   => $empleados->sum(fn($e) => $e->pagos->first()?->salario_bruto   ?? $e->salario_base),
            'tss'     => $empleados->sum(fn($e) => $e->pagos->first()?->desc_tss         ?? $e->calcularTSS()),
            'isr'     => $empleados->sum(fn($e) => $e->pagos->first()?->desc_isr         ?? $e->calcularISR()),
            'deduc'   => $empleados->sum(fn($e) => $e->pagos->first()?->deducciones      ?? ($e->calcularTSS() + $e->calcularISR())),
            'neto'    => $empleados->sum(fn($e) => $e->pagos->first()?->salario_neto     ?? ($e->salario_base - $e->calcularTSS() - $e->calcularISR())),
            'pagados' => $empleados->filter(fn($e) => $e->pagos->first()?->pagado)->count(),
        ];

        $pdf = Pdf::loadView('admin.nomina.nomina_pdf',
            compact('empleados', 'mes', 'mesLabel', 'inst', 'dir', 'totales')
        )->setPaper('letter', 'landscape');

        return $pdf->download('nomina_' . $mes . '.pdf');
    }

    // ── Resumen anual (vista HTML) ─────────────────────────────────────────
    public function resumenAnual(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);

        $pagosAnio = PagoNomina::where('mes', 'like', $anio . '-%')->get()->groupBy('mes');

        $meses = collect(range(1, 12))->map(function ($m) use ($anio, $pagosAnio) {
            $mesStr = $anio . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $pagos  = $pagosAnio->get($mesStr, collect());

            return [
                'mes'       => $mesStr,
                'nombre'    => PagoNomina::MESES[str_pad($m, 2, '0', STR_PAD_LEFT)] ?? $m,
                'empleados' => $pagos->count(),
                'bruto'     => $pagos->sum('salario_bruto'),
                'deduc'     => $pagos->sum('deducciones'),
                'neto'      => $pagos->sum('salario_neto'),
                'pagados'   => $pagos->where('pagado', true)->count(),
            ];
        });

        $totalEmpleados = NominaEmpleado::activos()->count();
        $inst           = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        return view('admin.nomina.resumen_anual', compact('meses', 'anio', 'totalEmpleados', 'inst'));
    }

    // ── Resumen anual PDF ──────────────────────────────────────────────────
    public function resumenAnualPdf(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);

        $pagosAnio = PagoNomina::where('mes', 'like', $anio . '-%')->get()->groupBy('mes');

        $meses = collect(range(1, 12))->map(function ($m) use ($anio, $pagosAnio) {
            $mesStr = $anio . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $pagos  = $pagosAnio->get($mesStr, collect());

            return [
                'mes'     => $mesStr,
                'nombre'  => PagoNomina::MESES[str_pad($m, 2, '0', STR_PAD_LEFT)] ?? $m,
                'bruto'   => $pagos->sum('salario_bruto'),
                'tss'     => $pagos->sum('desc_tss'),
                'isr'     => $pagos->sum('desc_isr'),
                'deduc'   => $pagos->sum('deducciones'),
                'neto'    => $pagos->sum('salario_neto'),
                'pagados' => $pagos->where('pagado', true)->count(),
                'total'   => $pagos->count(),
            ];
        });

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('nombre_director', '');

        $pdf = Pdf::loadView('admin.nomina.resumen_anual_pdf',
            compact('meses', 'anio', 'inst', 'dir')
        )->setPaper('letter', 'portrait');

        return $pdf->download('nomina_resumen_' . $anio . '.pdf');
    }

    // ── Reporte CSV (alias legacy) ─────────────────────────────────────────
    public function reporteCsv(Request $request)
    {
        return $this->excel($request);
    }

    // ── Generar recibo (legacy redirect) ──────────────────────────────────
    public function generarRecibo(Request $request, NominaEmpleado $nomina)
    {
        return redirect()->route('admin.nomina.recibo-pdf', [$nomina->id, 'mes' => $request->input('mes', now()->format('Y-m'))]);
    }

    private function mesLabel(string $mes): string
    {
        [$anio, $num] = explode('-', $mes);
        return (PagoNomina::MESES[$num] ?? $num) . ' ' . $anio;
    }
}
