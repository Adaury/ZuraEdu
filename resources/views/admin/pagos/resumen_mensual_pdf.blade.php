<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:14px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:8pt; font-weight:bold;
               padding:5px 7px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4.5px 7px; font-size:8.5pt; border:1px solid #e5e7eb;
               text-align:center; vertical-align:middle; }
    tbody td.left { text-align:left; font-weight:600; }
    tfoot td { padding:5px 7px; font-size:8.5pt; font-weight:800;
               background:#1e3a6e; color:#fff; border:1px solid #2a4f96; text-align:center; }
    tfoot td.left { text-align:left; }
    .pago { color:#065f46; font-weight:700; }
    .pend { color:#92400e; }
    .venc { color:#991b1b; font-weight:700; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Resumen Mensual de Pagos</h1>
    <p>Año escolar: {{ $sy?->nombre ?? date('Y') }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th class="left" style="min-width:90px;">Mes</th>
            <th style="width:100px;">Cobrado ({{ $mon }})</th>
            <th style="width:100px;">Pendiente ({{ $mon }})</th>
            <th style="width:100px;">Vencido ({{ $mon }})</th>
            <th style="width:70px;">Registros</th>
        </tr>
    </thead>
    <tbody>
    @foreach($meses as $m)
    <tr>
        <td class="left" style="text-transform:capitalize;">{{ $m['nombre'] }}</td>
        <td class="pago">{{ number_format($m['pagado'], 2) }}</td>
        <td class="pend">{{ number_format($m['pendiente'], 2) }}</td>
        <td class="venc">{{ number_format($m['vencido'], 2) }}</td>
        <td>{{ $m['total_reg'] }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="left">TOTALES</td>
            <td>{{ number_format($meses->sum('pagado'), 2) }}</td>
            <td>{{ number_format($meses->sum('pendiente'), 2) }}</td>
            <td>{{ number_format($meses->sum('vencido'), 2) }}</td>
            <td>{{ $meses->sum('total_reg') }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Resumen Mensual de Pagos &nbsp;·&nbsp; {{ $sy?->nombre ?? date('Y') }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
