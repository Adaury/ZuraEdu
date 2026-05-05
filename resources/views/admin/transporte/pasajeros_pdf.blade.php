<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pasajeros — {{ $ruta->nombre }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.1cm 1.4cm; }

/* ── Encabezado institucional ──────────────────────────────────── */
.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:.9rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; display:flex; align-items:center; gap:12px; }
.logo-box { width:52px; height:52px; border-radius:6px; background:#1e3a6e; color:#fff;
            font-size:13pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.logo-img  { height:50px; max-width:56px; object-fit:contain; }
.inst-name { font-size:11.5pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:2px; }

/* ── Título documento ──────────────────────────────────────────── */
.doc-title { text-align:center; margin:.7rem 0 .3rem;
             font-size:12pt; font-weight:900; color:#1e3a6e; text-transform:uppercase; }
.doc-meta  { display:flex; justify-content:space-between; font-size:8pt; color:#6b7280;
             border-bottom:2px solid #1e3a6e; padding-bottom:.35rem; margin-bottom:.7rem; }

/* ── Ficha de ruta ─────────────────────────────────────────────── */
.ficha { background:#f0f4ff; border:1px solid #c7d2fe; border-radius:4px;
         padding:7px 10px; margin-bottom:.7rem; display:flex; flex-wrap:wrap; gap:4px 20px; }
.ficha-item { font-size:7.5pt; color:#374151; }
.ficha-item strong { color:#1e3a6e; }

/* ── Sección de paradas ────────────────────────────────────────── */
.seccion-titulo { background:#1e3a6e; color:#fff; font-size:8pt; font-weight:700;
                  padding:4px 8px; border-radius:3px; margin:.6rem 0 .35rem; }

/* ── Tabla estudiantes ─────────────────────────────────────────── */
table { width:100%; border-collapse:collapse; }
thead th { background:#374151; color:#fff; font-size:7.5pt; font-weight:700;
           padding:5px 7px; text-align:left; letter-spacing:.02em; }
tbody td { font-size:8pt; padding:4px 7px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
tbody tr:nth-child(even) td { background:#f8faff; }

.col-num  { width:26px; text-align:center; color:#6b7280; font-size:7.5pt; }
.col-mat  { width:70px; font-family:monospace; font-size:7.5pt; color:#374151; }
.col-nom  { min-width:130px; }
.col-tipo { width:70px; }
.col-firma{ width:80px; text-align:center; }
.firma-box{ border-bottom:1px solid #9ca3af; height:14px; margin:0 auto; width:70px; }

/* ── Badge tipo servicio ───────────────────────────────────────── */
.badge { display:inline-block; font-size:6.5pt; font-weight:700; padding:1.5px 5px;
         border-radius:20px; text-transform:uppercase; letter-spacing:.04em; }
.badge-ambos  { background:#d1fae5; color:#065f46; }
.badge-ida    { background:#dbeafe; color:#1e40af; }
.badge-vuelta { background:#ede9fe; color:#5b21b6; }

/* ── Paradas resumen ───────────────────────────────────────────── */
.paradas-lista { display:flex; flex-wrap:wrap; gap:4px 10px; margin-bottom:.5rem; }
.parada-chip   { background:#e0e7ff; color:#3730a3; font-size:7pt; font-weight:600;
                 padding:2px 7px; border-radius:10px; }
.parada-hora   { font-weight:400; color:#6366f1; }

/* ── Footer ────────────────────────────────────────────────────── */
.footer { margin-top:.75rem; display:flex; justify-content:space-between;
          border-top:1px solid #e5e7eb; padding-top:.3rem; font-size:7pt; color:#9ca3af; }

/* ── Totales ───────────────────────────────────────────────────── */
.resumen { background:#1e3a6e; color:#fff; font-size:8pt; font-weight:700;
           padding:5px 10px; border-radius:3px; margin-top:.5rem;
           display:flex; justify-content:space-between; }
</style>
</head>
<body>

@php
    $logoPath   = $logo ? public_path('storage/' . $logo) : null;
    $totalEst   = $ruta->estudiantesRuta->count();
    $ocupPct    = $ruta->capacidad > 0 ? round(($totalEst / $ruta->capacidad) * 100) : 0;
@endphp

{{-- Encabezado institucional --}}
<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD</div>
    <div class="hdr-body">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-box">{{ strtoupper(substr($inst, 0, 2)) }}</div>
        @endif
        <div>
            <div class="inst-name">{{ $inst }}</div>
            <div class="inst-sub">Lista de Pasajeros — Transporte Escolar</div>
        </div>
    </div>
</div>

{{-- Título --}}
<div class="doc-title">Lista de Pasajeros</div>
<div class="doc-meta">
    <span><strong>Ruta:</strong> {{ $ruta->nombre }}</span>
    <span>
        <strong>Total:</strong> {{ $totalEst }} / {{ $ruta->capacidad }}
        &nbsp;·&nbsp;
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
    </span>
</div>

{{-- Ficha de la ruta --}}
<div class="ficha">
    @if($ruta->conductor)
        <span class="ficha-item"><strong>Conductor:</strong> {{ $ruta->conductor }}</span>
    @endif
    @if($ruta->vehiculo)
        <span class="ficha-item"><strong>Vehículo:</strong> {{ $ruta->vehiculo }}</span>
    @endif
    <span class="ficha-item"><strong>Capacidad:</strong> {{ $ruta->capacidad }} lugares</span>
    <span class="ficha-item"><strong>Ocupación:</strong> {{ $totalEst }} ({{ $ocupPct }}%)</span>
    <span class="ficha-item"><strong>Estado:</strong> {{ $ruta->activo ? 'Activa' : 'Inactiva' }}</span>
    @if($ruta->descripcion)
        <span class="ficha-item" style="flex-basis:100%;"><strong>Descripción:</strong> {{ $ruta->descripcion }}</span>
    @endif
</div>

{{-- Paradas --}}
@if($ruta->paradas->count())
<p style="font-size:8pt;font-weight:700;color:#1e3a6e;margin-bottom:.3rem;">
    Paradas de la ruta ({{ $ruta->paradas->count() }}):
</p>
<div class="paradas-lista">
    @foreach($ruta->paradas as $p)
        <span class="parada-chip">
            {{ $p->orden }}. {{ $p->nombre }}
            @if($p->hora_estimada)
                <span class="parada-hora">— {{ \Carbon\Carbon::parse($p->hora_estimada)->format('g:i A') }}</span>
            @endif
        </span>
    @endforeach
</div>
@endif

{{-- Estudiantes por parada --}}
@foreach($porParada as $parada)
    @if($parada->pasajeros->count())
    <div class="seccion-titulo">
        Parada {{ $parada->orden }}: {{ $parada->nombre }}
        @if($parada->hora_estimada)
            — {{ \Carbon\Carbon::parse($parada->hora_estimada)->format('g:i A') }}
        @endif
        ({{ $parada->pasajeros->count() }} pasajero{{ $parada->pasajeros->count() !== 1 ? 's' : '' }})
    </div>
    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-mat">Matrícula</th>
                <th class="col-nom">Apellidos, Nombre</th>
                <th class="col-tipo">Servicio</th>
                <th class="col-firma">Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parada->pasajeros as $i => $er)
            @php $est = $er->estudiante; @endphp
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-mat">{{ $est?->numero_matricula ?? '—' }}</td>
                <td class="col-nom">
                    <strong>{{ $est?->apellidos ?? '' }}</strong>{{ $est?->apellidos && $est?->nombres ? ', ' : '' }}{{ $est?->nombres ?? ($est?->nombre ?? '') }}
                </td>
                <td class="col-tipo">
                    <span class="badge badge-{{ $er->tipo }}">{{ $er->tipo_label }}</span>
                </td>
                <td class="col-firma"><div class="firma-box"></div></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
@endforeach

{{-- Sin parada asignada --}}
@if($sinParada->count())
<div class="seccion-titulo">
    Sin parada asignada ({{ $sinParada->count() }} pasajero{{ $sinParada->count() !== 1 ? 's' : '' }})
</div>
<table>
    <thead>
        <tr>
            <th class="col-num">#</th>
            <th class="col-mat">Matrícula</th>
            <th class="col-nom">Apellidos, Nombre</th>
            <th class="col-tipo">Servicio</th>
            <th class="col-firma">Firma</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sinParada as $i => $er)
        @php $est = $er->estudiante; @endphp
        <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td class="col-mat">{{ $est?->numero_matricula ?? '—' }}</td>
            <td class="col-nom">
                <strong>{{ $est?->apellidos ?? '' }}</strong>{{ $est?->apellidos && $est?->nombres ? ', ' : '' }}{{ $est?->nombres ?? ($est?->nombre ?? '') }}
            </td>
            <td class="col-tipo">
                <span class="badge badge-{{ $er->tipo }}">{{ $er->tipo_label }}</span>
            </td>
            <td class="col-firma"><div class="firma-box"></div></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Resumen totales --}}
@if($totalEst > 0)
<div class="resumen">
    <span>Total pasajeros: {{ $totalEst }}</span>
    <span>Capacidad del vehículo: {{ $ruta->capacidad }}</span>
    <span>Disponibles: {{ max(0, $ruta->capacidad - $totalEst) }}</span>
</div>
@endif

{{-- Footer --}}
<div class="footer">
    <span>{{ $inst }} · Lista oficial de pasajeros</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
