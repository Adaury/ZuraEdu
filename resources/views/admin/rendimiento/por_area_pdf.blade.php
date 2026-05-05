<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
@page { size:letter portrait; margin:.9cm 1.1cm; }

.header { text-align:center; margin-bottom:14px; border-bottom:2px solid #1e3a6e; padding-bottom:8px; }
.header .inst  { font-size:12px; font-weight:bold; color:#1e3a6e; text-transform:uppercase; }
.header .titulo{ font-size:10px; font-weight:bold; color:#0f172a; margin-top:4px; }
.header .sub   { font-size:8px; color:#6b7280; margin-top:3px; }

.area-box { border:1px solid #e2e8f0; border-radius:6px; margin-bottom:16px; overflow:hidden; }
.area-header { padding:10px 14px; color:#fff; }
.area-header.acad { background:#1e3a6e; }
.area-header.tech { background:#c0392b; }
.area-header .area-title { font-size:11px; font-weight:700; }
.area-header .area-sub   { font-size:7.5px; opacity:.85; margin-top:2px; }

.stats-row { display:table; width:100%; padding:10px 14px; }
.stat-cell { display:table-cell; width:50%; vertical-align:middle; }
.stat-val  { font-size:28px; font-weight:900; line-height:1; }
.stat-lbl  { font-size:7px; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-top:2px; }

.bar-wrap { background:#f0f4f8; border-radius:4px; height:8px; margin:8px 14px; overflow:hidden; }
.bar-fill { height:100%; border-radius:4px; }

.badge-nivel { display:inline-block; padding:2px 8px; border-radius:10px; font-size:7.5px; font-weight:700; }
.badge-good { background:#d1fae5; color:#065f46; }
.badge-mid  { background:#fef3c7; color:#92400e; }
.badge-low  { background:#fee2e2; color:#991b1b; }

.comparativa { border:1px solid #e2e8f0; border-radius:6px; padding:12px 14px; margin-bottom:16px; }
.comparativa-title { font-size:9px; font-weight:700; margin-bottom:8px; color:#1e3a6e; }
.comp-row { display:table; width:100%; margin-bottom:6px; }
.comp-label { display:table-cell; width:110px; font-size:8px; font-weight:600; vertical-align:middle; }
.comp-bar-wrap { display:table-cell; vertical-align:middle; }
.comp-val { display:table-cell; width:30px; text-align:right; font-size:9px; font-weight:900; vertical-align:middle; }

.note-box { background:#eff6ff; border-left:3px solid #3b82f6; padding:6px 10px; margin-bottom:10px; font-size:7.5px; }

.footer { margin-top:10px; border-top:1px solid #e2e8f0; padding-top:6px;
          display:table; width:100%; font-size:7px; color:#94a3b8; }
.footer-l { display:table-cell; }
.footer-r { display:table-cell; text-align:right; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">RENDIMIENTO ACADÉMICO POR ÁREA</div>
    <div class="sub">Año Escolar: {{ $schoolYear->nombre }} — Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

{{-- Área Académica --}}
@php
    $promAcad  = $academica->promedio ? round($academica->promedio, 1) : null;
    $colorAcad = $promAcad >= 80 ? '#22c55e' : ($promAcad >= 70 ? '#f59e0b' : '#ef4444');
    $nivelAcad = $promAcad >= 80 ? 'Bueno' : ($promAcad >= 70 ? 'Regular' : 'Bajo');
    $clsAcad   = $promAcad >= 80 ? 'badge-good' : ($promAcad >= 70 ? 'badge-mid' : 'badge-low');

    $promTec  = $tecnica->promedio ? round($tecnica->promedio, 1) : null;
    $colorTec = $promTec >= 80 ? '#22c55e' : ($promTec >= 70 ? '#f59e0b' : '#ef4444');
    $nivelTec = $promTec >= 80 ? 'Bueno' : ($promTec >= 70 ? 'Regular' : 'Bajo');
    $clsTec   = $promTec >= 80 ? 'badge-good' : ($promTec >= 70 ? 'badge-mid' : 'badge-low');
@endphp

<div class="area-box">
    <div class="area-header acad">
        <div class="area-title">Área Académica</div>
        <div class="area-sub">Calificaciones de materias generales</div>
    </div>
    <div class="stats-row">
        <div class="stat-cell">
            <div class="stat-val" style="color:#1e3a6e;">{{ $promAcad ?? '—' }}</div>
            <div class="stat-lbl">Promedio general</div>
            @if($promAcad)
            <span class="badge-nivel {{ $clsAcad }}" style="margin-top:4px;">{{ $nivelAcad }}</span>
            @endif
        </div>
        <div class="stat-cell">
            <div class="stat-val" style="color:#374151;">{{ number_format($academica->total ?? 0) }}</div>
            <div class="stat-lbl">Calificaciones registradas</div>
        </div>
    </div>
    @if($promAcad)
    <div class="bar-wrap">
        <div class="bar-fill" style="width:{{ min(100,$promAcad) }}%;background:{{ $colorAcad }};"></div>
    </div>
    @else
    <p style="text-align:center;color:#9ca3af;font-size:7.5px;padding:6px 0 10px;">Sin calificaciones publicadas aún.</p>
    @endif
</div>

{{-- Área Técnica --}}
<div class="area-box">
    <div class="area-header tech">
        <div class="area-title">Área Técnica</div>
        <div class="area-sub">Calificaciones de especialidades técnicas</div>
    </div>
    <div class="stats-row">
        <div class="stat-cell">
            <div class="stat-val" style="color:#c0392b;">{{ $promTec ?? '—' }}</div>
            <div class="stat-lbl">Promedio general</div>
            @if($promTec)
            <span class="badge-nivel {{ $clsTec }}" style="margin-top:4px;">{{ $nivelTec }}</span>
            @endif
        </div>
        <div class="stat-cell">
            <div class="stat-val" style="color:#374151;">{{ number_format($tecnica->total ?? 0) }}</div>
            <div class="stat-lbl">Calificaciones registradas</div>
        </div>
    </div>
    @if($promTec)
    <div class="bar-wrap">
        <div class="bar-fill" style="width:{{ min(100,$promTec) }}%;background:{{ $colorTec }};"></div>
    </div>
    @else
    <p style="text-align:center;color:#9ca3af;font-size:7.5px;padding:6px 0 10px;">Sin calificaciones publicadas aún.</p>
    @endif
</div>

{{-- Comparativa --}}
@if($promAcad || $promTec)
<div class="comparativa">
    <div class="comparativa-title">Comparativa Visual</div>
    <div class="comp-row">
        <div class="comp-label" style="color:#1e3a6e;">Área Académica</div>
        <div class="comp-bar-wrap">
            <div class="bar-wrap" style="margin:0;">
                <div class="bar-fill" style="width:{{ min(100,$promAcad ?? 0) }}%;background:#1e3a6e;"></div>
            </div>
        </div>
        <div class="comp-val" style="color:#1e3a6e;">{{ $promAcad ?? '—' }}</div>
    </div>
    <div class="comp-row">
        <div class="comp-label" style="color:#c0392b;">Área Técnica</div>
        <div class="comp-bar-wrap">
            <div class="bar-wrap" style="margin:0;">
                <div class="bar-fill" style="width:{{ min(100,$promTec ?? 0) }}%;background:#c0392b;"></div>
            </div>
        </div>
        <div class="comp-val" style="color:#c0392b;">{{ $promTec ?? '—' }}</div>
    </div>
</div>
@endif

<div class="note-box">
    <strong>Nota MINERD:</strong> La nota mínima de aprobación es <strong>70 puntos</strong>.
    Estudiantes con promedio &lt;70 en alguna asignatura se consideran en riesgo académico.
</div>

<div class="footer">
    <div class="footer-l">{{ $inst }} — Rendimiento por Área</div>
    <div class="footer-r">{{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
