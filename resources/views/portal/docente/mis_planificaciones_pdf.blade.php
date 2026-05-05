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
    .asig-block { margin-bottom:12px; }
    .asig-title { background:#1e3a6e; color:#fff; padding:4px 8px; font-size:9px; font-weight:700; border-radius:3px; margin-bottom:4px; }
    table { width:100%; border-collapse:collapse; margin-bottom:6px; }
    th { background:#e8edf5; color:#1e293b; padding:4px; font-size:8px; text-align:left; }
    td { padding:3px 4px; border-bottom:1px solid #e2e8f0; font-size:8.5px; vertical-align:top; }
    tr.alt { background:#f8faff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-ra   { background:#ede9fe; color:#6d28d9; }
    .badge-act  { background:#d1fae5; color:#065f46; }
    .badge-pub  { background:#dcfce7; color:#15803d; }
    .badge-bor  { background:#f3f4f6; color:#6b7280; }
    .empty { color:#94a3b8; font-size:8px; font-style:italic; padding:4px; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Mis Planificaciones</h2>
    <p>
        Docente: {{ $docente->nombre_completo }}
        &nbsp;|&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@foreach($asignaciones as $asig)
<div class="asig-block">
    <div class="asig-title">
        {{ $asig->asignatura?->nombre ?? '—' }} — {{ $asig->grupo?->nombre_completo ?? '—' }}
        &nbsp;({{ $planificaciones->get($asig->id)?->count() ?? 0 }} planificaciones)
    </div>

    @if(($planificaciones->get($asig->id) ?? collect())->isEmpty())
        <p class="empty">Sin planificaciones registradas.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Módulo / Título</th>
                <th>Código MF</th>
                <th>Tipo</th>
                <th>RA / Actividades</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($planificaciones->get($asig->id) as $i => $plan)
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $plan->modulo_nombre ?? $plan->titulo ?? '—' }}</td>
                <td>{{ $plan->mf_codigo ?? '—' }}</td>
                <td>
                    @if($plan->tipo === 'ra')
                        <span class="badge badge-ra">Por RA</span>
                    @else
                        <span class="badge badge-act">Por Actividad</span>
                    @endif
                </td>
                <td>
                    @if($plan->tipo === 'ra')
                        {{ $plan->raItems->count() }} RA(s)
                    @else
                        {{ $plan->actividades->count() }} actividad(es)
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
    @endif
</div>
@endforeach

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
