<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background: #1e3a6e; color: #fff; padding: 10px 16px; margin-bottom: 12px; }
    .header h1 { font-size: 12pt; font-weight: bold; margin-bottom: 2px; }
    .header p  { font-size: 7.5pt; opacity: .85; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #1e3a6e; color: #fff; font-size: 7.5pt; font-weight: bold;
        padding: 5px 6px; text-align: center;
    }
    thead th.left { text-align: left; }
    tbody tr.alerta td { background: #fff1f2; }
    tbody tr:nth-child(even):not(.alerta) td { background: #f8fafc; }
    tbody td { padding: 4px 6px; font-size: 8pt; border-bottom: 1px solid #e5e7eb; text-align: center; }
    tbody td.nombre { text-align: left; font-weight: 600; }
    .pct-ok  { color: #065f46; font-weight: 700; }
    .pct-bad { color: #991b1b; font-weight: 700; }
    .footer { margin-top: 14px; font-size: 7pt; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Reporte de Asistencia</h1>
    <p>
        {{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }}
        &nbsp;·&nbsp; {{ $schoolYear?->nombre }}
        @if($periodo)
            &nbsp;·&nbsp; Período {{ $periodo->numero }}
        @else
            &nbsp;·&nbsp; Año completo
        @endif
        &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left">Estudiante</th>
            <th>Total</th>
            <th>Presente</th>
            <th>Ausente</th>
            <th>Tarde</th>
            <th>Excusa</th>
            <th>% Asist.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stats as $s)
        <tr class="{{ $s['alerta'] ? 'alerta' : '' }}">
            <td style="color:#9ca3af;">{{ $loop->iteration }}</td>
            <td class="nombre">
                {{ $s['matricula']?->estudiante?->apellidos }}, {{ $s['matricula']?->estudiante?->nombres }}
            </td>
            <td>{{ $s['total'] }}</td>
            <td style="color:#10b981; font-weight:600;">{{ $s['presente'] }}</td>
            <td style="color:#ef4444; font-weight:600;">{{ $s['ausente'] }}</td>
            <td style="color:#f59e0b; font-weight:600;">{{ $s['tarde'] }}</td>
            <td style="color:#6b7280;">{{ $s['excusa'] }}</td>
            <td class="{{ $s['alerta'] ? 'pct-bad' : 'pct-ok' }}">
                {{ $s['porcentaje'] !== null ? $s['porcentaje'] . '%' : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; {{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }}
    &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Las filas en rosa indican asistencia &lt; 75%
</div>
</body>
</html>
