<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Encuestas de Satisfacción</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1.2cm 1.5cm; }

.hdr { border:2px solid #4f46e5; border-radius:4px; margin-bottom:.8rem; overflow:hidden; }
.hdr-top { background:#4f46e5; color:#fff; text-align:center; font-size:6pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:7px 12px; display:flex; align-items:center; gap:10px; }
.logo-box { width:44px; height:44px; border-radius:5px; background:#4f46e5; color:#fff;
            font-size:14pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.inst-name { font-size:10pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7pt; color:#374151; margin-top:1px; }
.doc-title { text-align:center; font-size:11pt; font-weight:900; color:#4f46e5;
             text-transform:uppercase; margin:.4rem 0 .6rem; }
.doc-meta  { text-align:center; font-size:7pt; color:#6b7280; margin-bottom:.7rem; }

table { width:100%; border-collapse:collapse; font-size:7.5pt; }
thead tr { background:#4f46e5; color:#fff; }
thead th { padding:.35rem .5rem; text-align:left; font-weight:700; font-size:7pt; text-transform:uppercase; letter-spacing:.04em; }
tbody tr:nth-child(even) { background:#eef2ff; }
tbody tr:nth-child(odd)  { background:#fff; }
tbody td { padding:.3rem .5rem; border-bottom:1px solid #e5e7eb; vertical-align:top; }
.badge { display:inline-block; padding:.1rem .4rem; border-radius:3px; font-size:6.5pt; font-weight:700; }
.activa   { background:#d1fae5; color:#065f46; }
.inactiva { background:#f3f4f6; color:#374151; }
.footer { margin-top:.8rem; font-size:6.5pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:.4rem; }
</style>
</head>
<body>

<div class="hdr">
    <div class="hdr-top">{{ $inst }} — Sistema de Gestión Escolar</div>
    <div class="hdr-body">
        <div class="logo-box">E</div>
        <div>
            <div class="inst-name">{{ $inst }}</div>
            <div class="inst-sub">Encuestas de Satisfacción</div>
        </div>
    </div>
</div>

<div class="doc-title">Lista de Encuestas</div>
<div class="doc-meta">Generado el {{ now()->format('d/m/Y H:i') }} — Total: {{ $encuestas->count() }} encuestas</div>

<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:32%">Título</th>
            <th style="width:16%">Dirigida a</th>
            <th style="width:10%">Preguntas</th>
            <th style="width:12%">Participantes</th>
            <th style="width:12%">Cierre</th>
            <th style="width:10%">Estado</th>
        </tr>
    </thead>
    <tbody>
    @php $dirigidaLabels = ['padres' => 'Padres/Representantes', 'estudiantes' => 'Estudiantes', 'todos' => 'Todos']; @endphp
    @forelse($encuestas as $i => $enc)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><strong>{{ $enc->titulo }}</strong></td>
        <td>{{ $dirigidaLabels[$enc->dirigida_a] ?? $enc->dirigida_a }}</td>
        <td style="text-align:center;">{{ $enc->preguntas_count }}</td>
        <td style="text-align:center;">{{ $enc->totalParticipantes() }}</td>
        <td>{{ $enc->fecha_cierre?->format('d/m/Y') ?? '—' }}</td>
        <td><span class="badge {{ $enc->activo ? 'activa' : 'inactiva' }}">{{ $enc->activo ? 'Activa' : 'Inactiva' }}</span></td>
    </tr>
    @empty
    <tr>
        <td colspan="7" style="text-align:center;color:#9ca3af;padding:1rem;">
            No hay encuestas registradas.
        </td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">Documento generado automáticamente — {{ config('app.name') }}</div>
</body>
</html>
