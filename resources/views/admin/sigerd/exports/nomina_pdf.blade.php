<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGERD - Nomina de Matricula</title>
<style>
body { font-family: Arial, sans-serif; font-size: 10px; margin: 20px; }
h2 { color: #1e3a6e; margin-bottom: 5px; }
.subtitle { color: #555; margin-bottom: 15px; font-size: 11px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th { background-color: #1e3a6e; color: #fff; padding: 5px 4px; text-align: left; font-size: 9px; }
td { padding: 4px; border: 1px solid #ccc; font-size: 9px; }
tr.alt td { background-color: #f0f4ff; }
.footer { margin-top: 15px; font-size: 10px; color: #666; }
</style>
</head>
<body>
<h2><i>SIGERD</i> - Nomina de Matricula</h2>
<p class="subtitle">Fecha de generacion: {{ date('d/m/Y H:i') }}</p>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>RNE/Cedula</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Sexo</th>
            <th>Fecha Nac.</th>
            <th>Grado</th>
            <th>Seccion</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $m)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $m->estudiante?->cedula }}</td>
            <td>{{ $m->estudiante?->nombres }}</td>
            <td>{{ $m->estudiante?->apellidos }}</td>
            <td>{{ $m->estudiante?->sexo }}</td>
            <td>{{ $m->estudiante?->fecha_nacimiento?->format('d/m/Y') }}</td>
            <td>{{ $m->grupo?->grado?->nombre }}</td>
            <td>{{ $m->grupo?->seccion?->nombre }}</td>
            <td>{{ $m->estudiante?->estado }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<p class="footer">Total registros: {{ $matriculas->count() }}</p>
</body>
</html>
