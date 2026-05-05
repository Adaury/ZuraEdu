<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:11px 16px; margin-bottom:14px; }
    .header h1 { font-size:13pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:8pt; opacity:.85; }
    .stats { display:flex; gap:12px; margin-bottom:14px; }
    .stat-box { flex:1; border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; text-align:center; }
    .stat-val { font-size:14pt; font-weight:800; }
    .stat-lbl { font-size:7pt; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
    .section-title { font-size:8.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.07em;
                     color:#1e3a6e; border-bottom:2px solid #1e3a6e; padding-bottom:4px; margin:14px 0 8px; }
    table { width:100%; border-collapse:collapse; margin-bottom:12px; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold; padding:4px 6px; text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 6px; font-size:8pt; border-bottom:1px solid #e5e7eb; }
    .badge-ap { background:#d1fae5; color:#065f46; padding:1px 5px; border-radius:3px; font-size:7pt; font-weight:700; }
    .badge-re { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:3px; font-size:7pt; font-weight:700; }
    .footer { margin-top:16px; font-size:7pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Rendimiento por Grupo</h1>
    <p>{{ $detalle->grupo?->grado?->nombre }} {{ $detalle->grupo?->seccion?->nombre }} &nbsp;·&nbsp; {{ $schoolYear?->nombre }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}</p>
</div>

@php
    $datos = $detalle->datos ?? [];
    $prom  = $datos['promedio_general'] ?? null;
    $apro  = $datos['porcentaje_aprobados'] ?? null;
    $total = $datos['total_estudiantes'] ?? 0;
    $repro = $datos['reprobados'] ?? 0;
@endphp

<div class="stats">
    <div class="stat-box">
        <div class="stat-val" style="color:#1e3a6e;">{{ number_format($prom ?? 0, 1) }}</div>
        <div class="stat-lbl">Promedio Gral.</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#10b981;">{{ number_format($apro ?? 0, 1) }}%</div>
        <div class="stat-lbl">Aprobados</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#ef4444;">{{ $repro }}</div>
        <div class="stat-lbl">Reprobados</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#6b7280;">{{ $total }}</div>
        <div class="stat-lbl">Estudiantes</div>
    </div>
</div>

@if(!empty($datos['por_asignatura']))
<div class="section-title">Rendimiento por Asignatura</div>
<table>
    <thead>
        <tr>
            <th>Asignatura</th>
            <th style="text-align:right;">Promedio</th>
            <th style="text-align:right;">% Aprobados</th>
            <th style="text-align:right;">Reprobados</th>
        </tr>
    </thead>
    <tbody>
    @foreach($datos['por_asignatura'] as $asi)
    <tr>
        <td style="font-weight:600;">{{ $asi['asignatura'] ?? '—' }}</td>
        <td style="text-align:right;font-weight:700;color:{{ ($asi['promedio'] ?? 0) >= 65 ? '#10b981' : '#ef4444' }};">
            {{ number_format($asi['promedio'] ?? 0, 1) }}
        </td>
        <td style="text-align:right;">{{ number_format($asi['pct_aprobados'] ?? 0, 1) }}%</td>
        <td style="text-align:right;">
            @if(($asi['reprobados'] ?? 0) > 0)
            <span class="badge-re">{{ $asi['reprobados'] }}</span>
            @else
            <span class="badge-ap">0</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">{{ $inst }} &nbsp;·&nbsp; Rendimiento — {{ $detalle->grupo?->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}</div>
</body>
</html>
