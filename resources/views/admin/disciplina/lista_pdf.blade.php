<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Disciplina</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1.2cm 1.5cm; }

.hdr { border:2px solid #7c3aed; border-radius:4px; margin-bottom:.8rem; overflow:hidden; }
.hdr-top { background:#7c3aed; color:#fff; text-align:center; font-size:6pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:7px 12px; display:flex; align-items:center; gap:10px; }
.logo-box { width:44px; height:44px; border-radius:5px; background:#7c3aed; color:#fff;
            font-size:14pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.inst-name { font-size:10pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7pt; color:#374151; margin-top:1px; }
.doc-title { text-align:center; font-size:11pt; font-weight:900; color:#7c3aed;
             text-transform:uppercase; margin:.4rem 0 .6rem; }
.doc-meta  { text-align:center; font-size:7pt; color:#6b7280; margin-bottom:.7rem; }

table { width:100%; border-collapse:collapse; font-size:7.5pt; }
thead tr { background:#7c3aed; color:#fff; }
thead th { padding:.35rem .5rem; text-align:left; font-weight:700; font-size:7pt; text-transform:uppercase; letter-spacing:.04em; }
tbody tr:nth-child(even) { background:#f5f3ff; }
tbody tr:nth-child(odd)  { background:#fff; }
tbody td { padding:.3rem .5rem; border-bottom:1px solid #e5e7eb; vertical-align:top; }
.badge { display:inline-block; padding:.1rem .4rem; border-radius:3px; font-size:6.5pt; font-weight:700; }
.resuelto   { background:#d1fae5; color:#065f46; }
.pendiente  { background:#fee2e2; color:#991b1b; }
.desc { color:#374151; font-size:7pt; }
.footer { margin-top:.8rem; font-size:6.5pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:.4rem; }
</style>
</head>
<body>

<div class="hdr">
    <div class="hdr-top">{{ $inst }} — Sistema de Gestión Escolar</div>
    <div class="hdr-body">
        <div class="logo-box">D</div>
        <div>
            <div class="inst-name">{{ $inst }}</div>
            <div class="inst-sub">Registro de Disciplina Escolar</div>
        </div>
    </div>
</div>

<div class="doc-title">Registro de Disciplina</div>
<div class="doc-meta">Generado el {{ now()->format('d/m/Y H:i') }} — Total: {{ $faltas->count() }} registros</div>

<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:7%">Fecha</th>
            <th style="width:20%">Estudiante</th>
            <th style="width:10%">Tipo</th>
            <th style="width:32%">Descripción</th>
            <th style="width:18%">Docente</th>
            <th style="width:10%">Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($faltas as $i => $f)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $f->fecha?->format('d/m/Y') ?? '—' }}</td>
        <td><strong>{{ $f->estudiante?->nombre_completo ?? '—' }}</strong></td>
        <td>{{ ucfirst($f->tipo ?? '—') }}</td>
        <td class="desc">{{ $f->descripcion ?? '—' }}</td>
        <td>{{ $f->docente?->nombre_completo ?? '—' }}</td>
        <td><span class="badge {{ $f->resuelto ? 'resuelto' : 'pendiente' }}">{{ $f->resuelto ? 'Resuelto' : 'Pendiente' }}</span></td>
    </tr>
    @empty
    <tr>
        <td colspan="7" style="text-align:center;color:#9ca3af;padding:1rem;">
            No hay faltas registradas.
        </td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">Documento generado automáticamente — {{ config('app.name') }}</div>
</body>
</html>
