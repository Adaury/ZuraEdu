<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:8.5pt; color:#1e293b; }

.header { background:linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); color:#fff; padding:12px 18px 10px; margin-bottom:12px; }
.header h1 { font-size:13pt; font-weight:700; margin-bottom:2px; }
.header p  { font-size:7.5pt; opacity:.85; }

.chips { display:flex; gap:10px; margin-bottom:12px; }
.chip  { flex:1; text-align:center; border:1px solid #e5e7eb; border-radius:6px; padding:6px 4px; }
.chip .val { font-size:10pt; font-weight:800; }
.chip .lbl { font-size:6.5pt; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }

table { width:100%; border-collapse:collapse; font-size:7.5pt; }
thead th {
    background:#0f766e; color:#fff;
    font-size:6.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
    padding:5px 6px; text-align:left;
}
tbody td { padding:4px 6px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody tr:nth-child(even) td { background:#f8fafc; }
tfoot td { background:#F0FDF4; font-weight:800; border-top:2px solid #86EFAC; padding:5px 6px; }

.badge { display:inline-block; padding:2px 6px; border-radius:10px; font-size:6.5pt; font-weight:700; }
.badge-pagado  { background:#d1fae5; color:#065f46; }
.badge-pendiente { background:#fef3c7; color:#92400e; }
.badge-sinprocesar { background:#f1f5f9; color:#64748b; }

.footer { margin-top:16px; display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid #e5e7eb; font-size:7pt; color:#9ca3af; }
.sigs { display:flex; justify-content:space-around; margin-top:30px; }
.sig-block { text-align:center; width:160px; }
.sig-line { border-top:1px solid #374151; padding-top:3px; font-size:7.5pt; font-weight:700; }
.sig-role { font-size:6.5pt; color:#6b7280; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Nómina de Empleados</h1>
    <p>Período: {{ $mesLabel }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; {{ $empleados->count() }} empleados activos</p>
</div>

@php
$pagados = $empleados->filter(fn($e) => $e->pagos->first()?->pagado)->count();
@endphp

<div class="chips">
    <div class="chip" style="border-color:#86efac;">
        <div class="val" style="color:#065f46;">RD$ {{ number_format($totales['bruto'],2) }}</div>
        <div class="lbl">Total Bruto</div>
    </div>
    <div class="chip" style="border-color:#fca5a5;">
        <div class="val" style="color:#991b1b;">-RD$ {{ number_format($totales['deduc'],2) }}</div>
        <div class="lbl">Deducciones</div>
    </div>
    <div class="chip" style="border-color:#93c5fd;">
        <div class="val" style="color:#1d4ed8;">RD$ {{ number_format($totales['neto'],2) }}</div>
        <div class="lbl">Total Neto</div>
    </div>
    <div class="chip" style="border-color:#86efac;">
        <div class="val" style="color:#16a34a;">{{ $pagados }} / {{ $empleados->count() }}</div>
        <div class="lbl">Pagados</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th>Empleado</th>
            <th>Cédula</th>
            <th>Cargo</th>
            <th>Contrato</th>
            <th style="text-align:right;">Bruto</th>
            <th style="text-align:right;">TSS</th>
            <th style="text-align:right;">ISR</th>
            <th style="text-align:right;">Otros</th>
            <th style="text-align:right;">Neto</th>
            <th style="text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($empleados as $i => $emp)
    @php
        $p     = $emp->pagos->first();
        $bruto = $p?->salario_bruto   ?? $emp->salario_base;
        $tss   = $p?->desc_tss        ?? $emp->calcularTSS();
        $isr   = $p?->desc_isr        ?? $emp->calcularISR();
        $otros = $p?->desc_otros      ?? 0;
        $neto  = $p?->salario_neto    ?? ($emp->salario_base - $tss - $isr);
    @endphp
    <tr>
        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $emp->user->name ?? '—' }}</td>
        <td style="font-family:monospace;font-size:7pt;color:#6b7280;">{{ $emp->cedula ?? '—' }}</td>
        <td style="font-size:7pt;">{{ $emp->cargo }}</td>
        <td style="font-size:7pt;">{{ $emp->tipo_contrato_label }}</td>
        <td style="text-align:right;">{{ number_format($bruto,2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ number_format($tss,2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ number_format($isr,2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ number_format($otros,2) }}</td>
        <td style="text-align:right;font-weight:700;color:#0f766e;">{{ number_format($neto,2) }}</td>
        <td style="text-align:center;">
            @if(!$p)
                <span class="badge badge-sinprocesar">Sin procesar</span>
            @elseif($p->pagado)
                <span class="badge badge-pagado">Pagado</span>
            @else
                <span class="badge badge-pendiente">Pendiente</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:right;color:#065f46;">TOTALES</td>
            <td style="text-align:right;">{{ number_format($totales['bruto'],2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($totales['tss'],2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($totales['isr'],2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($empleados->sum(fn($e) => $e->pagos->first()?->desc_otros ?? 0),2) }}</td>
            <td style="text-align:right;color:#065f46;">{{ number_format($totales['neto'],2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="sigs">
    <div class="sig-block">
        <div style="height:28px;"></div>
        <div class="sig-line">{{ $dir ?: '____________________________' }}</div>
        <div class="sig-role">Director/a General</div>
    </div>
    <div class="sig-block">
        <div style="height:28px;"></div>
        <div class="sig-line">____________________________</div>
        <div class="sig-role">Responsable de Nómina</div>
    </div>
</div>

<div class="footer">
    <span>{{ $inst }}</span>
    <span>Documento válido con sello y firma · Nómina {{ $mesLabel }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
