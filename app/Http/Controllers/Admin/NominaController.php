<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\NominaEmpleado;
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

        $totalNomina    = NominaEmpleado::activos()->sum('salario_base');
        $totalEmpleados = NominaEmpleado::activos()->count();

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

        $pago = PagoNomina::firstOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            [
                'salario_bruto' => $nomina->salario_base,
                'desc_tss'      => $nomina->calcularTSS(),
                'desc_isr'      => $nomina->calcularISR(),
                'deducciones'   => $nomina->calcularTSS() + $nomina->calcularISR(),
                'salario_neto'  => $nomina->salario_base - $nomina->calcularTSS() - $nomina->calcularISR(),
            ]
        );

        $pago->update([
            'pagado'          => true,
            'fecha_pago'      => now()->toDateString(),
            'pagado_por'      => auth()->id(),
            'metodo_pago'     => $data['metodo_pago'] ?? null,
            'referencia_pago' => $data['referencia_pago'] ?? null,
        ]);

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

        $pago = PagoNomina::firstOrCreate(
            ['nomina_empleado_id' => $nomina->id, 'mes' => $mes],
            [
                'salario_bruto' => $nomina->salario_base,
                'desc_tss'      => $nomina->calcularTSS(),
                'desc_isr'      => $nomina->calcularISR(),
                'deducciones'   => $nomina->calcularTSS() + $nomina->calcularISR(),
                'salario_neto'  => $nomina->salario_base - $nomina->calcularTSS() - $nomina->calcularISR(),
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

        $mesLabel  = $this->mesLabel($mes);
        $filename  = 'nomina_' . $mes . '.csv';
        $headers   = ['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>"attachment; filename=\"{$filename}\""];

        $callback = function () use ($empleados, $mes, $mesLabel) {
            $fh = fopen('php://output', 'w');
            fputs($fh, "\xEF\xBB\xBF");
            fputcsv($fh, ['NÓMINA DE EMPLEADOS — ' . strtoupper($mesLabel)]);
            fputcsv($fh, []);
            fputcsv($fh, ['#','Nombre','Cédula','Cargo','Contrato',
                          'Salario Bruto','Horas Extra','Bonificación','Otros Ingresos',
                          'TSS','ISR','Otras Deducciones','Total Deducciones',
                          'Salario Neto','Estado Pago','Fecha Pago','Método']);
            foreach ($empleados as $i => $emp) {
                $p = $emp->pagos->first();
                fputcsv($fh, [
                    $i+1,
                    $emp->user->name ?? '—',
                    $emp->cedula ?? '—',
                    $emp->cargo,
                    $emp->tipo_contrato_label,
                    number_format($p?->salario_bruto ?? $emp->salario_base, 2),
                    number_format($p?->horas_extra ?? 0, 2),
                    number_format($p?->bonificacion ?? 0, 2),
                    number_format($p?->otros_ingresos ?? 0, 2),
                    number_format($p?->desc_tss ?? $emp->calcularTSS(), 2),
                    number_format($p?->desc_isr ?? $emp->calcularISR(), 2),
                    number_format($p?->desc_otros ?? 0, 2),
                    number_format($p?->deducciones ?? ($emp->calcularTSS()+$emp->calcularISR()), 2),
                    number_format($p?->salario_neto ?? ($emp->salario_base-$emp->calcularTSS()-$emp->calcularISR()), 2),
                    $p && $p->pagado ? 'Pagado' : 'Pendiente',
                    $p?->fecha_pago?->format('d/m/Y') ?? '—',
                    $p?->metodo_pago ?? '—',
                ]);
            }
            // Totales
            fputcsv($fh, []);
            $pagos = $empleados->map(fn($e) => $e->pagos->first())->filter();
            fputcsv($fh, ['','TOTALES','','','',
                number_format($pagos->sum('salario_bruto'),2),'','','',
                number_format($pagos->sum('desc_tss'),2),
                number_format($pagos->sum('desc_isr'),2),
                number_format($pagos->sum('desc_otros'),2),
                number_format($pagos->sum('deducciones'),2),
                number_format($pagos->sum('salario_neto'),2)]);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
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
