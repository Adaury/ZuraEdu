<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
    .header { text-align:center; margin-bottom:12px; border-bottom:2px solid #1e3a6e; padding-bottom:8px; }
    .header h2 { font-size:13px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:5px 4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-ra       { background:#ede9fe; color:#6d28d9; }
    .badge-act      { background:#d1fae5; color:#065f46; }
    .badge-pub      { background:#d1fae5; color:#065f46; }
    .badge-bor      { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Planificaciones Área Técnica</h2>
    <p>Año Escolar: {{ $schoolYear?->nombre ?? '—' }} &nbsp;|&nbsp; Total: {{ $planificaciones->count() }} planificaciones &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Módulo</th>
            <th>Código MF</th>
            <th>Asignatura</th>
            <th>Docente</th>
            <th>Grupo</th>
            <th>Tipo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planificaciones as $i => $plan)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $plan->modulo_nombre ?? '—' }}</td>
            <td>{{ $plan->mf_codigo ?? '—' }}</td>
            <td>{{ $plan->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $plan->asignacion?->docente?->nombre_completo ?? '—' }}</td>
            <td>{{ $plan->asignacion?->grupo?->nombre_completo ?? '—' }}</td>
            <td>
                @if($plan->tipo === 'ra')
                    <span class="badge badge-ra">Por RA</span>
                @else
                    <span class="badge badge-act">Por Actividad</span>
                @endif
            </td>
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
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
