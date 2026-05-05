<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 5px; font-size:8pt; border:1px solid #e5e7eb; vertical-align:middle; }
    tbody td.left { text-align:left; }
    tbody td.center { text-align:center; }
    .badge { display:inline-block; border-radius:20px; padding:.15rem .5rem; font-size:7pt; font-weight:700;
             background:#dbeafe; color:#1d4ed8; }
    .pub  { color:#065f46; font-weight:700; }
    .priv { color:#9ca3af; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Recursos de Clase</h1>
    <p>
        {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }} &nbsp;·&nbsp;
        {{ $asignacion->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        Docente: {{ $docente->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </p>
</div>

@if($recursos->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">No hay recursos registrados.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="left" style="min-width:160px;">Título</th>
            <th class="left" style="min-width:80px;">Descripción</th>
            <th style="width:65px;">Tipo</th>
            <th style="width:55px;">Publicado</th>
            <th style="width:60px;">Fecha</th>
        </tr>
    </thead>
    <tbody>
    @foreach($recursos as $i => $rec)
    <tr>
        <td class="center" style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="left" style="font-weight:600;">{{ $rec->titulo }}</td>
        <td class="left" style="font-size:7.5pt;color:#6b7280;">{{ \Illuminate\Support\Str::limit($rec->descripcion ?? '', 60) }}</td>
        <td class="center"><span class="badge">{{ ucfirst($rec->tipo ?? '—') }}</span></td>
        <td class="center {{ $rec->publicado ? 'pub' : 'priv' }}">
            {{ $rec->publicado ? 'Sí' : 'No' }}
        </td>
        <td class="center" style="font-size:7.5pt;color:#6b7280;">{{ $rec->created_at?->format('d/m/Y') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Recursos — {{ $asignacion->asignatura?->nombre ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total: {{ $recursos->count() }} recurso(s)
</div>
</body>
</html>
