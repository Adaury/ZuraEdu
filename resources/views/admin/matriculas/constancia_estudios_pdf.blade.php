<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:10.5pt; color:#1a1a2e; }
@page { size:letter portrait; margin:2cm 2.5cm; }

.hdr { border:2.5px solid #1e3a6e; border-radius:4px; margin-bottom:1.5rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.18em; text-transform:uppercase; padding:4px 0 3px; }
.hdr-body { background:#fff; padding:10px 14px; display:flex; align-items:center; gap:14px; }
.logo-box { width:65px; height:65px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:16pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.inst-center { flex:1; text-align:center; }
.inst-name   { font-size:12pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub    { font-size:8pt; color:#374151; margin-top:3px; }

.doc-title { text-align:center; margin:1.5rem 0 .5rem; font-size:13pt; font-weight:900;
             color:#1e3a6e; text-transform:uppercase; letter-spacing:.1em;
             border-top:2px solid #1e3a6e; border-bottom:2px solid #1e3a6e; padding:.5rem 0; }
.doc-subtitle { text-align:center; font-size:8.5pt; color:#6b7280; margin-bottom:1.8rem; }

.cert-body { font-size:11pt; line-height:2.0; color:#1a1a2e; text-align:justify;
             margin-bottom:1.5rem; }
.cert-body strong { color:#1e3a6e; }

.datos-grid { border:1px solid #c7d6f0; border-radius:6px; overflow:hidden; margin:1.2rem 0; }
.dato-fila { display:flex; border-bottom:1px solid #e8eef8; }
.dato-fila:last-child { border-bottom:none; }
.dato-label { background:#f0f4ff; font-size:8pt; font-weight:700; color:#374151;
              padding:5px 12px; min-width:160px; text-transform:uppercase; letter-spacing:.03em; }
.dato-valor { font-size:9.5pt; padding:5px 12px; color:#1a1a2e; flex:1; }

.validez { background:#fef9c3; border:1px solid #fde68a; border-radius:4px; padding:8px 12px;
           font-size:8pt; color:#854d0e; margin-top:.8rem; }

.firmas { display:flex; gap:2cm; margin-top:2.5cm; }
.firma  { flex:1; text-align:center; }
.firma .linea { border-top:1.5px solid #1e3a6e; padding-top:6px; font-size:8.5pt; color:#374151; margin-top:2cm; }
.firma .nombre { font-weight:700; font-size:9pt; }

.sello { width:2.5cm; height:2.5cm; border:2px dashed #1e3a6e; border-radius:50%;
         margin:1.5cm auto 0; display:flex; align-items:center; justify-content:center;
         font-size:7pt; color:#a0aec0; text-align:center; }

.footer { border-top:1px solid #c7d6f0; padding-top:8px; margin-top:1.5rem;
          display:flex; justify-content:space-between; font-size:7.5pt; color:#9ca3af; }
</style>
</head>
<body>

{{-- Encabezado --}}
<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación (MINERD)</div>
    <div class="hdr-body">
        <div class="logo-box">{{ strtoupper(substr($si,0,2)) }}</div>
        <div class="inst-center">
            <div class="inst-name">{{ $si }}</div>
            @if($cod)<div class="inst-sub">Código: {{ $cod }}</div>@endif
            @if($tel)<div class="inst-sub">Tel.: {{ $tel }}</div>@endif
        </div>
        <div class="logo-box">{{ strtoupper(substr($si,0,2)) }}</div>
    </div>
</div>

<div class="doc-title">Constancia de Estudios</div>
<div class="doc-subtitle">Año Escolar {{ $sy?->nombre ?? '' }}</div>

{{-- Cuerpo --}}
@php
    $est  = $matricula->estudiante;
    $grp  = $matricula->grupo;
    $grado= $grp->grado->nombre ?? '';
    $secc = $grp->seccion->nombre ?? '';
    $rep  = $est->representantes->first();
@endphp

<div class="cert-body">
&nbsp;&nbsp;&nbsp;&nbsp;Quien suscribe, Director/a del Centro Educativo <strong>{{ strtoupper($si) }}</strong>,
hace constar que el/la joven <strong>{{ strtoupper($est->nombre_completo) }}</strong>,
portador/a de la cédula de identidad Nº <strong>{{ $est->cedula ?? '_______________' }}</strong>,
<strong>ES ESTUDIANTE ACTIVO/A</strong> de esta institución educativa, cursando actualmente
el <strong>{{ $grado }} ({{ $secc }})</strong> del Nivel Secundario, durante el
Año Escolar <strong>{{ $sy?->nombre ?? '' }}</strong>.
</div>

<div class="datos-grid">
    <div class="dato-fila">
        <div class="dato-label">Nombres y Apellidos</div>
        <div class="dato-valor">{{ $est->nombre_completo }}</div>
    </div>
    <div class="dato-fila">
        <div class="dato-label">Cédula / Documento</div>
        <div class="dato-valor">{{ $est->cedula ?? '—' }}</div>
    </div>
    <div class="dato-fila">
        <div class="dato-label">No. Matrícula</div>
        <div class="dato-valor">{{ $est->matricula ?? '—' }}</div>
    </div>
    <div class="dato-fila">
        <div class="dato-label">Grado / Sección</div>
        <div class="dato-valor">{{ $grado }} — {{ $secc }}</div>
    </div>
    <div class="dato-fila">
        <div class="dato-label">Modalidad</div>
        <div class="dato-valor">{{ $config?->nivel_educativo ?? 'Nivel Secundario' }}</div>
    </div>
    <div class="dato-fila">
        <div class="dato-label">Estado</div>
        <div class="dato-valor"><strong style="color:#15803d;">ACTIVO/A — ESTUDIA REGULARMENTE</strong></div>
    </div>
    @if($rep)
    <div class="dato-fila">
        <div class="dato-label">Representante</div>
        <div class="dato-valor">{{ $rep->nombre_completo }}</div>
    </div>
    @endif
    <div class="dato-fila">
        <div class="dato-label">Fecha de expedición</div>
        <div class="dato-valor">{{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}</div>
    </div>
</div>

<div class="validez">
    <strong>⚠ VÁLIDA POR 30 DÍAS</strong> a partir de su fecha de expedición.
    Esta constancia es emitida a solicitud del interesado y solo tiene validez con sello y firma originales del director/a del centro.
</div>

<div class="firmas">
    <div class="firma">
        <div class="linea">
            <div class="nombre">{{ $dir ?: 'Director/a del Centro' }}</div>
            Director/a
        </div>
    </div>
    <div class="firma">
        <div class="sello">SELLO<br>OFICIAL</div>
    </div>
    <div class="firma">
        <div class="linea">
            Secretaria/o Académica/o
        </div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} — Constancia de Estudios</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
