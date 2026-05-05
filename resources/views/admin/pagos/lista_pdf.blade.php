<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 16px; margin-bottom:10px; }
    .header h1 { font-size:12pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    .resumen { display:flex; gap:16px; margin-bottom:10px; }
    .resumen-box { flex:1; text-align:center; border:1px solid #e5e7eb; border-radius:5px; padding:6px 4px; }
    .resumen-box .val { font-size:10.5pt; font-weight:700; }
    .resumen-box .lbl { font-size:7pt; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:bold; padding:4px 5px; text-align:left; }
    tbody td { padding:3.5px 5px; font-size:7.5pt; border-bottom:1px solid #e5e7eb; }
    .est-pagado   { background:#d1fae5; color:#065f46; padding:1px 5px; border-radius:3px; font-size:6.5pt; font-weight:700; }
    .est-pendiente{ background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:3px; font-size:6.5pt; font-weight:700; }
    .est-vencido  { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:3px; font-size:6.5pt; font-weight:700; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Registro de Pagos</h1>
    <p>{{ $sy?->nombre ?? '' }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; {{ $mon }}</p>
</div>

<div class="resumen">
    <div class="resumen-box">
        <div class="val" style="color:#065f46;">{{ number_format($totales['pagado'], 2) }}</div>
        <div class="lbl">Cobrado</div>
    </div>
    <div class="resumen-box">
        <div class="val" style="color:#92400e;">{{ number_format($totales['pendiente'], 2) }}</div>
        <div class="lbl">Pendiente</div>
    </div>
    <div class="resumen-box">
        <div class="val" style="color:#991b1b;">{{ number_format($totales['vencido'], 2) }}</div>
        <div class="lbl">Vencido</div>
    </div>
    <div class="resumen-box">
        <div class="val" style="color:#1e3a6e;">{{ $pagos->count() }}</div>
        <div class="lbl">Registros</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th>Estudiante</th>
            <th>Grupo</th>
            <th>Concepto</th>
            <th style="text-align:right;">Monto</th>
            <th>Vencimiento</th>
            <th>F. Pago</th>
            <th style="text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($pagos as $i => $pago)
    @php
        $est = $pago->matricula?->estudiante;
        $grp = $pago->matricula?->grupo;
    @endphp
    <tr>
        <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $est ? (($est->apellidos ?? '') . ', ' . ($est->nombres ?? '')) : '—' }}</td>
        <td style="font-size:7pt;">{{ $grp ? (($grp->grado?->nombre ?? '') . ' ' . ($grp->seccion?->nombre ?? '')) : '—' }}</td>
        <td>{{ $pago->concepto ?? '—' }}</td>
        <td style="text-align:right;font-weight:600;">{{ number_format($pago->monto, 2) }}</td>
        <td>{{ $pago->fecha_vencimiento ? \Carbon\Carbon::parse($pago->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
        <td>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '—' }}</td>
        <td style="text-align:center;">
            <span class="est-{{ $pago->estado ?? 'pendiente' }}">{{ ucfirst($pago->estado ?? '—') }}</span>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Registro de Pagos &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
