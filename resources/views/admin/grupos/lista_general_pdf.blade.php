<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 14px; }
    .header .inst { font-size: 11pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 9pt; color: #475569; margin-top: 2px; }
    .header .sub { font-size: 8pt; color: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #1e3a6e; color: #fff; font-size: 8pt; padding: 5px 6px; text-align: left; }
    th.c { text-align: center; }
    td { font-size: 8.5pt; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
    td.c { text-align: center; }
    tr.even td { background: #eff6ff; }
    .footer { margin-top: 14px; text-align: right; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Lista de Grupos — {{ $schoolYear?->nombre ?? 'Todos los años' }}</div>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }} — Total: {{ $grupos->count() }} grupos</div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Grado</th>
            <th>Sección</th>
            <th>Tutor/a</th>
            <th class="c">Matrícula</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grupos as $i => $grupo)
        <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $grupo->nombre_completo ?? '—' }}</strong></td>
            <td>{{ $grupo->grado?->nombre ?? '—' }}</td>
            <td>{{ $grupo->seccion?->nombre ?? '—' }}</td>
            <td>{{ $grupo->tutor ? trim(($grupo->tutor->apellidos ?? '') . ', ' . ($grupo->tutor->nombres ?? $grupo->tutor->nombre ?? '')) : '—' }}</td>
            <td class="c"><strong>{{ $grupo->matriculas_count ?? 0 }}</strong></td>
        </tr>
        @endforeach
        <tr>
            <td colspan="5" style="text-align:right;font-weight:bold;font-size:8pt;padding-top:6px;">Total estudiantes:</td>
            <td class="c" style="font-weight:bold;padding-top:6px;">{{ $grupos->sum('matriculas_count') }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
