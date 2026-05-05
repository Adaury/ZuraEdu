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
    .recurso { border:1px solid #e2e8f0; border-radius:4px; padding:7px 10px; margin-bottom:6px; }
    .recurso.alt { background:#f8faff; }
    .titulo { font-weight:700; font-size:10px; color:#1e3a6e; }
    .meta   { font-size:7.5px; color:#6b7280; margin-top:2px; }
    .desc   { font-size:8.5px; margin-top:4px; color:#374151; }
    .url    { font-size:7.5px; color:#2563eb; word-break:break-all; margin-top:3px; }
    .badge  { padding:1px 5px; border-radius:3px; font-size:7px; font-weight:600; margin-right:4px; }
    .badge-doc  { background:#dbeafe; color:#1e40af; }
    .badge-vid  { background:#fee2e2; color:#991b1b; }
    .badge-link { background:#d1fae5; color:#065f46; }
    .badge-img  { background:#fef3c7; color:#92400e; }
    .badge-otro { background:#f3f4f6; color:#6b7280; }
    .empty { text-align:center; padding:20px; color:#94a3b8; font-style:italic; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Recursos: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->nombre_completo }}
        &nbsp;·&nbsp; Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ $recursos->count() }} recurso(s) &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@if($recursos->isEmpty())
    <p class="empty">No hay recursos publicados para esta materia.</p>
@else
    @foreach($recursos as $i => $recurso)
    <div class="recurso {{ $i % 2 === 1 ? 'alt' : '' }}">
        <div class="titulo">
            @php $tipo = $recurso->tipo ?? 'otro'; @endphp
            @if($tipo === 'documento')
                <span class="badge badge-doc">Documento</span>
            @elseif($tipo === 'video')
                <span class="badge badge-vid">Video</span>
            @elseif($tipo === 'enlace')
                <span class="badge badge-link">Enlace</span>
            @elseif($tipo === 'imagen')
                <span class="badge badge-img">Imagen</span>
            @else
                <span class="badge badge-otro">Otro</span>
            @endif
            {{ $recurso->titulo }}
        </div>
        <div class="meta">Publicado: {{ $recurso->created_at?->format('d/m/Y') }}</div>
        @if($recurso->descripcion)
            <div class="desc">{{ $recurso->descripcion }}</div>
        @endif
        @if($recurso->url)
            <div class="url">{{ $recurso->url }}</div>
        @endif
    </div>
    @endforeach
@endif

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
