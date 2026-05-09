<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Incidentes Médicos</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1.2cm 1.5cm; }

.hdr { border:2px solid #b45309; border-radius:4px; margin-bottom:.8rem; overflow:hidden; }
.hdr-top { background:#b45309; color:#fff; text-align:center; font-size:6pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:7px 12px; display:flex; align-items:center; gap:10px; }
.logo-box { width:44px; height:44px; border-radius:5px; background:#b45309; color:#fff;
            font-size:11pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.inst-name { font-size:10pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7pt; color:#374151; margin-top:1px; }
.doc-title { text-align:center; font-size:11pt; font-weight:900; color:#b45309;
             text-transform:uppercase; margin:.4rem 0 .6rem; }
.doc-meta  { text-align:center; font-size:7pt; color:#6b7280; margin-bottom:.7rem; }

table { width:100%; border-collapse:collapse; font-size:7.5pt; }
thead tr { background:#b45309; color:#fff; }
thead th { padding:.35rem .5rem; text-align:left; font-weight:700; font-size:7pt; text-transform:uppercase; letter-spacing:.04em; }
tbody tr:nth-child(even) { background:#fef3c7; }
tbody tr:nth-child(odd)  { background:#fff; }
tbody td { padding:.3rem .5rem; border-bottom:1px solid #e5e7eb; vertical-align:top; }
.tipo-badge { display:inline-block; padding:.1rem .4rem; border-radius:3px; font-size:6.5pt; font-weight:700; }
.tipo-accidente { background:#fee2e2; color:#991b1b; }
.tipo-enfermedad{ background:#fef9c3; color:#92400e; }
.tipo-alergia   { background:#d1fae5; color:#065f46; }
.tipo-otro      { background:#f3f4f6; color:#374151; }
.footer { margin-top:.8rem; font-size:6.5pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:.4rem; }
.desc { color:#374151; font-size:7pt; max-width:180px; }
</style>
</head>
<body>

<div class="hdr">
    <div class="hdr-top">{{ $inst }} — Sistema de Gestión Escolar</div>
    <div class="hdr-body">
        <div class="logo-box"><i>+</i></div>
        <div>
            <div class="inst-name">{{ $inst }}</div>
            <div class="inst-sub">Registro de Incidentes Médicos</div>
        </div>
    </div>
</div>

<div class="doc-title">Incidentes Médicos</div>
<div class="doc-meta">Generado el {{ now()->format('d/m/Y H:i') }} — Total: {{ $incidentes->count() }} registros</div>

<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:7%">Fecha</th>
            <th style="width:18%">Estudiante</th>
            <th style="width:8%">Tipo</th>
            <th style="width:32%">Descripción</th>
            <th style="width:25%">Acción Tomada</th>
            <th style="width:7%">Remitido A</th>
        </tr>
    </thead>
    <tbody>
    @forelse($incidentes as $i => $inc)
    @php $tipoKey = strtolower($inc->tipo); @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $inc->fecha?->format('d/m/Y') ?? '—' }}</td>
        <td><strong>{{ $inc->estudiante?->nombre_completo ?? '—' }}</strong></td>
        <td>
            <span class="tipo-badge tipo-{{ $tipoKey }}">
                {{ $tipos[$tipoKey]['label'] ?? ucfirst($tipoKey) }}
            </span>
        </td>
        <td class="desc">{{ $inc->descripcion }}</td>
        <td class="desc">{{ $inc->accion_tomada }}</td>
        <td>{{ $inc->remitido_a ?? '—' }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="7" style="text-align:center;color:#9ca3af;padding:1rem;">
            No hay incidentes registrados.
        </td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">Documento generado automáticamente — {{ config('app.name') }}</div>
</body>
</html>
