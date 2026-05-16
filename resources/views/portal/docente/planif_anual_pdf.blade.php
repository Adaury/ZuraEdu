<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $plan->titulo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8pt; color:#1a1a2e; }
@page { size:letter portrait; margin:.9cm 1.1cm; }

.hdr { border:2px solid #0284c7; border-radius:3px; margin-bottom:.6rem; overflow:hidden; }
.hdr-top { background:#0284c7; color:#fff; text-align:center; font-size:6pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:2px 0; }
.hdr-body { background:#fff; padding:5px 8px; display:flex; align-items:center; gap:8px; }
.logo-box { width:38px; height:38px; border-radius:5px; background:#0284c7; color:#fff;
            font-size:9pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.logo-img  { height:36px; max-width:40px; object-fit:contain; }
.inst-name { font-size:9.5pt; font-weight:900; color:#0284c7; }
.inst-sub  { font-size:6.5pt; color:#374151; }

.plan-title { text-align:center; font-size:10pt; font-weight:900; color:#0284c7; margin:.35rem 0 .1rem; }
.plan-meta  { display:flex; justify-content:space-between; font-size:7pt; color:#6b7280;
              border-bottom:1.5px solid #0284c7; padding-bottom:.25rem; margin-bottom:.6rem; }

.unidad-blk { border:1px solid #dbeafe; border-radius:4px; margin-bottom:.7rem; page-break-inside:avoid; }
.unidad-hdr { background:#0284c7; color:#fff; padding:5px 8px; display:flex; align-items:center; gap:6px; }
.unidad-num { background:#fff; color:#0284c7; width:20px; height:20px; border-radius:4px;
              font-size:8pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.unidad-titulo { font-size:8.5pt; font-weight:700; flex:1; }
.per-badge { background:#fff; color:#0284c7; border-radius:99px; padding:.1rem .5rem;
             font-size:6.5pt; font-weight:700; }

.unidad-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; }
.cell { padding:5px 7px; border-bottom:1px solid #dbeafe; }
.cell:nth-child(odd) { border-right:1px solid #dbeafe; }
.cell-full { padding:5px 7px; border-bottom:1px solid #dbeafe; }
.cell-lbl { font-size:6pt; font-weight:700; color:#0284c7; text-transform:uppercase;
            letter-spacing:.05em; margin-bottom:2px; }
.cell-val { font-size:7.5pt; color:#374151; white-space:pre-wrap; }

.comp-chip { display:inline-block; background:#e0f2fe; color:#0284c7; border-radius:99px;
             padding:.1rem .4rem; font-size:6pt; font-weight:700; margin:.15rem .15rem 0 0; }

.footer { margin-top:.5rem; display:flex; justify-content:space-between;
          font-size:6pt; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.2rem; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
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

<div class="plan-title">{{ $plan->titulo }}</div>
<div class="plan-meta">
    <span>
        <strong>Materia:</strong> {{ $asignacion->asignatura?->nombre ?? '—' }}
        &nbsp;·&nbsp;
        <strong>Docente:</strong> {{ $docente->nombre_completo }}
    </span>
    <span>
        <strong>Grupo:</strong> {{ $asignacion->grupo?->grado?->nombre ?? '' }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
        &nbsp;·&nbsp;
        <strong>Año:</strong> {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
        &nbsp;·&nbsp; {{ $plan->unidades->count() }} unidad(es)
    </span>
</div>

@if($plan->descripcion)
<div style="background:#f0f9ff;border-radius:3px;padding:5px 8px;margin-bottom:.6rem;font-size:7.5pt;color:#374151;border-left:3px solid #0284c7;">
    {{ $plan->descripcion }}
</div>
@endif

@foreach($plan->unidades as $u)
<div class="unidad-blk">
    <div class="unidad-hdr">
        <div class="unidad-num">{{ $u->numero }}</div>
        <div class="unidad-titulo">{{ $u->titulo }}</div>
        @if($u->periodo)<span class="per-badge">{{ $u->periodo }}</span>@endif
        @if($u->semanas)<span style="font-size:6.5pt;opacity:.85;">{{ $u->semanas }} sem.</span>@endif
        @if($u->fecha_inicio || $u->fecha_fin)
        <span style="font-size:6.5pt;opacity:.85;">
            {{ $u->fecha_inicio?->format('d/m/Y') ?? '?' }} → {{ $u->fecha_fin?->format('d/m/Y') ?? '?' }}
        </span>
        @endif
    </div>

    <div class="unidad-grid">
        @if($u->objetivos)
        <div class="cell">
            <div class="cell-lbl">Objetivos</div>
            <div class="cell-val">{{ $u->objetivos }}</div>
        </div>
        @endif
        @if($u->indicadores)
        <div class="cell">
            <div class="cell-lbl">Indicadores de Logro</div>
            <div class="cell-val">{{ $u->indicadores }}</div>
        </div>
        @endif
        @if($u->contenidos)
        <div class="cell">
            <div class="cell-lbl">Contenidos / Temas</div>
            <div class="cell-val">{{ $u->contenidos }}</div>
        </div>
        @endif
        @if($u->estrategias)
        <div class="cell">
            <div class="cell-lbl">Estrategias / Actividades</div>
            <div class="cell-val">{{ $u->estrategias }}</div>
        </div>
        @endif
        @if($u->recursos)
        <div class="cell">
            <div class="cell-lbl">Recursos</div>
            <div class="cell-val">{{ $u->recursos }}</div>
        </div>
        @endif
        @if($u->evaluacion)
        <div class="cell">
            <div class="cell-lbl">Evaluación / Instrumentos</div>
            <div class="cell-val">{{ $u->evaluacion }}</div>
        </div>
        @endif
    </div>

    @if($u->competencias && count($u->competencias))
    <div class="cell-full">
        <div class="cell-lbl">Competencias</div>
        <div style="padding-top:2px;">
            @foreach($u->competencias as $comp)
            <span class="comp-chip">{{ $comp }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endforeach

@if($plan->unidades->isEmpty())
<div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:8pt;">Sin unidades registradas.</div>
@endif

<div style="margin-top:1.2rem;display:flex;justify-content:space-around;">
    <div style="text-align:center;width:160px;">
        <div style="border-top:1px solid #374151;margin-bottom:.2rem;"></div>
        <div style="font-size:7pt;font-weight:700;">{{ $docente->nombre_completo }}</div>
        <div style="font-size:6pt;color:#6b7280;">Firma del Docente</div>
    </div>
    <div style="text-align:center;width:160px;">
        <div style="border-top:1px solid #374151;margin-bottom:.2rem;"></div>
        <div style="font-size:7pt;font-weight:700;">{{ \App\Models\ConfigInstitucional::get('nombre_director','') ?: '________________________________' }}</div>
        <div style="font-size:6pt;color:#6b7280;">Visto Bueno — Director/a</div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} · Planificación Anual por Unidades · SGE · {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
