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
    .asig-block { margin-bottom:14px; }
    .asig-title { background:#1e3a6e; color:#fff; padding:4px 6px; font-size:9px; font-weight:700; }
    table { width:100%; border-collapse:collapse; }
    th { background:#334e8a; color:#fff; padding:4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; font-size:8px; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-pub  { background:#d1fae5; color:#065f46; }
    .badge-act  { background:#dbeafe; color:#1e40af; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Planificaciones: {{ $estudiante->nombre_completo }}</h2>
    <p>
        Grupo: {{ $matricula?->grupo?->nombre_completo ?? '—' }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@forelse($planificaciones as $asignacionId => $plans)
@php $primera = $plans->first(); @endphp
<div class="asig-block">
    <div class="asig-title">{{ $primera->asignacion?->asignatura?->nombre ?? 'Asignatura' }} — {{ $primera->asignacion?->docente?->nombre_completo ?? '' }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Módulo / Título</th>
                <th>Código MF</th>
                <th>Tipo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plans as $i => $plan)
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $plan->modulo ?? $plan->titulo ?? '—' }}</td>
                <td>{{ $plan->codigo_mf ?? '—' }}</td>
                <td>
                    @if($plan->tipo === 'ra')
                        <span class="badge badge-act">RA</span>
                    @else
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;">Actividad</span>
                    @endif
                </td>
                <td><span class="badge badge-pub">Publicado</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@empty
    <p style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay planificaciones publicadas.</p>
@endforelse

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
