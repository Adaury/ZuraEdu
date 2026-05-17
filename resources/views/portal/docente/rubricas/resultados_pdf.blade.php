<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:8.5pt; color:#1e293b; }

/* ── Cabecera ── */
.header { display:table; width:100%; margin-bottom:12px; border-bottom:2px solid #3b82f6; padding-bottom:8px; }
.header-logo  { display:table-cell; width:56px; vertical-align:middle; }
.header-logo img { max-width:50px; max-height:50px; }
.header-info  { display:table-cell; vertical-align:middle; padding-left:10px; }
.inst-name    { font-size:11.5pt; font-weight:700; color:#1e40af; }
.inst-sub     { font-size:7.5pt; color:#64748b; margin-top:2px; }
.header-right { display:table-cell; text-align:right; vertical-align:top; font-size:7.5pt; color:#64748b; white-space:nowrap; }

/* ── Título ── */
.doc-title { font-size:12pt; font-weight:800; color:#1e293b; margin-bottom:3px; }
.doc-meta  { font-size:8pt; color:#475569; margin-bottom:10px; }

/* ── KPIs ── */
.kpis { width:100%; border-collapse:collapse; margin-bottom:12px; }
.kpis td { width:25%; padding:6px 10px; border-radius:8px; text-align:center; }
.kpi-num   { font-size:14pt; font-weight:800; display:block; }
.kpi-label { font-size:7pt; font-weight:600; }

/* ── Análisis por criterio ── */
.criterios-section { margin-bottom:14px; }
.section-title { font-size:9pt; font-weight:700; color:#1e40af; margin-bottom:6px; border-bottom:1px solid #bfdbfe; padding-bottom:3px; }
.criterio-row { display:table; width:100%; margin-bottom:5px; }
.criterio-lbl { display:table-cell; width:180px; vertical-align:middle; font-size:7.5pt; font-weight:600; color:#374151; padding-right:8px; }
.criterio-pts { display:table-cell; width:36px; vertical-align:middle; font-size:7pt; color:#94a3b8; padding-right:6px; text-align:right; }
.criterio-bar { display:table-cell; vertical-align:middle; }
.dist-bar { width:100%; height:14px; background:#f1f5f9; border-radius:4px; overflow:hidden; display:table; }
.dist-seg { display:table-cell; height:100%; }
.dist-labels { display:table; width:100%; margin-top:2px; }
.dist-lbl-cell { display:table-cell; font-size:6pt; color:#64748b; text-align:center; }

/* ── Tabla estudiantes ── */
table.main { width:100%; border-collapse:collapse; font-size:7.5pt; }
table.main thead tr { background:#1e40af; color:#fff; }
table.main thead th { padding:4px 5px; text-align:left; font-weight:700; }
table.main thead th.center { text-align:center; }
table.main tbody tr:nth-child(even) { background:#f8fafc; }
table.main tbody td { padding:4px 5px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
table.main tbody td.center { text-align:center; }

.chip-nivel { display:inline-block; padding:1px 5px; border-radius:99px; font-size:6.5pt; font-weight:700; color:#fff; }
.chip-nd    { background:#94a3b8; color:#fff; }

.pct-bar { width:100%; height:5px; background:#e2e8f0; border-radius:99px; margin-top:2px; }
.pct-fill { height:100%; border-radius:99px; }

.firma-row { display:table; width:100%; margin-top:20px; }
.firma-cell { display:table-cell; width:33.3%; text-align:center; padding:0 16px; }
.firma-line { border-top:1px solid #64748b; margin-bottom:3px; }
.firma-label { font-size:7.5pt; color:#475569; font-weight:600; }
.firma-sub   { font-size:7pt; color:#94a3b8; }

.footer { margin-top:10px; border-top:1px solid #e2e8f0; padding-top:6px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>

@php
    $logoUrl    = $tenant?->logo_url ?? null;
    $nombreInst = $tenant?->nombre_institucion ?? $tenant?->nombre ?? config('app.name', 'Institución');
    $niveles    = $rubrica->niveles ?? [];
    $criterios  = $rubrica->criterios ?? [];
    $total      = $matriculas->count();

    /* Distribución por criterio: criIdx => [nivelIdx => count] */
    $distrib = [];
    foreach ($criterios as $ci => $crit) {
        $distrib[$ci] = [];
        foreach ($niveles as $ni => $niv) {
            $distrib[$ci][$ni] = 0;
        }
    }
    foreach ($aplicaciones as $aplic) {
        $resultados = $aplic->resultados ?? [];
        foreach ($criterios as $ci => $crit) {
            $nivelIdx = $resultados[$ci] ?? null;
            if ($nivelIdx !== null && isset($niveles[$nivelIdx])) {
                $distrib[$ci][$nivelIdx] = ($distrib[$ci][$nivelIdx] ?? 0) + 1;
            }
        }
    }
    $nAplicados = $aplicaciones->count();
@endphp

{{-- Cabecera institucional --}}
<div class="header">
    <div class="header-logo">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo">
        @else
            <div style="width:46px;height:46px;background:#1e40af;border-radius:8px;text-align:center;padding-top:8px;">
                <span style="color:#fff;font-size:18pt;font-weight:800;line-height:1;">{{ strtoupper(substr($nombreInst,0,1)) }}</span>
            </div>
        @endif
    </div>
    <div class="header-info">
        <div class="inst-name">{{ $nombreInst }}</div>
        <div class="inst-sub">Resultados de Rúbrica &mdash; {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }}</div>
        <div class="inst-sub">
            {{ $asignacion->grupo?->grado?->nombre ?? '' }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
            &mdash; {{ $asignacion->docente?->nombre_completo ?? '' }}
        </div>
    </div>
    <div class="header-right">
        Generado: {{ now()->format('d/m/Y H:i') }}<br>
        Año escolar: {{ $schoolYear?->nombre ?? date('Y') }}
    </div>
</div>

{{-- Título de la rúbrica --}}
<div class="doc-title">{{ $rubrica->titulo }}</div>
<div class="doc-meta">
    @if($rubrica->descripcion){{ \Illuminate\Support\Str::limit($rubrica->descripcion, 160) }} &mdash; @endif
    Puntaje máximo: {{ $rubrica->puntaje_max }} pts
    &mdash; {{ count($criterios) }} criterio(s) &mdash; {{ count($niveles) }} nivel(es)
</div>

{{-- KPIs --}}
@if($stats)
<table class="kpis">
    <tr>
        <td style="background:#eff6ff;">
            <span class="kpi-num" style="color:#1d4ed8;">{{ $stats['completados'] }}</span>
            <span class="kpi-label" style="color:#3b82f6;">Evaluados</span>
        </td>
        <td style="background:#fef3c7;">
            <span class="kpi-num" style="color:#d97706;">{{ $stats['pendientes'] }}</span>
            <span class="kpi-label" style="color:#d97706;">Pendientes</span>
        </td>
        <td style="background:#d1fae5;">
            <span class="kpi-num" style="color:#059669;">{{ $stats['promedio'] }}%</span>
            <span class="kpi-label" style="color:#059669;">Promedio</span>
        </td>
        <td style="background:#ede9fe;">
            <span class="kpi-num" style="color:#7c3aed;">{{ $stats['aprobados'] }}</span>
            <span class="kpi-label" style="color:#7c3aed;">≥ 60%</span>
        </td>
    </tr>
</table>
@endif

{{-- Análisis por criterio --}}
@if($nAplicados > 0)
<div class="criterios-section">
    <div class="section-title">Distribución por Criterio</div>
    @foreach($criterios as $ci => $crit)
    @php
        $d = $distrib[$ci] ?? [];
    @endphp
    <div class="criterio-row">
        <div class="criterio-lbl">{{ \Illuminate\Support\Str::limit($crit['nombre'], 32) }}</div>
        <div class="criterio-pts">{{ $crit['puntos'] }}pts</div>
        <div class="criterio-bar">
            <div class="dist-bar">
                @foreach($niveles as $ni => $niv)
                @php
                    $cnt = $d[$ni] ?? 0;
                    $pct = $nAplicados > 0 ? round($cnt / $nAplicados * 100, 1) : 0;
                    $bg  = $niv['color'] ?? '#94a3b8';
                @endphp
                @if($pct > 0)
                <div class="dist-seg" style="width:{{ $pct }}%;background:{{ $bg }};"></div>
                @endif
                @endforeach
            </div>
            <div class="dist-labels">
                @foreach($niveles as $ni => $niv)
                @php $cnt = $d[$ni] ?? 0; @endphp
                <div class="dist-lbl-cell" style="width:{{ round(100/count($niveles),1) }}%;">
                    {{ $niv['nombre'] ?? '' }} ({{ $cnt }})
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Tabla de estudiantes --}}
<div class="section-title">Detalle por Estudiante</div>
<table class="main">
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th>Estudiante</th>
            @foreach($criterios as $ci => $crit)
            <th class="center" style="width:{{ max(55, min(90, floor(320/count($criterios)))) }}px;">
                {{ \Illuminate\Support\Str::limit($crit['nombre'], 14) }}
            </th>
            @endforeach
            <th class="center" style="width:52px;">Puntaje</th>
            <th class="center" style="width:46px;">%</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
    @php $rowNum = 1; @endphp
    @foreach($matriculas as $m)
    @php
        $est    = $m->estudiante;
        $aplic  = $aplicaciones->get($m->id);
        $puntaje = $aplic?->puntaje ?? null;
        $pmax    = $aplic?->puntaje_max ?? $rubrica->puntaje_max;
        $pct     = $aplic?->porcentaje ?? null;
        $barColor = $pct === null ? '#94a3b8'
                  : ($pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444'));
        $resultados = $aplic?->resultados ?? [];
    @endphp
    <tr>
        <td style="color:#94a3b8;font-size:7pt;">{{ $rowNum++ }}</td>
        <td style="font-weight:600;">{{ $est?->nombre_completo ?? '—' }}</td>
        @foreach($criterios as $ci => $crit)
        @php
            $nivelIdx = $resultados[$ci] ?? null;
            $nivel    = ($nivelIdx !== null && isset($niveles[$nivelIdx])) ? $niveles[$nivelIdx] : null;
        @endphp
        <td class="center">
            @if($nivel)
                <span class="chip-nivel" style="background:{{ $nivel['color'] ?? '#94a3b8' }};">
                    {{ \Illuminate\Support\Str::limit($nivel['nombre'] ?? '—', 10) }}
                </span>
            @else
                <span style="color:#cbd5e1;font-size:7pt;">—</span>
            @endif
        </td>
        @endforeach
        <td class="center" style="font-weight:700;color:{{ $barColor }};">
            {{ $puntaje !== null ? number_format($puntaje, 1) . '/' . number_format($pmax, 0) : '—' }}
        </td>
        <td class="center">
            @if($pct !== null)
                <span style="font-weight:700;color:{{ $barColor }};">{{ $pct }}%</span>
                <div class="pct-bar"><div class="pct-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div></div>
            @else
                <span style="color:#94a3b8;">—</span>
            @endif
        </td>
        <td style="font-size:7pt;color:#475569;">
            {{ $aplic?->observaciones ? \Illuminate\Support\Str::limit($aplic->observaciones, 60) : '' }}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

{{-- Firmas --}}
<div class="firma-row">
    <div class="firma-cell">
        <br><br><br>
        <div class="firma-line"></div>
        <div class="firma-label">{{ $asignacion->docente?->nombre_completo ?? 'Docente' }}</div>
        <div class="firma-sub">Docente</div>
    </div>
    <div class="firma-cell">
        <br><br><br>
        <div class="firma-line"></div>
        <div class="firma-label">Coordinación Académica</div>
        <div class="firma-sub">Firma y Sello</div>
    </div>
    <div class="firma-cell">
        <br><br><br>
        <div class="firma-line"></div>
        <div class="firma-label">Director/a</div>
        <div class="firma-sub">Firma y Sello</div>
    </div>
</div>

<div class="footer">
    {{ $nombreInst }} &mdash; Reporte de Rúbrica generado automáticamente &mdash; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
