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
    .card { border:1px solid #e2e8f0; border-radius:4px; padding:6px 8px; margin-bottom:7px; }
    .card-title { font-size:10px; font-weight:700; color:#1e3a6e; }
    .card-meta  { font-size:7px; color:#64748b; margin:2px 0 3px; }
    .card-url   { font-size:7px; color:#3b82f6; word-break:break-all; }
    .card-desc  { font-size:8px; color:#334155; margin-top:3px; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; margin-left:4px; }
    .badge-doc { background:#dbeafe; color:#1e40af; }
    .badge-vid { background:#fee2e2; color:#991b1b; }
    .badge-lnk { background:#d1fae5; color:#065f46; }
    .badge-img { background:#fef3c7; color:#92400e; }
    .badge-oth { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Recursos: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->nombre_completo }}
        &nbsp;·&nbsp; Estudiante: {{ $estudiante->nombre_completo }}
        &nbsp;·&nbsp; {{ $recursos->count() }} recurso(s) &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@forelse($recursos as $recurso)
<div class="card">
    <div class="card-title">
        {{ $recurso->titulo }}
        @php $tipo = $recurso->tipo ?? 'otro'; @endphp
        @if($tipo === 'documento') <span class="badge badge-doc">Documento</span>
        @elseif($tipo === 'video') <span class="badge badge-vid">Video</span>
        @elseif($tipo === 'enlace') <span class="badge badge-lnk">Enlace</span>
        @elseif($tipo === 'imagen') <span class="badge badge-img">Imagen</span>
        @else <span class="badge badge-oth">Otro</span>
        @endif
    </div>
    <div class="card-meta">Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }} &nbsp;·&nbsp; {{ $recurso->created_at?->format('d/m/Y') ?? '' }}</div>
    @if($recurso->descripcion)
    <div class="card-desc">{{ \Illuminate\Support\Str::limit($recurso->descripcion, 200) }}</div>
    @endif
    @if($recurso->url)
    <div class="card-url">{{ $recurso->url }}</div>
    @endif
</div>
@empty
    <p style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay recursos publicados.</p>
@endforelse

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
