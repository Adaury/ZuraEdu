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
    table { width:100%; border-collapse:collapse; }
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
    <h1>{{ $inst }} — Asistencia del Grupo</h1>
    <p>
        {{ $grupo->nombre_completo ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
        @if($grupo->schoolYear) &nbsp;·&nbsp; {{ $grupo->schoolYear->nombre }} @endif
        &nbsp;·&nbsp; {{ $grupo->matriculas->count() }} estudiante(s)
    </p>
</div>

@if($grupo->matriculas->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin estudiantes matriculados.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left" style="min-width:180px;">Estudiante</th>
            <th style="width:60px;">Total</th>
            <th style="width:60px;">Presentes</th>
            <th style="width:60px;">Tardanzas</th>
            <th style="width:60px;">Ausentes</th>
            <th style="width:65px;">% Asist.</th>
            <th style="width:55px;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($grupo->matriculas as $i => $mat)
    @php
        $asis  = $mat->asistencias;
        $total = $asis->count();
        $pres  = $asis->whereIn('estado', ['presente','tardanza'])->count();
        $tard  = $asis->where('estado','tardanza')->count();
        $aus   = $asis->where('estado','ausente')->count();
        $pct   = $total > 0 ? round($pres / $total * 100, 1) : null;
        $ok    = $pct === null || $pct >= 80;
    @endphp
    <tr>
        <td class="center" style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">
            {{ trim(($mat->estudiante?->apellidos ?? '') . ', ' . ($mat->estudiante?->nombres ?? '')) }}
        </td>
        <td class="center">{{ $total ?: '—' }}</td>
        <td class="center">{{ $total ? $pres : '—' }}</td>
        <td class="center">{{ $total ? $tard : '—' }}</td>
        <td class="center">{{ $total ? $aus  : '—' }}</td>
        <td class="center {{ $ok ? 'ok' : 'risk' }}">{{ $pct !== null ? $pct.'%' : '—' }}</td>
        <td class="center {{ $ok ? 'ok' : 'risk' }}">{{ $pct === null ? '—' : ($ok ? 'Regular' : 'Riesgo') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Asistencia — {{ $grupo->nombre_completo ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
