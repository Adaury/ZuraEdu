<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:7.5px; color:#1e293b; }
    .header { text-align:center; margin-bottom:8px; border-bottom:2px solid #1e3a6e; padding-bottom:6px; }
    .header h2 { font-size:11px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:7.5px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:3px 2px; font-size:7px; text-align:center; border:1px solid #1e3a6e; }
    th.nombre { text-align:left; min-width:80px; }
    td { padding:2px; border:1px solid #e2e8f0; text-align:center; font-size:7px; }
    td.nombre { text-align:left; font-size:7.5px; padding-left:3px; }
    tr.alt { background:#f8faff; }
    .p  { background:#d1fae5; color:#065f46; font-weight:700; }
    .a  { background:#fee2e2; color:#991b1b; font-weight:700; }
    .t  { background:#fef3c7; color:#92400e; font-weight:700; }
    .e  { background:#ede9fe; color:#6d28d9; font-weight:700; }
    .leyenda { margin-top:6px; font-size:7px; display:flex; gap:10px; }
    .ley-item { display:flex; align-items:center; gap:3px; }
    .dot { width:8px; height:8px; border-radius:2px; display:inline-block; }
    .footer { margin-top:8px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Grilla de Asistencia: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->nombre_completo }}
        &nbsp;·&nbsp; Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }}
        &nbsp;·&nbsp; Mes: {{ ucfirst($nombreMes) }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th class="nombre">#</th>
            <th class="nombre">Estudiante</th>
            @for($d = 1; $d <= $diasEnMes; $d++)
                <th>{{ $d }}</th>
            @endfor
            <th>P</th>
            <th>A</th>
            <th>T</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $fila  = $asistencias->get($mat->id) ?? collect();
            $pres  = 0; $ause = 0; $tard = 0;
        @endphp
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td class="nombre">{{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}</td>
            @for($d = 1; $d <= $diasEnMes; $d++)
                @php $reg = $fila->get($d); $est = $reg?->estado ?? ''; @endphp
                @if($est === 'presente')     @php $pres++; @endphp <td class="p">P</td>
                @elseif($est === 'ausente')  @php $ause++; @endphp <td class="a">A</td>
                @elseif($est === 'tardanza' || $est === 'tarde') @php $tard++; $pres++; @endphp <td class="t">T</td>
                @elseif($est === 'excusa')   <td class="e">E</td>
                @else <td></td>
                @endif
            @endfor
            <td><strong>{{ $pres }}</strong></td>
            <td><strong>{{ $ause }}</strong></td>
            <td><strong>{{ $tard }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="leyenda">
    <span class="ley-item"><span class="dot p"></span>P = Presente</span>
    <span class="ley-item"><span class="dot a"></span>A = Ausente</span>
    <span class="ley-item"><span class="dot t"></span>T = Tardanza</span>
    <span class="ley-item"><span class="dot e"></span>E = Excusa</span>
</div>
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
