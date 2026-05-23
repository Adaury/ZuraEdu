<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Cierre — {{ $schoolYear?->nombre }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8.5pt; color:#1a1a2e; background:#fff; line-height:1.35; }
@page { size:letter portrait; margin:.9cm 1.1cm; }

/* ── Encabezado ── */
.hdr-outer { border:2px solid #1e3a6e; border-radius:3px; margin-bottom:8px; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:6.5pt; font-weight:700;
           letter-spacing:.18em; text-transform:uppercase; padding:2px 0; }
.hdr-table { width:100%; border-collapse:collapse; }
.hdr-table td { padding:6px 8px; vertical-align:middle; }
.hdr-logo-cell { width:65px; text-align:center; border-right:1px solid #e5e7eb; }
.logo-img { height:50px; max-width:60px; object-fit:contain; }
.logo-abbr { width:50px; height:50px; border-radius:5px; background:#1e3a6e; color:#fff;
             font-size:12pt; font-weight:900; display:inline-block; text-align:center; line-height:50px; }
.hdr-center { text-align:center; }
.inst-rep    { font-size:6pt; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:#6b7280; }
.inst-min    { font-size:6pt; font-weight:700; letter-spacing:.1em;  text-transform:uppercase; color:#9ca3af; margin-bottom:3px; }
.inst-nom    { font-size:13pt; font-weight:900; color:#1e3a6e; line-height:1.1; }
.inst-niv    { font-size:7pt; color:#4b5563; font-weight:600; margin-top:1px; }
.hdr-right   { width:120px; text-align:center; border-left:1px solid #e5e7eb; padding:6px 8px; }
.codigo-box  { border:1.5px solid #1e3a6e; border-radius:4px; padding:4px 6px; margin-bottom:4px; }
.codigo-lbl  { font-size:6pt; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#6b7280; display:block; }
.codigo-val  { font-size:9pt; font-weight:900; color:#1e3a6e; display:block; }
.fecha-val   { font-size:7pt; font-weight:700; color:#374151; display:block; margin-top:2px; }

/* ── Barra de título ── */
.title-bar {
    background:#1e3a6e; color:#fff; text-align:center;
    font-size:9pt; font-weight:900; letter-spacing:.12em;
    text-transform:uppercase; padding:4px 0 3px; margin-bottom:8px;
}

/* ── Stats globales ── */
.stats-row { width:100%; border-collapse:collapse; margin-bottom:8px; }
.stats-row td { padding:0 4px; }
.stat-box { border:1px solid #e5e7eb; border-radius:4px; padding:5px 8px; text-align:center; }
.stat-val { font-size:14pt; font-weight:900; line-height:1; }
.stat-lbl { font-size:6pt; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-top:1px; }

/* ── Tabla principal ── */
.main-table { width:100%; border-collapse:collapse; font-size:7.5pt; margin-bottom:8px; }
.main-table th {
    background:#1e3a6e; color:#fff; padding:4px 6px;
    text-align:center; font-weight:700; font-size:7pt;
    letter-spacing:.04em; border:1px solid #1e3a6e; vertical-align:middle;
}
.main-table th.left { text-align:left; }
.main-table td { padding:3px 6px; border:1px solid #d1d5db; vertical-align:middle; }
.main-table tr:nth-child(even) td { background:#f8faff; }
.main-table td.center { text-align:center; }
.main-table td.right  { text-align:right; }

/* ── Barras de progreso ── */
.bar-wrap { height:7px; background:#f3f4f6; border-radius:4px; overflow:hidden; min-width:60px; }
.bar-fill-a { height:100%; background:#10b981; float:left; }
.bar-fill-r { height:100%; background:#ef4444; float:left; }
.bar-fill-c { height:100%; background:#f59e0b; float:left; }
.bar-fill-p { height:100%; background:#94a3b8; float:left; }

/* ── Badges ── */
.bg-a { background:#d1fae5; color:#065f46; border-radius:4px; padding:1px 5px; font-weight:700; }
.bg-r { background:#fee2e2; color:#991b1b; border-radius:4px; padding:1px 5px; font-weight:700; }
.bg-c { background:#fef3c7; color:#92400e; border-radius:4px; padding:1px 5px; font-weight:700; }
.bg-p { background:#f3f4f6; color:#374151; border-radius:4px; padding:1px 5px; font-weight:700; }

/* ── Totales ── */
.totales-row td { background:#eef3fb !important; font-weight:800; border-top:2px solid #1e3a6e; }

/* ── Gráfica de barras ── */
.chart-section { margin-bottom:8px; }
.chart-title { font-size:7pt; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#1e3a6e; margin-bottom:4px; }
.chart-bar-row { margin-bottom:3px; }
.chart-bar-label { font-size:6.5pt; font-weight:600; color:#374151; margin-bottom:1px; }
.chart-bar-outer { height:9px; background:#f3f4f6; border-radius:4px; overflow:hidden; }
.chart-bar-inner { height:100%; background:#2563eb; border-radius:4px; }

/* ── Firmas ── */
.firma-table { width:100%; border-collapse:collapse; margin-top:16px; }
.firma-table td { text-align:center; padding:0 10px; vertical-align:bottom; }
.firma-linea { border-top:1.5px solid #1e3a6e; margin-top:20px; padding-top:3px; font-size:7pt; }
.firma-cargo { font-size:6.5pt; color:#6b7280; margin-top:1px; }

.footer-bar { border-top:1px solid #e5e7eb; margin-top:8px; padding-top:3px;
              font-size:6pt; color:#9ca3af; text-align:center; }
</style>
</head>
<body>

{{-- Encabezado --}}
<div class="hdr-outer">
    <div class="hdr-top">República Dominicana &nbsp;·&nbsp; Ministerio de Educación (MINERD)</div>
    <table class="hdr-table">
        <tr>
            <td class="hdr-logo-cell">
                @if(!empty($logoPath))
                    <img src="{{ public_path('storage/' . $logoPath) }}" class="logo-img" alt="Logo">
                @else
                    <div class="logo-abbr">{{ strtoupper(substr($instNombre ?? 'P', 0, 2)) }}</div>
                @endif
            </td>
            <td class="hdr-center">
                <div class="inst-rep">República Dominicana</div>
                <div class="inst-min">Ministerio de Educación</div>
                <div class="inst-nom">{{ $instNombre ?? 'Centro Educativo' }}</div>
                <div class="inst-niv">Nivel Secundario</div>
            </td>
            <td class="hdr-right">
                <div class="codigo-box">
                    <span class="codigo-lbl">Año Escolar</span>
                    <span class="codigo-val">{{ $schoolYear?->nombre ?? '—' }}</span>
                </div>
                <span class="fecha-val">{{ now()->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="title-bar">Reporte Consolidado de Cierre de Año Escolar</div>

{{-- Stats globales --}}
@php
    $pctAprobacion = $totalesGlobales['total'] > 0
        ? round(($totalesGlobales['promovidos'] / $totalesGlobales['total']) * 100, 1)
        : 0;
@endphp
<table class="stats-row">
    <tr>
        <td style="width:20%;">
            <div class="stat-box">
                <div class="stat-val" style="color:#1d4ed8;">{{ $totalesGlobales['total'] }}</div>
                <div class="stat-lbl">Total Estudiantes</div>
            </div>
        </td>
        <td style="width:20%;">
            <div class="stat-box">
                <div class="stat-val" style="color:#059669;">{{ $totalesGlobales['promovidos'] }}</div>
                <div class="stat-lbl">Promovidos</div>
            </div>
        </td>
        <td style="width:20%;">
            <div class="stat-box">
                <div class="stat-val" style="color:#dc2626;">{{ $totalesGlobales['no_promovidos'] }}</div>
                <div class="stat-lbl">No Promovidos</div>
            </div>
        </td>
        <td style="width:20%;">
            <div class="stat-box">
                <div class="stat-val" style="color:#d97706;">{{ $totalesGlobales['condicionados'] }}</div>
                <div class="stat-lbl">Condicionados</div>
            </div>
        </td>
        <td style="width:20%;">
            <div class="stat-box">
                <div class="stat-val" style="color:#1d4ed8;">{{ $pctAprobacion }}%</div>
                <div class="stat-lbl">% Aprobación</div>
            </div>
        </td>
    </tr>
</table>

{{-- Tabla por grupos --}}
<table class="main-table">
    <thead>
        <tr>
            <th class="left" style="width:130px;">Grupo</th>
            <th style="width:70px;">Ciclo</th>
            <th style="width:40px;">Total</th>
            <th style="width:55px;">Promovidos</th>
            <th style="width:60px;">No Prom.</th>
            <th style="width:60px;">Condic.</th>
            <th style="width:55px;">Pendientes</th>
            <th style="width:40px;">Prom.</th>
            <th style="width:45px;">% Apro.</th>
            <th>Distribución</th>
        </tr>
    </thead>
    <tbody>
        @foreach($resumen as $r)
        @php
            $total = $r['total'];
            $pA = $total > 0 ? round($r['promovidos']    / $total * 100) : 0;
            $pR = $total > 0 ? round($r['no_promovidos'] / $total * 100) : 0;
            $pC = $total > 0 ? round($r['condicionados'] / $total * 100) : 0;
            $pP = $total > 0 ? round($r['pendientes']    / $total * 100) : 0;
            $ciclo = $r['grupo']->grado?->ciclo;
        @endphp
        <tr>
            <td class="left">{{ $r['grupo']->nombre_completo }}</td>
            <td class="center">
                @if($ciclo === 'primer_ciclo')  1er Ciclo
                @elseif($ciclo === 'segundo_ciclo') 2do Ciclo
                @elseif($ciclo === 'bachillerato')  Bachillerato
                @elseif($ciclo === 'inicial')        Inicial
                @else {{ $ciclo }}
                @endif
            </td>
            <td class="center"><strong>{{ $total }}</strong></td>
            <td class="center"><span class="bg-a">{{ $r['promovidos'] }}</span></td>
            <td class="center"><span class="bg-r">{{ $r['no_promovidos'] }}</span></td>
            <td class="center"><span class="bg-c">{{ $r['condicionados'] }}</span></td>
            <td class="center"><span class="bg-p">{{ $r['pendientes'] }}</span></td>
            <td class="center">{{ $r['promedio_inst'] ?? '—' }}</td>
            <td class="center" style="font-weight:700;color:{{ $r['pct_aprobacion'] >= 60 ? '#059669' : '#dc2626' }};">
                {{ $r['pct_aprobacion'] }}%
            </td>
            <td>
                @if($total > 0)
                <div class="bar-wrap">
                    <div class="bar-fill-a" style="width:{{ $pA }}%;"></div>
                    <div class="bar-fill-r" style="width:{{ $pR }}%;"></div>
                    <div class="bar-fill-c" style="width:{{ $pC }}%;"></div>
                    <div class="bar-fill-p" style="width:{{ $pP }}%;"></div>
                </div>
                @endif
            </td>
        </tr>
        @endforeach

        {{-- Fila de totales --}}
        <tr class="totales-row">
            <td class="left" colspan="2">TOTALES INSTITUCIONALES</td>
            <td class="center">{{ $totalesGlobales['total'] }}</td>
            <td class="center">{{ $totalesGlobales['promovidos'] }}</td>
            <td class="center">{{ $totalesGlobales['no_promovidos'] }}</td>
            <td class="center">{{ $totalesGlobales['condicionados'] }}</td>
            <td class="center">{{ $totalesGlobales['pendientes'] }}</td>
            <td class="center">—</td>
            <td class="center" style="color:{{ $pctAprobacion >= 60 ? '#059669' : '#dc2626' }};">{{ $pctAprobacion }}%</td>
            <td>
                @php
                    $t = $totalesGlobales['total'];
                    $bA = $t > 0 ? round($totalesGlobales['promovidos']    / $t * 100) : 0;
                    $bR = $t > 0 ? round($totalesGlobales['no_promovidos'] / $t * 100) : 0;
                    $bC = $t > 0 ? round($totalesGlobales['condicionados'] / $t * 100) : 0;
                    $bP = $t > 0 ? round($totalesGlobales['pendientes']    / $t * 100) : 0;
                @endphp
                @if($t > 0)
                <div class="bar-wrap">
                    <div class="bar-fill-a" style="width:{{ $bA }}%;"></div>
                    <div class="bar-fill-r" style="width:{{ $bR }}%;"></div>
                    <div class="bar-fill-c" style="width:{{ $bC }}%;"></div>
                    <div class="bar-fill-p" style="width:{{ $bP }}%;"></div>
                </div>
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- Análisis por ciclo --}}
@php
    $porCiclo = collect($resumen)->groupBy(fn ($r) => $r['grupo']->grado?->ciclo ?? 'sin_ciclo');
@endphp
@if($porCiclo->count() > 1)
<div class="chart-section">
    <div class="chart-title">Aprobación por Ciclo</div>
    @foreach($porCiclo as $ciclo => $grupos)
    @php
        $tot = collect($grupos)->sum('total');
        $apr = collect($grupos)->sum('promovidos');
        $pct = $tot > 0 ? round(($apr / $tot) * 100, 1) : 0;
        $cicloLabel = match($ciclo) {
            'primer_ciclo'  => 'Primer Ciclo (7mo, 8vo, 9no)',
            'segundo_ciclo' => 'Segundo Ciclo (1ro–3ro Bachillerato)',
            'bachillerato'  => 'Bachillerato Técnico',
            'inicial'       => 'Nivel Inicial',
            default         => 'Otros',
        };
    @endphp
    <div class="chart-bar-row">
        <div class="chart-bar-label">{{ $cicloLabel }} — {{ $tot }} est. · {{ $apr }} promovidos · <strong>{{ $pct }}%</strong></div>
        <div class="chart-bar-outer">
            <div class="chart-bar-inner" style="width:{{ $pct }}%;"></div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Firmas --}}
<table class="firma-table">
    <tr>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Coordinador(a) Académico(a)</div>
        </td>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Director(a) del Centro</div>
        </td>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Secretaria Docente</div>
        </td>
    </tr>
</table>

<div class="footer-bar">
    Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }} &nbsp;·&nbsp;
    {{ $instNombre ?? 'Centro Educativo' }} &nbsp;·&nbsp;
    Año Escolar {{ $schoolYear?->nombre ?? '—' }}
</div>

</body>
</html>
