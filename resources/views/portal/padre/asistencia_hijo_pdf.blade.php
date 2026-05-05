<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    .stats { display:flex; gap:8px; margin-bottom:10px; }
    .stat-box { flex:1; border:1px solid #e5e7eb; border-radius:4px; padding:6px 8px; text-align:center; }
    .stat-val { font-size:14pt; font-weight:bold; }
    .stat-lbl { font-size:7pt; color:#64748b; }
    table { width:100%; border-collapse:collapse; margin-top:8px; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 5px; font-size:8pt; border:1px solid #e5e7eb; vertical-align:middle; }
    tbody td.center { text-align:center; }
    .ok   { color:#065f46; font-weight:700; }
    .risk { color:#991b1b; font-weight:700; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Reporte de Asistencia</h1>
    <p>
        {{ $estudiante->nombre_completo }} &nbsp;·&nbsp;
        {{ $matricula?->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        {{ now()->format('d/m/Y') }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </p>
</div>

@if($resumenAsistencia['total'] === 0)
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin registros de asistencia disponibles.</p>
@else

{{-- Resumen general --}}
<table style="margin-bottom:10px;">
    <thead>
        <tr>
            <th>Total Sesiones</th>
            <th>Presentes</th>
            <th>Tardanzas</th>
            <th>Ausentes</th>
            <th>% Asistencia</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="center">{{ $resumenAsistencia['total'] }}</td>
            <td class="center ok">{{ $resumenAsistencia['presentes'] }}</td>
            <td class="center" style="color:#92400e;font-weight:700;">{{ $resumenAsistencia['tardanzas'] }}</td>
            <td class="center risk">{{ $resumenAsistencia['ausentes'] }}</td>
            <td class="center {{ ($resumenAsistencia['porcentaje'] ?? 100) >= 80 ? 'ok' : 'risk' }}">
                {{ $resumenAsistencia['porcentaje'] !== null ? $resumenAsistencia['porcentaje'].'%' : '—' }}
            </td>
        </tr>
    </tbody>
</table>

{{-- Desglose por materia --}}
@if(!empty($resumenAsistencia['por_materia']))
<p style="font-size:8.5pt;font-weight:700;color:#1e3a6e;margin-bottom:4px;">Desglose por Materia</p>
<table>
    <thead>
        <tr>
            <th class="left" style="min-width:160px;">Asignatura</th>
            <th style="width:60px;">Total</th>
            <th style="width:60px;">Presentes</th>
            <th style="width:60px;">Ausentes</th>
            <th style="width:65px;">% Asist.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($resumenAsistencia['por_materia'] as $pm)
    @php $okm = $pm['porcentaje'] === null || $pm['porcentaje'] >= 80; @endphp
    <tr>
        <td style="font-weight:600;">{{ $pm['asignatura'] }}</td>
        <td class="center">{{ $pm['total'] }}</td>
        <td class="center ok">{{ $pm['presentes'] }}</td>
        <td class="center risk">{{ $pm['ausentes'] }}</td>
        <td class="center {{ $okm ? 'ok' : 'risk' }}">{{ $pm['porcentaje'] !== null ? $pm['porcentaje'].'%' : '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Asistencia — {{ $estudiante->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
