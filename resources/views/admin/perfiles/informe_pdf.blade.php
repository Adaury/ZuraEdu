<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe Académico — {{ $estudiante->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.3cm 1.6cm; }

.hdr { border:2.5px solid #1e3a6e; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.18em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:9px 13px; display:flex; align-items:center; gap:12px; }
.logo-box { width:55px; height:55px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:13pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:53px; max-width:57px; object-fit:contain; }
.inst-name { font-size:12pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:1px; }

.doc-title { text-align:center; font-size:13pt; font-weight:900; color:#1e3a6e;
             text-transform:uppercase; margin:.6rem 0 .2rem; }
.doc-sub   { text-align:center; font-size:8.5pt; color:#6b7280; margin-bottom:.75rem; }

.est-box { border:1.5px solid #1e3a6e; border-radius:6px; padding:.75rem 1rem;
           background:#f8faff; margin-bottom:1rem; }
.est-name { font-size:12pt; font-weight:900; color:#1e3a6e; }
.est-meta { font-size:8pt; color:#374151; margin-top:3px; }

.year-block { margin-bottom:1.1rem; }
.year-title { background:#1e3a6e; color:#fff; font-size:9pt; font-weight:800;
              padding:5px 10px; border-radius:5px 5px 0 0; display:flex; justify-content:space-between; }
.year-body  { border:1px solid #d1d5db; border-radius:0 0 5px 5px; padding:.6rem .85rem; }

.stats-row { display:flex; gap:1rem; margin-bottom:.6rem; font-size:8pt; flex-wrap:wrap; }
.stat-chip { background:#f1f5f9; border-radius:5px; padding:.3rem .65rem; }
.stat-chip b { color:#1e3a6e; }

table { width:100%; border-collapse:collapse; margin-top:.3rem; }
th { background:#e0e7ff; color:#374151; font-size:7.5pt; font-weight:700;
     padding:4px 7px; text-align:left; border-bottom:1.5px solid #c7d2fe; }
td { font-size:8pt; padding:3.5px 7px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
.ap { color:#065f46; font-weight:700; }
.rp { color:#991b1b; font-weight:700; }

.footer { margin-top:1rem; display:flex; justify-content:space-between;
          font-size:7pt; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.3rem; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $rep      = $estudiante->representantes->first();
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
            <div class="inst-sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo','') }}</div>
        </div>
    </div>
</div>

<div class="doc-title">Informe Académico del Estudiante</div>
<div class="doc-sub">Historial completo · Generado el {{ now()->format('d/m/Y') }}</div>

<div class="est-box">
    <div class="est-name">{{ strtoupper($estudiante->nombre_completo ?? $estudiante->nombres . ' ' . $estudiante->apellidos) }}</div>
    <div class="est-meta">
        Cédula: <strong>{{ $estudiante->cedula ?? '—' }}</strong>
        &nbsp;·&nbsp; Matrícula: <strong>{{ $estudiante->matricula ?? '—' }}</strong>
        @if($rep)
        &nbsp;·&nbsp; Representante: <strong>{{ $rep->nombres }} {{ $rep->apellidos }}</strong>
        @endif
    </div>
</div>

@forelse($historial as $h)
<div class="year-block">
    <div class="year-title">
        <span>{{ $h['schoolYear']->nombre ?? '—' }}</span>
        <span>{{ $h['matricula']->grupo?->grado?->nombre ?? '' }} {{ $h['matricula']->grupo?->seccion?->nombre ?? '' }}</span>
    </div>
    <div class="year-body">
        <div class="stats-row">
            <div class="stat-chip">Promedio: <b>{{ $h['promedio'] ? number_format($h['promedio'],1) : '—' }}</b></div>
            <div class="stat-chip">Aprobadas: <b class="ap">{{ $h['aprobadas'] }}</b></div>
            <div class="stat-chip">Reprobadas: <b class="rp">{{ $h['reprobadas'] }}</b></div>
            @if($h['asistencia'] !== null)
            <div class="stat-chip">Asistencia: <b>{{ $h['asistencia'] }}%</b></div>
            @endif
        </div>

        @if($h['califs']->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th style="width:60px;text-align:center;">Nota Final</th>
                    <th style="width:70px;text-align:center;">Situación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($h['califs']->sortBy(fn($c) => $c->asignacion?->asignatura?->nombre) as $cal)
                <tr>
                    <td>{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
                    <td style="text-align:center;font-weight:700;{{ $cal->nota_final >= 65 ? 'color:#065f46;' : ($cal->nota_final !== null ? 'color:#991b1b;' : '') }}">
                        {{ $cal->nota_final !== null ? number_format($cal->nota_final,1) : '—' }}
                    </td>
                    <td style="text-align:center;">
                        @if($cal->situacion === 'A')
                            <span class="ap">Aprobado</span>
                        @elseif($cal->situacion === 'R')
                            <span class="rp">Reprobado</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="font-size:8pt;color:#9ca3af;padding:.25rem 0;">Sin calificaciones registradas.</div>
        @endif
    </div>
</div>
@empty
<p style="text-align:center;color:#9ca3af;padding:1.5rem;">Sin historial académico registrado.</p>
@endforelse

<div class="footer">
    <span>{{ $si }} · Informe generado por SGE · {{ now()->format('d/m/Y H:i') }}</span>
    <span>Confidencial — uso interno</span>
</div>

</body>
</html>
