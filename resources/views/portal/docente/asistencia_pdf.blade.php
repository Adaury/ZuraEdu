<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:8px 12px; margin-bottom:8px; }
    .header h1 { font-size:10pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th {
        background:#1e3a6e; color:#fff; font-size:6.5pt; font-weight:bold;
        padding:3px 4px; text-align:center; border:1px solid #2a4f96;
    }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:3px 4px; font-size:7pt; border:1px solid #e5e7eb; vertical-align:middle; text-align:center; }
    tbody td.left { text-align:left; }
    .p  { background:#d1fae5; color:#065f46; font-weight:700; }
    .a  { background:#fee2e2; color:#991b1b; font-weight:700; }
    .t  { background:#fef3c7; color:#854d0e; font-weight:700; }
    .e  { background:#ede9fe; color:#6d28d9; font-weight:700; }
    .total-col { font-weight:700; background:#eff6ff; color:#1d4ed8; }
    .footer { margin-top:10px; font-size:6.5pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:5px; }
    .leyenda { margin-top:6px; font-size:6.5pt; color:#6b7280; display:flex; gap:12px; }
    .leyenda span { display:inline-flex; align-items:center; gap:4px; }
    .dot { display:inline-block; width:8px; height:8px; border-radius:2px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Registro de Asistencia</h1>
    <p>
        {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }} &nbsp;·&nbsp;
        {{ $asignacion->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        {{ now()->format('d/m/Y') }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </p>
</div>

@if($matriculas->isEmpty() || $fechas->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin registros de asistencia aún.</p>
@else
<table>
    <thead>
        <tr>
            <th class="left" style="min-width:18px;">#</th>
            <th class="left" style="min-width:130px;">Estudiante</th>
            @foreach($fechas as $f)
            <th style="min-width:22px;">{{ \Carbon\Carbon::parse($f)->format('d/m') }}</th>
            @endforeach
            <th style="min-width:30px;">P/T</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $i => $mat)
    @php
        $est = $mat->estudiante;
        $presentes = 0;
        $total = $fechas->count();
    @endphp
    <tr>
        <td class="left" style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="left" style="font-weight:600;font-size:7pt;">
            {{ trim(($est?->apellidos ?? $est?->apellido ?? '') . ' ' . ($est?->nombres ?? $est?->nombre ?? '')) }}
        </td>
        @foreach($fechas as $f)
        @php
            $key    = \Carbon\Carbon::parse($f)->format('Y-m-d');
            $estado = $mapa[$mat->id][$key]?->estado ?? null;
            $letra  = match($estado) { 'presente' => 'P', 'ausente' => 'A', 'tarde','tardanza' => 'T', 'excusa' => 'E', 'retiro' => 'R', default => '—' };
            $cls    = match($letra) { 'P' => 'p', 'A' => 'a', 'T' => 't', 'E' => 'e', default => '' };
            if (!in_array($letra, ['A', '—'])) $presentes++;
        @endphp
        <td class="{{ $cls }}">{{ $letra }}</td>
        @endforeach
        <td class="total-col">{{ $presentes }}/{{ $total }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="leyenda">
    <span><span class="dot" style="background:#d1fae5;"></span>P = Presente</span>
    <span><span class="dot" style="background:#fee2e2;"></span>A = Ausente</span>
    <span><span class="dot" style="background:#fef3c7;"></span>T = Tardanza</span>
    <span><span class="dot" style="background:#ede9fe;"></span>E = Excusa</span>
    <span>— = Sin registro</span>
    <span>P/T = Presentes / Total sesiones</span>
</div>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Asistencia — {{ $asignacion->asignatura?->nombre ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; {{ $matriculas->count() }} estudiante(s) · {{ $fechas->count() }} sesión(es)
</div>
</body>
</html>
