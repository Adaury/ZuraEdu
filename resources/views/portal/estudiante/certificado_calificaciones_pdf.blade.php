<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Certificado de Calificaciones</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.8cm 2.2cm; }

/* ── Encabezado ── */
.hdr { border:2.5px solid #1e3a6e; border-radius:4px; margin-bottom:1.2rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:6.5pt; font-weight:700;
           letter-spacing:.18em; text-transform:uppercase; padding:4px 0; }
.hdr-body { background:#fff; padding:9px 14px; display:flex; align-items:center; gap:14px; }
.logo-box { width:60px; height:60px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:15pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:58px; max-width:62px; object-fit:contain; }
.inst-center { flex:1; text-align:center; }
.inst-name { font-size:12pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:2px; }
.inst-cod  { font-size:7pt; color:#6b7280; margin-top:1px; }

/* ── Folio --*/
.folio-bar { background:#f0f4ff; border:1px solid #c7d6f0; border-radius:6px; padding:5px 10px;
             text-align:right; font-size:7pt; color:#4b5563; margin-bottom:1rem; }
.folio-bar strong { color:#1e3a6e; letter-spacing:.05em; }

/* ── Título ── */
.doc-title { text-align:center; margin:.7rem 0 .35rem;
    font-size:13pt; font-weight:900; color:#1e3a6e;
    text-transform:uppercase; letter-spacing:.1em;
    border-top:2px solid #1e3a6e; border-bottom:2px solid #1e3a6e; padding:.4rem 0; }

/* ── Datos estudiante ── */
.data-box { border:1.5px solid #1e3a6e; border-radius:6px; margin:.9rem 0;
            padding:.7rem 1rem; background:#f8faff; }
.data-row { display:flex; gap:1rem; margin-bottom:.3rem; font-size:9pt; }
.data-label { font-weight:700; color:#374151; min-width:125px; }
.data-value { color:#1e293b; border-bottom:1px solid #cbd5e1; flex:1; }

/* ── Tabla calificaciones ── */
.cal-table { width:100%; border-collapse:collapse; margin:1rem 0; font-size:8.5pt; }
.cal-table th { background:#1e3a6e; color:#fff; padding:5px 6px;
                text-align:center; font-size:8pt; letter-spacing:.02em; }
.cal-table th:first-child { text-align:left; }
.cal-table td { padding:4px 6px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
.cal-table td:first-child { font-weight:600; color:#1e293b; }
.cal-table td:not(:first-child) { text-align:center; }
.cal-table tr:nth-child(even) { background:#f8faff; }
.nota-final { font-weight:700; font-size:9pt; }
.sit-aprobado { color:#16a34a; font-weight:700; font-size:7.5pt; }
.sit-reprobado { color:#dc2626; font-weight:700; font-size:7.5pt; }

/* ── Resumen ── */
.resumen { display:flex; gap:1rem; margin:1rem 0; }
.resumen-box { flex:1; border:1.5px solid #c7d6f0; border-radius:6px;
               padding:.6rem .9rem; text-align:center; background:#f8faff; }
.resumen-val { font-size:15pt; font-weight:900; color:#1e3a6e; line-height:1; }
.resumen-lbl { font-size:7pt; color:#6b7280; margin-top:2px; }

/* ── Texto certificación ── */
.cert-body { font-size:9.5pt; line-height:1.7; text-align:justify; color:#1a1a2e; margin:.6rem 0; }
.cert-body strong { color:#1e3a6e; }

/* ── Firmas ── */
.sigs { margin-top:1.8rem; display:flex; justify-content:space-around; }
.sig-block { text-align:center; width:165px; }
.sig-space { height:44px; }
.sig-line { border-top:1.5px solid #374151; margin-bottom:.3rem; }
.sig-name { font-size:8pt; font-weight:700; color:#374151; }
.sig-role { font-size:7pt; color:#6b7280; }
.sello-area { width:72px; height:72px; border:1.5px dashed #9ca3af; border-radius:50%;
              margin:0 auto .4rem; display:flex; align-items:center; justify-content:center;
              color:#9ca3af; font-size:6.5pt; text-align:center; line-height:1.2; }

/* ── Pie ── */
.footer { margin-top:1.2rem; text-align:center; font-size:7pt; color:#9ca3af;
          border-top:1px solid #e5e7eb; padding-top:.35rem; }
</style>
</head>
<body>

@php
    $est      = $matricula->estudiante;
    $grupo    = $matricula->grupo;
    $sy       = $matricula->schoolYear;
    $grado    = $grupo?->grado?->nombre ?? '—';
    $seccion  = $grupo?->seccion?->nombre ?? '';
    $fechaHoy = \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $folio    = 'CERT-' . ($sy?->id ?? date('Y')) . '-' . str_pad($matricula->id, 5, '0', STR_PAD_LEFT);

    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;

    // Agrupar calificaciones MINERD
    $calAcad = $calificacionesAcademicas->sortBy(fn($c) => $c->asignacion?->asignatura?->nombre ?? '');

    // Agrupar calificaciones técnicas por asignatura si no hay MINERD
    $promedioGeneral = $calAcad->whereNotNull('nota_final')->avg('nota_final');
    if (! $promedioGeneral) {
        $promedioGeneral = $calificaciones->flatten()->whereNotNull('nota_final')->avg('nota_final');
    }
    $aprobadas  = $calAcad->where('nota_final', '>=', 60)->count();
    $reprobadas = $calAcad->where('nota_final', '<', 60)->count();
    $totalMat   = $calAcad->count();
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
            @if($cod)<div class="inst-cod">Código: {{ $cod }}</div>@endif
        </div>
    </div>
</div>

<div class="folio-bar">Folio: <strong>{{ $folio }}</strong> &nbsp;·&nbsp; Emitido: {{ now()->format('d/m/Y H:i') }}</div>

<div class="doc-title">Certificado de Calificaciones</div>

{{-- Datos del estudiante --}}
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
        <span class="data-label">Grado / Sección:</span>
        <span class="data-value">{{ $grado }} {{ $seccion }}</span>
    </div>
    <div class="data-row">
        <span class="data-label">Año Escolar:</span>
        <span class="data-value">{{ $sy?->nombre ?? '—' }}</span>
    </div>
</div>

{{-- Tabla de calificaciones --}}
@if($calAcad->isNotEmpty())
<table class="cal-table">
    <thead>
        <tr>
            <th style="width:38%;">Asignatura</th>
            <th>P1</th>
            <th>P2</th>
            <th>P3</th>
            <th>P4</th>
            <th>Final</th>
            <th>Situación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($calAcad as $cal)
        @php
            $notaFinal = $cal->nota_final;
            $aprobada  = $notaFinal !== null && $notaFinal >= 60;
        @endphp
        <tr>
            <td>{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $cal->p1 ?? '—' }}</td>
            <td>{{ $cal->p2 ?? '—' }}</td>
            <td>{{ $cal->p3 ?? '—' }}</td>
            <td>{{ $cal->p4 ?? '—' }}</td>
            <td class="nota-final" style="color:{{ $aprobada ? '#16a34a' : ($notaFinal !== null ? '#dc2626' : '#9ca3af') }};">
                {{ $notaFinal !== null ? number_format($notaFinal, 1) : '—' }}
            </td>
            <td>
                @if($notaFinal !== null)
                    <span class="{{ $aprobada ? 'sit-aprobado' : 'sit-reprobado' }}">
                        {{ $aprobada ? '✓ Aprobado' : '✗ Reprobado' }}
                    </span>
                @else
                    <span style="color:#9ca3af;font-size:7.5pt;">Pendiente</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@elseif($calificaciones->isNotEmpty())
{{-- Fallback: calificaciones técnicas --}}
<table class="cal-table">
    <thead>
        <tr>
            <th style="width:45%;">Asignatura</th>
            @foreach($periodos as $p)<th>P{{ $p->numero }}</th>@endforeach
            <th>Final</th>
        </tr>
    </thead>
    <tbody>
        @foreach($calificaciones as $pid => $calsPeriodo)
        @php $primeraCal = $calsPeriodo->first(); $asigNombre = $primeraCal?->asignacion?->asignatura?->nombre ?? '—'; @endphp
        <tr>
            <td>{{ $asigNombre }}</td>
            @foreach($periodos as $p)
            @php $c = $calsPeriodo->firstWhere('periodo_id', $p->id); @endphp
            <td>{{ $c?->nota_final ?? '—' }}</td>
            @endforeach
            <td class="nota-final">{{ $calsPeriodo->whereNotNull('nota_final')->avg('nota_final') ? number_format($calsPeriodo->whereNotNull('nota_final')->avg('nota_final'), 1) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center;color:#9ca3af;font-size:9pt;margin:1.5rem 0;">
    No hay calificaciones publicadas para este año escolar.
</p>
@endif

{{-- Resumen estadístico --}}
<div class="resumen">
    <div class="resumen-box">
        <div class="resumen-val">{{ $totalMat }}</div>
        <div class="resumen-lbl">Total Asignaturas</div>
    </div>
    <div class="resumen-box">
        <div class="resumen-val" style="color:#16a34a;">{{ $aprobadas }}</div>
        <div class="resumen-lbl">Aprobadas</div>
    </div>
    <div class="resumen-box">
        <div class="resumen-val" style="color:{{ $reprobadas > 0 ? '#dc2626' : '#16a34a' }};">{{ $reprobadas }}</div>
        <div class="resumen-lbl">Reprobadas</div>
    </div>
    <div class="resumen-box">
        <div class="resumen-val" style="color:{{ $promedioGeneral >= 70 ? '#16a34a' : ($promedioGeneral >= 60 ? '#d97706' : '#dc2626') }};">
            {{ $promedioGeneral ? number_format($promedioGeneral, 1) : '—' }}
        </div>
        <div class="resumen-lbl">Promedio General</div>
    </div>
</div>

<p class="cert-body">
    La Dirección del <strong>{{ $si }}</strong> certifica que las calificaciones presentadas en este
    documento corresponden a los registros oficiales del/la estudiante
    <strong>{{ strtoupper($est->nombre_completo ?? '') }}</strong>
    durante el año escolar <strong>{{ $sy?->nombre ?? '—' }}</strong>.
    Este certificado tiene validez oficial únicamente con el sello del centro.
</p>

<p style="font-size:9pt;color:#374151;text-align:right;">
    {{ \App\Models\ConfigInstitucional::get('ciudad', '') }}, a los {{ $fechaHoy }}.
</p>

{{-- Firmas --}}
<div class="sigs">
    <div class="sig-block">
        <div class="sig-space"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $dir ?: '________________________________' }}</div>
        <div class="sig-role">Director/a del Centro</div>
    </div>
    <div class="sig-block" style="text-align:center;">
        <div class="sello-area">Sello<br>del<br>Centro</div>
        <div style="font-size:7pt;color:#9ca3af;">Sello oficial</div>
    </div>
    <div class="sig-block">
        <div class="sig-space"></div>
        <div class="sig-line"></div>
        <div class="sig-name">Encargado/a de Registros</div>
        <div class="sig-role">Secretaría Académica</div>
    </div>
</div>

<div class="footer">
    {{ $si }} · Certificado de Calificaciones · Folio {{ $folio }} ·
    Generado {{ now()->format('d/m/Y H:i') }} · Este documento requiere sello oficial para ser válido.
</div>

</body>
</html>
