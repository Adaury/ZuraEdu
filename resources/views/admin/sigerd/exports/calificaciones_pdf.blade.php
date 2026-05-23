<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGERD - Libro de Calificaciones</title>
<style>
body { font-family: Arial, sans-serif; font-size: 8px; margin: 15px; }
h2   { color: #1e3a6e; margin-bottom: 3px; font-size: 13px; }
.subtitle { color: #555; margin-bottom: 12px; font-size: 9px; }
.asig-title { background: #1e3a6e; color: #fff; padding: 4px 8px; font-size: 9px; font-weight: bold; margin-top: 14px; margin-bottom: 0; border-radius: 3px 3px 0 0; }
table { width: 100%; border-collapse: collapse; }
th { background-color: #2563eb; color: #fff; padding: 4px; text-align: center; font-size: 8px; border: 1px solid #1e3a6e; }
th.left { text-align: left; }
td { padding: 3px 4px; border: 1px solid #ccc; font-size: 8px; }
td.center { text-align: center; }
tr.alt td { background-color: #f0f4ff; }
.sit-a { color: #065f46; font-weight: bold; }
.sit-r { color: #991b1b; font-weight: bold; }
.footer { margin-top: 12px; font-size: 8px; color: #666; border-top: 1px solid #e5e7eb; padding-top: 4px; }
.page-break { page-break-before: always; }
</style>
</head>
<body>

<h2>SIGERD — Libro de Calificaciones</h2>
<p class="subtitle">Año Escolar: <strong>{{ $sy->nombre }}</strong> &nbsp;·&nbsp; Generado: {{ date('d/m/Y H:i') }}</p>

@foreach($filasPdf as $bloque)
@php $asig = $bloque['asignacion']; $filas = $bloque['filas']; @endphp
@if(!$loop->first)<div class="page-break"></div>@endif

<div class="asig-title">
    {{ $asig->asignatura?->nombre ?? '—' }}
    &nbsp;·&nbsp; Docente: {{ $asig->docente?->nombre_completo ?? '—' }}
</div>
<table>
    <thead>
        <tr>
            <th class="left" style="width:18px;">No.</th>
            <th class="left" style="width:70px;">RNE/Cédula</th>
            <th class="left">Apellidos, Nombres</th>
            <th style="width:28px;">P1</th>
            <th style="width:28px;">P2</th>
            <th style="width:28px;">P3</th>
            <th style="width:28px;">P4</th>
            <th style="width:32px;">N.F.</th>
            <th style="width:38px;">Situación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $i => $fila)
        <tr class="{{ $i % 2 !== 0 ? 'alt' : '' }}">
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $fila['cedula'] }}</td>
            <td>{{ $fila['nombre'] }}</td>
            <td class="center">{{ $fila['p1'] !== null ? number_format($fila['p1'], 1) : '—' }}</td>
            <td class="center">{{ $fila['p2'] !== null ? number_format($fila['p2'], 1) : '—' }}</td>
            <td class="center">{{ $fila['p3'] !== null ? number_format($fila['p3'], 1) : '—' }}</td>
            <td class="center">{{ $fila['p4'] !== null ? number_format($fila['p4'], 1) : '—' }}</td>
            <td class="center" style="font-weight:700;">{{ $fila['nota_final'] !== null ? number_format((float)$fila['nota_final'], 1) : '—' }}</td>
            <td class="center {{ $fila['situacion'] === 'A' ? 'sit-a' : ($fila['situacion'] === 'R' ? 'sit-r' : '') }}">
                {{ $fila['situacion'] === 'A' ? 'Aprobado' : ($fila['situacion'] === 'R' ? 'Reprobado' : '—') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<p style="font-size:8px;color:#555;margin-top:3px;">Total: {{ count($filas) }} estudiantes</p>
@endforeach

<p class="footer">SIGERD — Sistema de Gestión Educativa &nbsp;·&nbsp; {{ date('d/m/Y H:i') }}</p>
</body>
</html>
