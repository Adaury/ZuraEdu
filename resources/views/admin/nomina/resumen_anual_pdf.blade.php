<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:9pt; color:#1e293b; }

.header { background:linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); color:#fff; padding:14px 20px 12px; margin-bottom:14px; }
.header h1 { font-size:14pt; font-weight:700; margin-bottom:3px; }
.header p  { font-size:8pt; opacity:.85; }

.chips { display:flex; gap:12px; margin-bottom:14px; }
.chip  { flex:1; text-align:center; border:1px solid #e5e7eb; border-radius:6px; padding:8px 4px; }
.chip .val { font-size:11pt; font-weight:800; }
.chip .lbl { font-size:7pt; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:1px; }

table { width:100%; border-collapse:collapse; font-size:8.5pt; }
thead th {
    background:#0f766e; color:#fff;
    font-size:7.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
    padding:6px 8px; text-align:left;
}
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; }
tbody tr:nth-child(even) td { background:#f8fafc; }
tbody tr.sin-datos td { opacity:.45; }
tfoot td { background:#F0FDF4; font-weight:800; border-top:2px solid #86EFAC; padding:6px 8px; }

.sigs { display:flex; justify-content:space-around; margin-top:35px; }
.sig-block { text-align:center; width:170px; }
.sig-line { border-top:1px solid #374151; padding-top:3px; font-size:8pt; font-weight:700; }
.sig-role { font-size:7pt; color:#6b7280; }

.footer { margin-top:18px; display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid #e5e7eb; font-size:7.5pt; color:#9ca3af; }
</style>
</head>
<body>

@php
$totalBruto = $meses->sum('bruto');
$totalTss   = $meses->sum('tss');
$totalIsr   = $meses->sum('isr');
$totalDeduc = $meses->sum('deduc');
$totalNeto  = $meses->sum('neto');
$mesesConDatos = $meses->filter(fn($m) => $m['bruto'] > 0);
@endphp

<div class="header">
    <h1>{{ $inst }} — Resumen Anual de Nómina {{ $anio }}</h1>
    <p>Consolidado mensual de pagos a empleados · Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="chips">
    <div class="chip" style="border-color:#86efac;">
        <div class="val" style="color:#065f46;">RD$ {{ number_format($totalBruto,2) }}</div>
        <div class="lbl">Bruto Anual</div>
    </div>
    <div class="chip" style="border-color:#fca5a5;">
        <div class="val" style="color:#991b1b;">-RD$ {{ number_format($totalDeduc,2) }}</div>
        <div class="lbl">Deducciones</div>
    </div>
    <div class="chip" style="border-color:#93c5fd;">
        <div class="val" style="color:#1d4ed8;">RD$ {{ number_format($totalNeto,2) }}</div>
        <div class="lbl">Neto Anual</div>
    </div>
    <div class="chip" style="border-color:#c4b5fd;">
        <div class="val" style="color:#7c3aed;">{{ $mesesConDatos->count() }}</div>
        <div class="lbl">Meses activos</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Mes</th>
            <th style="text-align:center;">Empleados</th>
            <th style="text-align:right;">Salario Bruto</th>
            <th style="text-align:right;">TSS</th>
            <th style="text-align:right;">ISR</th>
            <th style="text-align:right;">Deducciones</th>
            <th style="text-align:right;">Neto Pagado</th>
            <th style="text-align:center;">Pagados</th>
        </tr>
    </thead>
    <tbody>
    @foreach($meses as $m)
    @php $sinDatos = $m['bruto'] == 0 && $m['empleados'] == 0; @endphp
    <tr class="{{ $sinDatos ? 'sin-datos' : '' }}">
        <td style="font-weight:{{ $sinDatos ? 'normal' : '600' }};">{{ $m['nombre'] }} {{ $anio }}</td>
        <td style="text-align:center;">{{ $sinDatos ? '—' : $m['empleados'] }}</td>
        <td style="text-align:right;">{{ $sinDatos ? '—' : number_format($m['bruto'],2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ $sinDatos ? '—' : number_format($m['tss'],2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ $sinDatos ? '—' : number_format($m['isr'],2) }}</td>
        <td style="text-align:right;color:#dc2626;">{{ $sinDatos ? '—' : number_format($m['deduc'],2) }}</td>
        <td style="text-align:right;font-weight:{{ $sinDatos ? 'normal':'700' }};color:#0f766e;">{{ $sinDatos ? '—' : number_format($m['neto'],2) }}</td>
        <td style="text-align:center;">
            @if(!$sinDatos)
            {{ $m['pagados'] }} / {{ $m['total'] }}
            @else —
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    @if($mesesConDatos->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="2" style="color:#065f46;">TOTALES {{ $anio }}</td>
            <td style="text-align:right;">{{ number_format($totalBruto,2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($totalTss,2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($totalIsr,2) }}</td>
            <td style="text-align:right;color:#dc2626;">{{ number_format($totalDeduc,2) }}</td>
            <td style="text-align:right;color:#065f46;">{{ number_format($totalNeto,2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="sigs">
    <div class="sig-block">
        <div style="height:32px;"></div>
        <div class="sig-line">{{ $dir ?: '________________________________' }}</div>
        <div class="sig-role">Director/a General</div>
    </div>
    <div class="sig-block">
        <div style="height:32px;"></div>
        <div class="sig-line">________________________________</div>
        <div class="sig-role">Responsable de Nómina</div>
    </div>
</div>

<div class="footer">
    <span>{{ $inst }}</span>
    <span>Resumen Anual de Nómina — {{ $anio }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
