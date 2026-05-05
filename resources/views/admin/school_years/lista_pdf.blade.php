<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
    .header { text-align:center; margin-bottom:10px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
    .header h2 { font-size:12px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:5px 4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-act { background:#d1fae5; color:#065f46; }
    .badge-ina { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Años Escolares</h2>
    <p>Total: {{ $schoolYears->count() }} año(s) &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Grupos</th>
            <th>Períodos</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($schoolYears as $i => $sy)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $sy->nombre }}</td>
            <td>{{ $sy->fecha_inicio ? \Carbon\Carbon::parse($sy->fecha_inicio)->format('d/m/Y') : '—' }}</td>
            <td>{{ $sy->fecha_fin   ? \Carbon\Carbon::parse($sy->fecha_fin)->format('d/m/Y')   : '—' }}</td>
            <td>{{ $sy->grupos_count }}</td>
            <td>{{ $sy->periodos_count }}</td>
            <td>
                @if($sy->activo)
                    <span class="badge badge-act">Activo</span>
                @else
                    <span class="badge badge-ina">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
