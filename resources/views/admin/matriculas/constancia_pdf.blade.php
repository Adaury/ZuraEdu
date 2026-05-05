<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Constancia de Matrícula</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:10pt; color:#1a1a2e; }
@page { size:letter portrait; margin:2cm 2.5cm; }

/* ── Encabezado ── */
.hdr { border:2.5px solid #1e3a6e; border-radius:4px; margin-bottom:1.5rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.18em; text-transform:uppercase; padding:4px 0 3px; }
.hdr-body { background:#fff; padding:10px 14px; display:flex; align-items:center; gap:14px; }
.logo-box { width:65px; height:65px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:16pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:62px; max-width:65px; object-fit:contain; }
.inst-center { flex:1; text-align:center; }
.inst-name   { font-size:13pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub    { font-size:8pt; color:#374151; margin-top:3px; }
.inst-cod    { font-size:7.5pt; color:#6b7280; margin-top:2px; }

/* ── Título ── */
.doc-title {
    text-align:center; margin:1.5rem 0 .5rem;
    font-size:14pt; font-weight:900; color:#1e3a6e;
    text-transform:uppercase; letter-spacing:.1em;
    border-top:2px solid #1e3a6e; border-bottom:2px solid #1e3a6e;
    padding:.45rem 0;
}

/* ── Cuerpo ── */
.body-text { font-size:10.5pt; line-height:1.75; text-align:justify; margin:1rem 0; }
.body-text strong { color:#1e3a6e; }

/* ── Cuadro de datos ── */
.data-box {
    border:1.5px solid #1e3a6e; border-radius:6px;
    margin:1.2rem 0; padding:.85rem 1.1rem;
    background:#f8faff;
}
.data-row { display:flex; gap:1rem; margin-bottom:.4rem; font-size:9.5pt; }
.data-label { font-weight:700; color:#374151; min-width:130px; }
.data-value { color:#1e293b; border-bottom:1px solid #cbd5e1; flex:1; }

/* ── Firmas ── */
.sigs { margin-top:2.5rem; display:flex; justify-content:space-around; }
.sig-block { text-align:center; width:180px; }
.sig-line { border-top:1.5px solid #374151; margin-bottom:.3rem; }
.sig-name { font-size:8.5pt; font-weight:700; color:#374151; }
.sig-role { font-size:7.5pt; color:#6b7280; }

/* ── Pie ── */
.footer { margin-top:1.5rem; text-align:center; font-size:7.5pt; color:#9ca3af;
          border-top:1px solid #e5e7eb; padding-top:.4rem; }

.sello-area { width:80px; height:80px; border:1.5px dashed #9ca3af; border-radius:50%;
              margin:0 auto .5rem; display:flex; align-items:center; justify-content:center;
              color:#9ca3af; font-size:7pt; text-align:center; line-height:1.2; }
</style>
</head>
<body>

@php
    $est       = $matricula->estudiante;
    $grupo     = $matricula->grupo;
    $sy        = $matricula->schoolYear;
    $grado     = $grupo?->grado?->nombre ?? '—';
    $seccion   = $grupo?->seccion?->nombre ?? '';
    $logoPath  = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $fechaHoy  = \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $rep       = $est->representantes->first();
@endphp

{{-- Encabezado institucional --}}
<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD</div>
    <div class="hdr-body">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
        @endif
        <div class="inst-center">
            <div class="inst-name">{{ $si }}</div>
            <div class="inst-sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo', '') }}</div>
            @if($cod)
            <div class="inst-cod">Código: {{ $cod }}</div>
            @endif
        </div>
    </div>
</div>

<div class="doc-title">Constancia de Matrícula</div>

{{-- Cuerpo del documento --}}
<p class="body-text">
    La Dirección del <strong>{{ $si }}</strong>
    @if($dir)hace constar que el/la estudiante <strong>{{ strtoupper($est->nombre_completo ?? $est->nombres . ' ' . $est->apellidos) }}</strong>
    @else
    hace constar que el/la estudiante <strong>{{ strtoupper($est->nombre_completo ?? $est->nombres . ' ' . $est->apellidos) }}</strong>
    @endif
    se encuentra <strong>debidamente matriculado/a</strong> en este centro educativo durante
    el año escolar <strong>{{ $sy?->nombre ?? '—' }}</strong>, en el nivel correspondiente
    al <strong>{{ $grado }} {{ $seccion }}</strong>.
</p>

<p class="body-text">
    Esta constancia se expide a solicitud del/la interesado/a para los fines legales y
    administrativos que estime conveniente.
</p>

{{-- Cuadro de datos del estudiante --}}
<div class="data-box">
    <div class="data-row">
        <span class="data-label">Nombres y Apellidos:</span>
        <span class="data-value">{{ strtoupper($est->nombre_completo ?? ($est->nombres . ' ' . $est->apellidos)) }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">Cédula / RNE:</span>
        <span class="data-value">{{ $est->cedula ?? '—' }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">Fecha de nacimiento:</span>
        <span class="data-value">{{ $est->fecha_nacimiento ? \Carbon\Carbon::parse($est->fecha_nacimiento)->format('d/m/Y') : '—' }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">No. de matrícula:</span>
        <span class="data-value">{{ $est->matricula ?? $matricula->id }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">Grado / Sección:</span>
        <span class="data-value">{{ $grado }} {{ $seccion }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">Año escolar:</span>
        <span class="data-value">{{ $sy?->nombre ?? '—' }}</span>
    </div>
    @if($rep)
    <div class="data-row">
        <span class="data-label">Representante:</span>
        <span class="data-value">{{ $rep->nombres }} {{ $rep->apellidos }}</span>
    </div>
    @endif
</div>

<p class="body-text" style="margin-top:.75rem;">
    Dado en {{ \App\Models\ConfigInstitucional::get('ciudad', '') }}, a los {{ $fechaHoy }}.
</p>

{{-- Firmas --}}
<div class="sigs">
    <div class="sig-block">
        <div style="height:50px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $dir ?: '________________________________' }}</div>
        <div class="sig-role">Director/a del Centro</div>
    </div>
    <div class="sig-block" style="text-align:center;">
        <div class="sello-area">Sello<br>del<br>Centro</div>
        <div style="font-size:7.5pt;color:#9ca3af;">Sello oficial</div>
    </div>
    <div class="sig-block">
        <div style="height:50px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $est->nombre_completo ?? '' }}</div>
        <div class="sig-role">Firma del Estudiante</div>
    </div>
</div>

<div class="footer">
    Documento generado por el Sistema de Gestión Escolar (SGE) · {{ now()->format('d/m/Y H:i') }}
    &nbsp;·&nbsp; Este documento tiene validez oficial con el sello del centro.
</div>

</body>
</html>
