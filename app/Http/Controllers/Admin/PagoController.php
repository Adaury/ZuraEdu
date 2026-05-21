<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\Setting;
use App\Models\ConfigInstitucional;
use App\Models\ConceptoPago;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PagoController extends Controller
{
    // ── Índice general ────────────────────────────────────────────────────
    public function index(Request $request)
    {
        Pago::sincronizarVencidos();

        $syActual = SchoolYear::actual();
        $grupos   = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $syActual?->id)
            ->activos()->orderBy('id')->get();

        $q = Pago::with(['matricula.estudiante', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->whereHas('matricula', fn ($m) => $m->where('school_year_id', $syActual?->id));

        if ($request->filled('estado')) {
            $q->where('estado', $request->estado);
        }
        if ($request->filled('grupo_id')) {
            $q->whereHas('matricula', fn ($m) => $m->where('grupo_id', $request->grupo_id));
        }
        if ($request->filled('mes')) {
            $q->whereMonth('fecha_vencimiento', $request->mes);
        }
        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $q->whereHas('matricula.estudiante', function ($e) use ($term) {
                $e->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellidos', 'like', "%{$term}%")
                  ->orWhere('numero_matricula', 'like', "%{$term}%");
            });
        }

        $pagos = $q->latest('fecha_vencimiento')->paginate(30)->withQueryString();

        // Resumen
        $resumen = [
            'pendiente' => Pago::whereHas('matricula', fn ($m) => $m->where('school_year_id', $syActual?->id))->where('estado', 'pendiente')->sum('monto'),
            'pagado'    => Pago::whereHas('matricula', fn ($m) => $m->where('school_year_id', $syActual?->id))->where('estado', 'pagado')->sum('monto'),
            'vencido'   => Pago::whereHas('matricula', fn ($m) => $m->where('school_year_id', $syActual?->id))->where('estado', 'vencido')->sum('monto'),
            'total'     => Pago::whereHas('matricula', fn ($m) => $m->where('school_year_id', $syActual?->id))->whereIn('estado', ['pendiente','pagado','vencido'])->count(),
        ];

        // Cobros por mes (últimos 6 meses)
        $cobrosPorMes = Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $syActual?->id))
            ->where('estado', 'pagado')
            ->whereNotNull('fecha_pago')
            ->selectRaw("DATE_FORMAT(fecha_pago, '%Y-%m') as mes, SUM(monto) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->limit(8)
            ->pluck('total', 'mes');

        $conceptos = ConceptoPago::activos()->orderBy('nombre')->get();

        return view('admin.pagos.index', compact('pagos', 'grupos', 'resumen', 'cobrosPorMes', 'conceptos'));
    }

    // ── Estado de cuenta de un estudiante ─────────────────────────────────
    public function porEstudiante(Matricula $matricula)
    {
        Pago::sincronizarVencidos();

        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion', 'becaActiva.beca']);

        $pagos = $matricula->pagos()->latest('fecha_vencimiento')->get();

        $totales = [
            'pagado'    => $pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $pagos->whereIn('estado', ['pendiente', 'vencido'])->sum('monto'),
        ];

        return view('admin.pagos.por_estudiante', compact('matricula', 'pagos', 'totales'));
    }

    // ── Estado de cuenta PDF ──────────────────────────────────────────────
    public function estadoCuentaPdf(Matricula $matricula)
    {
        Pago::sincronizarVencidos();

        $matricula->load([
            'estudiante.representantes',
            'grupo.grado',
            'grupo.seccion',
            'pagos' => fn($q) => $q->orderBy('fecha_vencimiento'),
        ]);

        $sy = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $mon    = Setting::get('payments_currency', 'DOP');

        $totales = [
            'pagado'    => $matricula->pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $matricula->pagos->whereIn('estado', ['pendiente', 'vencido'])->sum('monto'),
            'total'     => $matricula->pagos->sum('monto'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.estado_cuenta_pdf',
            compact('matricula', 'inst', 'config', 'mon', 'totales', 'sy')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($matricula->estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("estado_cuenta_{$slug}.pdf");
    }

    // ── Formulario nuevo pago ─────────────────────────────────────────────
    public function create(Request $request)
    {
        $syActual   = SchoolYear::actual();
        $matriculas = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $syActual?->id)
            ->orderByHas('estudiante', fn ($q) => $q->orderBy('apellidos'))
            ->get();

        $concepto  = Setting::get('payments_concept', 'Cuota escolar mensual');
        $conceptos = ConceptoPago::activos()->orderBy('nombre')->get();

        return view('admin.pagos.create', compact('matriculas', 'concepto', 'conceptos'));
    }

    // ── Guardar nuevo pago ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'matricula_id'      => 'required|exists:matriculas,id',
            'concepto'          => 'required|string|max:255',
            'monto'             => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
            'fecha_pago'        => 'nullable|date',
            'estado'            => 'required|in:pendiente,pagado,vencido,cancelado',
            'metodo_pago'       => 'nullable|in:efectivo,transferencia,tarjeta,stripe,cardnet,otro',
            'referencia'        => 'nullable|string|max:100',
            'notas'             => 'nullable|string|max:500',
        ]);

        $data['registrado_por'] = auth()->id();

        Pago::create($data);

        return redirect()->route('admin.pagos.index')
                         ->with('success', 'Pago registrado correctamente.');
    }

    // ── Editar pago ───────────────────────────────────────────────────────
    public function edit(Pago $pago)
    {
        $pago->load('matricula.estudiante');
        return view('admin.pagos.edit', compact('pago'));
    }

    // ── Actualizar pago ───────────────────────────────────────────────────
    public function update(Request $request, Pago $pago)
    {
        $data = $request->validate([
            'concepto'          => 'required|string|max:255',
            'monto'             => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
            'fecha_pago'        => 'nullable|date',
            'estado'            => 'required|in:pendiente,pagado,vencido,cancelado',
            'metodo_pago'       => 'nullable|in:efectivo,transferencia,tarjeta,stripe,cardnet,otro',
            'referencia'        => 'nullable|string|max:100',
            'notas'             => 'nullable|string|max:500',
        ]);

        $pago->update($data);

        return redirect()->back()->with('success', 'Pago actualizado.');
    }

    // ── Marcar como pagado (quick action) ─────────────────────────────────
    public function marcarPagado(Request $request, Pago $pago)
    {
        $data = $request->validate([
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta,stripe,cardnet,otro',
            'referencia'  => 'nullable|string|max:100',
        ]);

        $pago->update([
            'estado'      => 'pagado',
            'fecha_pago'  => today(),
            'metodo_pago' => $data['metodo_pago'],
            'referencia'  => $data['referencia'] ?? null,
            'registrado_por' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Pago registrado.']);
    }

    // ── Recibo de pago PDF ────────────────────────────────────────────────
    public function reciboPdf(Pago $pago)
    {
        if ($pago->estado !== 'pagado') {
            return back()->with('error', 'Solo se puede generar recibo de pagos confirmados.');
        }

        $pago->load(['matricula.estudiante.representantes', 'matricula.grupo.grado', 'matricula.grupo.seccion', 'registrador']);

        $si  = Setting::get('payments_currency', 'DOP');
        $mon = $si;
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $sy   = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.recibo_pdf',
            compact('pago', 'inst', 'dir', 'mon', 'config')
        )->setPaper([0, 0, 340, 500], 'portrait');   // media carta ~

        $slug = 'recibo_' . $pago->id . '_' . now()->format('Ymd');
        return $pdf->download("{$slug}.pdf");
    }

    // ── Eliminar ──────────────────────────────────────────────────────────
    public function destroy(Pago $pago)
    {
        $pago->delete();
        return redirect()->back()->with('success', 'Registro eliminado.');
    }

    // ── Reporte de deudores ───────────────────────────────────────────────
    public function deudores(Request $request)
    {
        Pago::sincronizarVencidos();

        $syActual = SchoolYear::actual();
        $grupos   = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $syActual?->id)
            ->activos()->orderBy('id')->get();

        // Matriculas con al menos un pago vencido
        $q = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion', 'pagos'])
            ->where('school_year_id', $syActual?->id)
            ->whereHas('pagos', fn($p) => $p->where('estado', 'vencido'));

        if ($request->filled('grupo_id')) {
            $q->where('grupo_id', $request->grupo_id);
        }

        $matriculas = $q->get()->map(function ($mat) {
            $pagosVencidos = $mat->pagos->where('estado', 'vencido');
            $mat->total_vencido   = $pagosVencidos->sum('monto');
            $mat->cuotas_vencidas = $pagosVencidos->count();
            $mat->primera_mora    = $pagosVencidos->min('fecha_vencimiento');
            return $mat;
        })->sortByDesc('total_vencido');

        $totalDeuda = $matriculas->sum('total_vencido');

        return view('admin.pagos.deudores', compact('matriculas', 'grupos', 'totalDeuda'));
    }

    // ── Generación masiva de cuotas ───────────────────────────────────────
    public function generarCuotas(Request $request)
    {
        $data = $request->validate([
            'concepto'          => 'required|string|max:255',
            'monto'             => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
            'grupo_id'          => 'nullable|exists:grupos,id',
        ]);

        $syActual = SchoolYear::actual();

        $q = Matricula::where('school_year_id', $syActual?->id)
                      ->where('estado', 'activa');

        if (!empty($data['grupo_id'])) {
            $q->where('grupo_id', $data['grupo_id']);
        }

        $matriculas = $q->get();
        $creados    = 0;

        foreach ($matriculas as $matricula) {
            // Evitar duplicar el mismo concepto/vencimiento por estudiante
            $existe = Pago::where('matricula_id', $matricula->id)
                          ->where('concepto', $data['concepto'])
                          ->where('fecha_vencimiento', $data['fecha_vencimiento'])
                          ->exists();

            if (!$existe) {
                // ── Aplicar beca activa si la tiene ──────────────────────
                $montoFinal = (float) $data['monto'];
                $becaActiva = \App\Models\BecaEstudiante::with('beca')
                    ->where('matricula_id', $matricula->id)
                    ->where('activo', true)
                    ->where(fn($q) =>
                        $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', today())
                    )
                    ->latest()
                    ->first();

                $notaBeca = null;
                if ($becaActiva && $becaActiva->beca) {
                    $descuento   = $becaActiva->beca->calcularDescuento($montoFinal);
                    $montoFinal  = max(0, $montoFinal - $descuento);
                    $notaBeca    = "Beca aplicada: {$becaActiva->beca->nombre} (descuento: " .
                                   ($becaActiva->beca->tipo === 'porcentaje'
                                       ? $becaActiva->beca->valor . '%'
                                       : 'RD$ ' . number_format($descuento, 2)) . ')';
                }
                // ─────────────────────────────────────────────────────────

                Pago::create([
                    'matricula_id'      => $matricula->id,
                    'concepto'          => $data['concepto'],
                    'monto'             => $montoFinal,
                    'fecha_vencimiento' => $data['fecha_vencimiento'],
                    'estado'            => 'pendiente',
                    'notas'             => $notaBeca,
                    'registrado_por'    => auth()->id(),
                ]);
                $creados++;
            }
        }

        return redirect()->route('admin.pagos.index')
                         ->with('success', "{$creados} cuota(s) generada(s) correctamente.");
    }

    // ── Configuración de pagos ────────────────────────────────────────────
    public function configIndex()
    {
        $config = [
            'payments_gateway'              => Setting::get('payments_gateway', 'stripe'),
            'payments_stripe_pk'            => Setting::get('payments_stripe_pk', ''),
            'payments_stripe_sk'            => Setting::get('payments_stripe_sk', ''),
            'payments_cardnet_merchant_id'  => Setting::get('payments_cardnet_merchant_id', ''),
            'payments_cardnet_terminal_id'  => Setting::get('payments_cardnet_terminal_id', '00000001'),
            'payments_cardnet_secret_key'   => Setting::get('payments_cardnet_secret_key', ''),
            'payments_cardnet_sandbox'      => Setting::get('payments_cardnet_sandbox', '1'),
            'payments_currency'             => Setting::get('payments_currency', 'DOP'),
            'payments_concept'              => Setting::get('payments_concept', 'Cuota escolar mensual'),
            'module_payments'               => Setting::get('module_payments', '0'),
        ];

        return view('admin.pagos.config', compact('config'));
    }

    // ── Guardar configuración ─────────────────────────────────────────────
    public function configUpdate(Request $request)
    {
        $data = $request->validate([
            'payments_gateway'             => 'required|in:stripe,cardnet,manual',
            'payments_stripe_pk'           => 'nullable|string|max:255',
            'payments_stripe_sk'           => 'nullable|string|max:255',
            'payments_cardnet_merchant_id' => 'nullable|string|max:50',
            'payments_cardnet_terminal_id' => 'nullable|string|max:20',
            'payments_cardnet_secret_key'  => 'nullable|string|max:255',
            'payments_cardnet_sandbox'     => 'nullable|boolean',
            'payments_currency'            => 'required|string|max:10',
            'payments_concept'             => 'required|string|max:255',
            'module_payments'              => 'nullable|boolean',
        ]);

        Setting::setMany([
            'payments_gateway'             => $data['payments_gateway'],
            'payments_stripe_pk'           => $data['payments_stripe_pk'] ?? '',
            'payments_stripe_sk'           => $data['payments_stripe_sk'] ?? '',
            'payments_cardnet_merchant_id' => $data['payments_cardnet_merchant_id'] ?? '',
            'payments_cardnet_terminal_id' => $data['payments_cardnet_terminal_id'] ?? '00000001',
            'payments_cardnet_secret_key'  => $data['payments_cardnet_secret_key'] ?? '',
            'payments_cardnet_sandbox'     => $request->boolean('payments_cardnet_sandbox') ? '1' : '0',
            'payments_currency'            => $data['payments_currency'],
            'payments_concept'             => $data['payments_concept'],
            'module_payments'              => $request->boolean('module_payments') ? '1' : '0',
        ]);

        // Sincronizar también en config_institucional
        ConfigInstitucional::set('modulo_pagos_activo', $request->boolean('module_payments') ? '1' : '0');

        return redirect()->back()->with('success', 'Configuración de pagos guardada.');
    }

    // ── Deudores PDF ──────────────────────────────────────────────────────
    public function deudoresPdf(Request $request)
    {
        Pago::sincronizarVencidos();
        $syActual   = SchoolYear::actual();
        $matriculas = $this->getDeudoresQuery($request, $syActual)->get()->map(fn($m) => $this->enrichDeudor($m));
        $totalDeuda = $matriculas->sum('total_vencido');
        $schoolYear = $syActual;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pagos.deudores_pdf', compact('matriculas','totalDeuda','schoolYear'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('deudores_' . now()->format('Ymd') . '.pdf');
    }

    // ── Deudores Excel ────────────────────────────────────────────────────
    public function deudoresExcel(Request $request)
    {
        Pago::sincronizarVencidos();
        $syActual   = SchoolYear::actual();
        $matriculas = $this->getDeudoresQuery($request, $syActual)->get()->map(fn($m) => $this->enrichDeudor($m));

        $ss   = new Spreadsheet();
        $ws   = $ss->getActiveSheet();
        $ws->setTitle('Deudores');

        // Header
        $headers = ['#','Matrícula','Apellidos','Nombre','Grupo','Cuotas Vencidas','Deuda Total (RD$)','Primera Mora'];
        foreach ($headers as $col => $hdr) {
            $cell = chr(65 + $col) . '1';
            $ws->setCellValue($cell, $hdr);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e40af');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($matriculas->values() as $i => $mat) {
            $row = $i + 2;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $mat->estudiante->numero_matricula ?? '—');
            $ws->setCellValue("C{$row}", $mat->estudiante->apellidos ?? '—');
            $ws->setCellValue("D{$row}", $mat->estudiante->nombres ?? '—');
            $ws->setCellValue("E{$row}", ($mat->grupo->grado->nombre ?? '') . ' ' . ($mat->grupo->seccion->nombre ?? ''));
            $ws->setCellValue("F{$row}", $mat->cuotas_vencidas);
            $ws->setCellValue("G{$row}", number_format($mat->total_vencido, 2));
            $ws->setCellValue("H{$row}", $mat->primera_mora ? Carbon::parse($mat->primera_mora)->format('d/m/Y') : '—');
        }

        foreach (range('A','H') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($ss);
        $filename = 'deudores_' . now()->format('Ymd') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Excel general de pagos ────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        Pago::sincronizarVencidos();
        $sy = SchoolYear::actual();
        $mon = Setting::get('payments_currency', 'DOP');

        $q = Pago::with(['matricula.estudiante', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $sy?->id));

        if ($request->filled('estado'))   $q->where('estado', $request->estado);
        if ($request->filled('grupo_id')) $q->whereHas('matricula', fn($m) => $m->where('grupo_id', $request->grupo_id));
        if ($request->filled('mes'))      $q->whereMonth('fecha_vencimiento', $request->mes);

        $pagos = $q->orderBy('fecha_vencimiento')->get();

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Pagos');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'REGISTRO DE PAGOS — ' . ($sy?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Estudiante', 'Grupo', 'Concepto', 'Monto', 'Vencimiento', 'F. Pago', 'Estado', 'Recibo', 'Moneda'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:J2')->applyFromArray($hdrStyle);

        $colorEstado = ['pagado' => 'd1fae5', 'pendiente' => 'fef3c7', 'vencido' => 'fee2e2'];

        foreach ($pagos as $i => $pago) {
            $row = $i + 3;
            $est = $pago->matricula?->estudiante;
            $grp = $pago->matricula?->grupo;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $est ? trim(($est->apellidos ?? '') . ', ' . ($est->nombres ?? '')) : '');
            $sheet->setCellValue("C{$row}", $grp ? ($grp->grado->nombre ?? '') . ' ' . ($grp->seccion->nombre ?? '') : '');
            $sheet->setCellValue("D{$row}", $pago->concepto ?? '');
            $sheet->setCellValue("E{$row}", $pago->monto ?? 0);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue("F{$row}", $pago->fecha_vencimiento ? Carbon::parse($pago->fecha_vencimiento)->format('d/m/Y') : '');
            $sheet->setCellValue("G{$row}", $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '');
            $sheet->setCellValue("H{$row}", ucfirst($pago->estado ?? ''));
            $sheet->setCellValue("I{$row}", $pago->numero_recibo ?? '');
            $sheet->setCellValue("J{$row}", $mon);

            $bg = $colorEstado[$pago->estado] ?? 'ffffff';
            $sheet->getStyle("A{$row}:J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
        }

        foreach (range('A', 'J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'pagos_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'pagos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF de lista general de pagos ────────────────────────────────────
    public function listaPdf(Request $request)
    {
        Pago::sincronizarVencidos();
        $sy  = SchoolYear::actual();
        $mon = Setting::get('payments_currency', 'DOP');

        $q = Pago::with(['matricula.estudiante', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $sy?->id));

        if ($request->filled('estado'))   $q->where('estado', $request->estado);
        if ($request->filled('grupo_id')) $q->whereHas('matricula', fn($m) => $m->where('grupo_id', $request->grupo_id));
        if ($request->filled('mes'))      $q->whereMonth('fecha_vencimiento', $request->mes);

        $pagos  = $q->orderBy('fecha_vencimiento')->get();
        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $totales = [
            'pagado'    => $pagos->where('estado', 'pagado')->sum('monto'),
            'pendiente' => $pagos->where('estado', 'pendiente')->sum('monto'),
            'vencido'   => $pagos->where('estado', 'vencido')->sum('monto'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.lista_pdf',
            compact('pagos', 'sy', 'inst', 'config', 'mon', 'totales')
        )->setPaper('letter', 'landscape');

        return $pdf->download('pagos_' . now()->format('Ymd') . '.pdf');
    }

    // ── Resumen mensual de pagos PDF ─────────────────────────────────────
    public function resumenMensualPdf(Request $request)
    {
        $sy  = SchoolYear::actual();
        $mon = Setting::get('payments_currency', 'DOP');

        $meses = collect(range(1, 12))->map(function ($m) use ($sy, $mon) {
            $base = Pago::whereHas('matricula', fn($q) => $q->where('school_year_id', $sy?->id))
                ->whereMonth('fecha_vencimiento', $m);

            return [
                'mes'        => $m,
                'nombre'     => \Carbon\Carbon::createFromDate(null, $m, 1)->locale('es')->monthName,
                'pagado'     => (clone $base)->where('estado', 'pagado')->sum('monto'),
                'pendiente'  => (clone $base)->where('estado', 'pendiente')->sum('monto'),
                'vencido'    => (clone $base)->where('estado', 'vencido')->sum('monto'),
                'total_reg'  => (clone $base)->count(),
            ];
        })->filter(fn($m) => $m['total_reg'] > 0);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pagos.resumen_mensual_pdf',
            compact('meses', 'sy', 'inst', 'mon')
        )->setPaper('letter', 'portrait');

        return $pdf->download('Pagos_Mensual_' . ($sy?->nombre ?? date('Y')) . '.pdf');
    }

    // ── Resumen mensual de pagos Excel ───────────────────────────────────
    public function resumenMensualExcel(Request $request)
    {
        $sy  = SchoolYear::actual();
        $mon = Setting::get('payments_currency', 'DOP');

        $meses = collect(range(1, 12))->map(function ($m) use ($sy, $mon) {
            $base = Pago::whereHas('matricula', fn($q) => $q->where('school_year_id', $sy?->id))
                ->whereMonth('fecha_vencimiento', $m);

            return [
                'mes'       => $m,
                'nombre'    => Carbon::createFromDate(null, $m, 1)->locale('es')->monthName,
                'pagado'    => (clone $base)->where('estado', 'pagado')->sum('monto'),
                'pendiente' => (clone $base)->where('estado', 'pendiente')->sum('monto'),
                'vencido'   => (clone $base)->where('estado', 'vencido')->sum('monto'),
                'total_reg' => (clone $base)->count(),
            ];
        })->filter(fn($m) => $m['total_reg'] > 0)->values();

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Mensual');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($inst));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Resumen Mensual de Pagos — ' . ($sy?->nombre ?? date('Y')) . ' (' . $mon . ')');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Mes', 'Pagado', 'Pendiente', 'Vencido', 'Total Reg.'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
            $sheet->getStyle($col . '4')->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e3a6e');
            $col++;
        }

        $totPagado = $totPendiente = $totVencido = $totReg = 0;
        foreach ($meses as $idx => $mes) {
            $row = $idx + 5;
            $bg = ($idx % 2 === 0) ? 'f0f4ff' : 'ffffff';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, ucfirst($mes['nombre']));
            $sheet->setCellValue('C' . $row, number_format($mes['pagado'], 2));
            $sheet->setCellValue('D' . $row, number_format($mes['pendiente'], 2));
            $sheet->setCellValue('E' . $row, number_format($mes['vencido'], 2));
            $sheet->setCellValue('F' . $row, $mes['total_reg']);
            $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bg);
            if ($mes['vencido'] > 0) {
                $sheet->getStyle('E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fee2e2');
            }
            $totPagado    += $mes['pagado'];
            $totPendiente += $mes['pendiente'];
            $totVencido   += $mes['vencido'];
            $totReg       += $mes['total_reg'];
        }

        $totRow = $meses->count() + 5;
        $sheet->setCellValue('A' . $totRow, '');
        $sheet->setCellValue('B' . $totRow, 'TOTAL');
        $sheet->setCellValue('C' . $totRow, number_format($totPagado, 2));
        $sheet->setCellValue('D' . $totRow, number_format($totPendiente, 2));
        $sheet->setCellValue('E' . $totRow, number_format($totVencido, 2));
        $sheet->setCellValue('F' . $totRow, $totReg);
        $sheet->getStyle("A{$totRow}:F{$totRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totRow}:F{$totRow}")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('e0e7ff');

        foreach (['A'=>5,'B'=>16,'C'=>16,'D'=>16,'E'=>16,'F'=>12] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Pagos_Mensual_' . ($sy?->nombre ?? date('Y')) . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Recordatorio de pagos vencidos ────────────────────────────────────
    public function recordatorio(Request $request)
    {
        Pago::sincronizarVencidos();

        $syActual   = SchoolYear::actual();
        $matriculas = $this->getDeudoresQuery($request, $syActual)->get();

        if ($matriculas->isEmpty()) {
            return redirect()->back()->with('info', 'No hay deudores para notificar.');
        }

        $enviados = 0;
        $mon      = Setting::get('payments_currency', 'DOP');

        foreach ($matriculas as $matricula) {
            $pagosVencidos = $matricula->pagos->where('estado', 'vencido');
            $total         = $pagosVencidos->sum('monto');
            $cuotas        = $pagosVencidos->count();

            $nombreEst = $matricula->estudiante->nombre_completo ?? $matricula->estudiante->nombres ?? '';
            $msg       = "Recordatorio: {$nombreEst} tiene {$cuotas} cuota(s) vencida(s) por {$mon} " . number_format($total, 2) . ". Por favor regularice su situación.";

            // Notificación portal a representantes
            foreach ($matricula->estudiante->representantes as $rep) {
                if ($rep->user_id) {
                    \App\Models\Notificacion::enviarA(
                        [$rep->user_id],
                        'pago',
                        'Recordatorio de Pago Vencido',
                        $msg,
                        ['matricula_id' => $matricula->id]
                    );
                    $enviados++;
                }
            }

            // WhatsApp si está configurado
            try {
                foreach ($matricula->estudiante->representantes as $rep) {
                    if (!empty($rep->celular)) {
                        app(\App\Services\WhatsAppService::class)->sendMessage($rep->celular, $msg);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return redirect()->back()->with('success', "Recordatorio enviado a {$enviados} representante(s).");
    }

    // ── Helpers privados ──────────────────────────────────────────────────
    private function getDeudoresQuery(Request $request, $syActual)
    {
        $q = Matricula::with(['estudiante.representantes', 'grupo.grado', 'grupo.seccion', 'pagos'])
            ->where('school_year_id', $syActual?->id)
            ->whereHas('pagos', fn($p) => $p->where('estado', 'vencido'));

        if ($request->filled('grupo_id')) {
            $q->where('grupo_id', $request->grupo_id);
        }

        return $q;
    }

    private function enrichDeudor(Matricula $mat): Matricula
    {
        $pagosVencidos        = $mat->pagos->where('estado', 'vencido');
        $mat->total_vencido   = $pagosVencidos->sum('monto');
        $mat->cuotas_vencidas = $pagosVencidos->count();
        $mat->primera_mora    = $pagosVencidos->min('fecha_vencimiento');
        return $mat;
    }

    // ── Conceptos de Pago ────────────────────────────────────────────────────
    public function conceptos()
    {
        $conceptos = ConceptoPago::orderBy('activo', 'desc')->orderBy('nombre')->get();
        return view('admin.pagos.conceptos', compact('conceptos'));
    }

    public function storeConcepto(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'monto_defecto' => 'nullable|numeric|min:0',
            'tipo'          => 'required|in:mensualidad,inscripcion,otro',
            'descripcion'   => 'nullable|string|max:500',
        ]);
        $data['activo'] = true;
        ConceptoPago::create($data);
        return back()->with('success', 'Concepto creado correctamente.');
    }

    public function updateConcepto(Request $request, ConceptoPago $conceptoPago)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'monto_defecto' => 'nullable|numeric|min:0',
            'tipo'          => 'required|in:mensualidad,inscripcion,otro',
            'descripcion'   => 'nullable|string|max:500',
            'activo'        => 'nullable|boolean',
        ]);
        $data['activo'] = $request->boolean('activo');
        $conceptoPago->update($data);
        return back()->with('success', 'Concepto actualizado.');
    }

    public function destroyConcepto(ConceptoPago $conceptoPago)
    {
        $conceptoPago->delete();
        return back()->with('success', 'Concepto eliminado.');
    }
}
