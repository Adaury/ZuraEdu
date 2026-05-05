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
    .card { border:1px solid #e2e8f0; border-radius:4px; padding:7px 8px; margin-bottom:8px; }
    .card-title { font-size:10px; font-weight:700; color:#1e3a6e; margin-bottom:3px; }
    .card-meta  { font-size:7px; color:#64748b; margin-bottom:4px; }
    .card-body  { font-size:8px; color:#334155; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; margin-left:4px; }
    .badge-todos      { background:#dbeafe; color:#1e40af; }
    .badge-estudiantes{ background:#d1fae5; color:#065f46; }
    .badge-grupo      { background:#fef3c7; color:#92400e; }
    .badge-general    { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Comunicados</h2>
    <p>
        Estudiante: {{ $estudiante->nombre_completo }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ $comunicados->count() }} comunicado(s) &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@forelse($comunicados as $com)
<div class="card">
    <div class="card-title">
        {{ $com->titulo }}
        @php $tipo = $com->tipo_destinatarios ?? 'general'; @endphp
        <span class="badge badge-{{ $tipo === 'todos' ? 'todos' : ($tipo === 'estudiantes' ? 'estudiantes' : ($tipo === 'grupo' ? 'grupo' : 'general')) }}">
            {{ ucfirst($tipo) }}
        </span>
    </div>
    <div class="card-meta">
        Publicado: {{ $com->published_at?->format('d/m/Y') ?? '—' }}
        &nbsp;·&nbsp; Por: {{ $com->autor?->name ?? '—' }}
    </div>
    <div class="card-body">{{ \Illuminate\Support\Str::limit(strip_tags($com->contenido ?? ''), 300) }}</div>
</div>
@empty
    <p style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay comunicados disponibles.</p>
@endforelse

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
