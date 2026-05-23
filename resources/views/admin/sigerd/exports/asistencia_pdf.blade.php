<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGERD - Registro de Asistencia</title>
<style>
body { font-family: Arial, sans-serif; font-size: 9px; margin: 20px; }
h2   { color: #1e3a6e; margin-bottom: 3px; font-size: 13px; }
.subtitle { color: #555; margin-bottom: 12px; font-size: 9px; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; }
th { background-color: #1e3a6e; color: #fff; padding: 5px 4px; text-align: center; font-size: 8px; border: 1px solid #1e3a6e; }
th.left { text-align: left; }
td { padding: 3.5px 4px; border: 1px solid #ccc; font-size: 8.5px; text-align: center; }
td.left { text-align: left; }
tr.alt td { background-color: #f0f4ff; }
.pct-alta  { color: #065f46; font-weight: bold; }
.pct-media { color: #92400e; font-weight: bold; }
.pct-baja  { color: #991b1b; font-weight: bold; }
.footer { margin-top: 14px; font-size: 8px; color: #666; border-top: 1px solid #e5e7eb; padding-top: 4px; }
</style>
</head>
<body>

<h2>SIGERD — Registro de Asistencia</h2>
<p class="subtitle">
    Año Escolar: <strong>{{ $sy->nombre }}</strong> &nbsp;·&nbsp;
    Período: <strong>{{ $desde }}</strong> al <strong>{{ $hasta }}</strong> &nbsp;·&nbsp;
    Generado: {{ date('d/m/Y H:i') }}
</p>

<table>
    <thead>
        <tr>
            <th class="left" style="width:20px;">No.</th>
            <th class="left" style="width:68px;">RNE/Cédula</th>
            <th class="left">Apellidos, Nombres</th>
            <th style="width:65px;">Grado / Sección</th>
            <th style="width:32px;">Total</th>
            <th style="width:34px;">Pres.</th>
            <th style="width:34px;">Tard.</th>
            <th style="width:34px;">Aus.</th>
            <th style="width:34px;">Just.</th>
            <th style="width:40px;">% Asist.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filasPdf as $i => $fila)
        @php
            $m   = $fila['matricula'];
            $pct = $fila['pct'];
            $pctClass = $pct >= 90 ? 'pct-alta' : ($pct >= 75 ? 'pct-media' : 'pct-baja');
        @endphp
        <tr class="{{ $i % 2 !== 0 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td class="left">{{ $m->estudiante?->cedula ?? '—' }}</td>
            <td class="left">{{ ($m->estudiante?->apellidos ?? '') . ', ' . ($m->estudiante?->nombres ?? '') }}</td>
            <td>{{ $m->grupo?->grado?->nombre ?? '—' }} / {{ $m->grupo?->seccion?->nombre ?? '—' }}</td>
            <td>{{ $fila['total'] }}</td>
            <td>{{ $fila['pres'] }}</td>
            <td>{{ $fila['tard'] }}</td>
            <td>{{ $fila['ause'] }}</td>
            <td>{{ $fila['just'] }}</td>
            <td class="{{ $pctClass }}">{{ number_format($pct, 1) }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p class="footer">
    Total estudiantes: <strong>{{ count($filasPdf) }}</strong> &nbsp;·&nbsp;
    Promedio asistencia: <strong>{{ count($filasPdf) > 0 ? number_format(collect($filasPdf)->avg('pct'), 1) : 0 }}%</strong> &nbsp;·&nbsp;
    SIGERD — Sistema de Gestión Educativa
</p>
</body>
</html>
