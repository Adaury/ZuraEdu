<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Lista de Estudiantes — {{ $grupo->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.1cm 1.4cm; }

.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; display:flex; align-items:center; gap:12px; }
.logo-box { width:58px; height:58px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:14pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.logo-img  { height:55px; max-width:60px; object-fit:contain; }
.inst-name { font-size:12pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:2px; }

.doc-title { text-align:center; margin:.75rem 0 .3rem;
             font-size:12pt; font-weight:900; color:#1e3a6e; text-transform:uppercase; }
.doc-meta  { display:flex; justify-content:space-between; font-size:8pt; color:#6b7280;
             border-bottom:2px solid #1e3a6e; padding-bottom:.35rem; margin-bottom:.6rem; }

table { width:100%; border-collapse:collapse; }
thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:700;
           padding:5px 7px; text-align:left; letter-spacing:.02em; }
tbody td { font-size:8pt; padding:4.5px 7px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
tbody tr:nth-child(even) td { background:#f0f4ff; }

.num  { width:28px; text-align:center; color:#6b7280; font-size:7.5pt; }
.mat  { width:68px; font-family:monospace; font-size:7.5pt; color:#374151; }
.nom  { min-width:120px; }
.ced  { width:90px; font-family:monospace; font-size:7.5pt; }
.rep  { font-size:7.5pt; color:#374151; }
.firma-col { width:80px; }
.firma-box { border-bottom:1px solid #9ca3af; height:14px; }

.footer { margin-top:.75rem; display:flex; justify-content:space-between;
          border-top:1px solid #e5e7eb; padding-top:.3rem; font-size:7pt; color:#9ca3af; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $total    = $grupo->matriculas->count();
    $sy       = $grupo->schoolYear;
    $tutor    = $grupo->tutor;
@endphp

<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD</div>
    <div class="hdr-body">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
        @endif
        <div>
            <div class="inst-name">{{ $si }}</div>
            <div class="inst-sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo', '') }}</div>
        </div>
    </div>
</div>

<div class="doc-title">Lista Oficial de Estudiantes</div>
<div class="doc-meta">
    <span>
        <strong>Grado:</strong> {{ $grupo->grado->nombre ?? '—' }}
        &nbsp;·&nbsp;
        <strong>Sección:</strong> {{ $grupo->seccion->nombre ?? '—' }}
    </span>
    <span>
        <strong>Año:</strong> {{ $sy->nombre ?? '—' }}
        &nbsp;·&nbsp;
        <strong>Docente-guía:</strong> {{ $tutor ? $tutor->nombre_completo : '—' }}
        &nbsp;·&nbsp;
        <strong>Total:</strong> {{ $total }}
    </span>
</div>

<table>
    <thead>
        <tr>
            <th class="num">#</th>
            <th class="mat">Matrícula</th>
            <th class="nom">Apellidos, Nombre</th>
            <th class="ced">Cédula</th>
            <th class="rep">Representante</th>
            <th class="firma-col" style="text-align:center;">Firma</th>
        </tr>
    </thead>
    <tbody>
        @forelse($grupo->matriculas as $i => $mat)
        @php $est = $mat->estudiante; $rep = $est->representantes->first(); @endphp
        <tr>
            <td class="num">{{ $mat->numero_orden ?? ($i + 1) }}</td>
            <td class="mat">{{ $est->matricula ?? '—' }}</td>
            <td class="nom"><strong>{{ $est->apellidos ?? $est->apellido ?? '' }}</strong>, {{ $est->nombres ?? $est->nombre ?? '' }}</td>
            <td class="ced">{{ $est->cedula ?? '—' }}</td>
            <td class="rep">{{ $rep ? trim(($rep->nombres ?? '') . ' ' . ($rep->apellidos ?? '')) : '—' }}</td>
            <td class="firma-col"><div class="firma-box"></div></td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:1rem;color:#9ca3af;">Sin estudiantes matriculados.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
    <span>{{ $si }} · Lista oficial {{ $sy?->nombre }}</span>
</div>

</body>
</html>
