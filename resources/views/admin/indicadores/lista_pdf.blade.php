<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
    .header { text-align:center; margin-bottom:10px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
    .header h2 { font-size:13px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:5px 4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .periodo { background:#dbeafe; color:#1e40af; padding:1px 5px; border-radius:3px; font-size:7px; font-weight:600; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Indicadores de Aprendizaje</h2>
    <p>Total: {{ $indicadores->count() }} indicadores &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Asignatura</th>
            <th>Grado</th>
            <th>Período</th>
            <th>Descripción</th>
            <th>Orden</th>
        </tr>
    </thead>
    <tbody>
        @foreach($indicadores as $i => $ind)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $ind->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $ind->grado?->nombre ?? '—' }}</td>
            <td><span class="periodo">P{{ $ind->periodo_numero }}</span></td>
            <td>{{ $ind->descripcion }}</td>
            <td>{{ $ind->orden }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
