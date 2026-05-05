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
    .badge-pub  { background:#d1fae5; color:#065f46; }
    .badge-bor  { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Planes de Clase: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->nombre_completo }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ $planes->count() }} plan(es) &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@if($planes->isEmpty())
    <p style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay planes de clase registrados.</p>
@else
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Fecha</th>
            <th>Estrategias</th>
            <th>Momentos</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planes as $i => $plan)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $plan->titulo ?? '—' }}</td>
            <td>{{ $plan->fecha?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ is_array($plan->estrategias) ? implode(', ', array_slice($plan->estrategias, 0, 3)) : '—' }}</td>
            <td>{{ $plan->momentos->count() }}</td>
            <td>
                @if($plan->publicado)
                    <span class="badge badge-pub">Publicado</span>
                @else
                    <span class="badge badge-bor">Borrador</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
