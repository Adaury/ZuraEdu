<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGERD - Nómina de Docentes</title>
<style>
body { font-family: Arial, sans-serif; font-size: 9px; margin: 20px; }
h2   { color: #1e3a6e; margin-bottom: 3px; font-size: 13px; }
.subtitle { color: #555; margin-bottom: 12px; font-size: 9px; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; }
th { background-color: #1e3a6e; color: #fff; padding: 5px 4px; text-align: left; font-size: 8px; border: 1px solid #1e3a6e; }
td { padding: 4px; border: 1px solid #ccc; font-size: 8.5px; }
tr.alt td { background-color: #f0f4ff; }
.footer { margin-top: 14px; font-size: 8px; color: #666; border-top: 1px solid #e5e7eb; padding-top: 4px; }
</style>
</head>
<body>

<h2>SIGERD — Nómina de Docentes</h2>
<p class="subtitle">Año Escolar: <strong>{{ $sy->nombre }}</strong> &nbsp;·&nbsp; Generado: {{ date('d/m/Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th style="width:20px;">No.</th>
            <th style="width:70px;">Cédula</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th style="width:80px;">Especialidad</th>
            <th style="width:80px;">Título Académico</th>
            <th style="width:55px;">Cargo</th>
            <th>Asignatura(s)</th>
            <th>Grupo(s)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($docenteRows as $i => $dr)
        <tr class="{{ $i % 2 !== 0 ? 'alt' : '' }}">
            <td style="text-align:center;">{{ $i + 1 }}</td>
            <td>{{ $dr['docente']?->cedula ?? '—' }}</td>
            <td>{{ $dr['docente']?->nombres ?? '—' }}</td>
            <td>{{ $dr['docente']?->apellidos ?? '—' }}</td>
            <td>{{ $dr['docente']?->especialidad ?? '—' }}</td>
            <td>{{ $dr['docente']?->titulo_academico ?? '—' }}</td>
            <td>{{ $dr['docente']?->cargo ?? '—' }}</td>
            <td>{{ $dr['asignaturas'] ?: '—' }}</td>
            <td>{{ $dr['grupos'] ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p class="footer">
    Total docentes: <strong>{{ count($docenteRows) }}</strong> &nbsp;·&nbsp;
    SIGERD — Sistema de Gestión Educativa &nbsp;·&nbsp; {{ date('d/m/Y H:i') }}
</p>
</body>
</html>
