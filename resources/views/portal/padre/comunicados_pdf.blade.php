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
    .comunicado { border:1px solid #e2e8f0; border-radius:4px; padding:8px 10px; margin-bottom:7px; }
    .comunicado.alt { background:#f8faff; }
    .titulo { font-weight:700; font-size:10px; color:#1e3a6e; margin-bottom:3px; }
    .meta   { font-size:7.5px; color:#6b7280; margin-bottom:4px; }
    .contenido { font-size:8.5px; color:#374151; line-height:1.5; }
    .badge { padding:1px 6px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-todos    { background:#dbeafe; color:#1e40af; }
    .badge-padres   { background:#d1fae5; color:#065f46; }
    .badge-docentes { background:#fef3c7; color:#92400e; }
    .badge-general  { background:#f3f4f6; color:#6b7280; }
    .empty  { text-align:center; padding:20px; color:#94a3b8; font-style:italic; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Comunicados</h2>
    <p>Total: {{ $comunicados->count() }} comunicados &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

@if($comunicados->isEmpty())
    <p class="empty">No hay comunicados publicados.</p>
@else
    @foreach($comunicados as $i => $com)
    <div class="comunicado {{ $i % 2 === 1 ? 'alt' : '' }}">
        <div class="titulo">
            @php $dest = $com->destinatario ?? 'general'; @endphp
            @if($dest === 'todos')
                <span class="badge badge-todos">Todos</span>
            @elseif($dest === 'padres')
                <span class="badge badge-padres">Padres</span>
            @elseif($dest === 'docentes')
                <span class="badge badge-docentes">Docentes</span>
            @else
                <span class="badge badge-general">General</span>
            @endif
            {{ $com->titulo }}
        </div>
        <div class="meta">
            Publicado: {{ $com->published_at?->format('d/m/Y') ?? $com->created_at?->format('d/m/Y') }}
            @if($com->autor) &nbsp;·&nbsp; Por: {{ $com->autor->name }} @endif
        </div>
        @if($com->contenido)
        <div class="contenido">{{ Str::limit(strip_tags($com->contenido), 300) }}</div>
        @endif
    </div>
    @endforeach
@endif

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
