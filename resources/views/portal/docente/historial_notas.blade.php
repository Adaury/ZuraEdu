@extends('layouts.portal')
@section('page-title', 'Comparativa de Rendimiento — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'historial-notas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.rendimiento', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-graph-up-arrow"></i>Rendimiento
    </a>
    <a href="{{ route('portal.docente.historial-notas', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-activity"></i>Historial
    </a>
@endsection

@push('styles')
<style>
/* KPI cards */
.hn-kpi { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:.75rem 1rem; text-align:center; }
.hn-kpi-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.2rem; }
.hn-kpi-val   { font-size:1.5rem; font-weight:900; line-height:1.1; }
.hn-kpi-sub   { font-size:.68rem; color:#94a3b8; margin-top:.15rem; }

/* Filtros */
.hn-filtro { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .8rem;
    border-radius:99px; font-size:.72rem; font-weight:700; cursor:pointer; border:2px solid transparent;
    transition:all .15s; user-select:none; }
.hn-filtro.active { border-color:currentColor; background:rgba(99,102,241,.08); }
.hn-filtro:hover  { background:rgba(99,102,241,.05); }

/* Tabla */
.hn-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.hn-table th { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:#94a3b8; padding:.5rem .6rem; border-bottom:2px solid #e2e8f0; text-align:left; }
.hn-table td { padding:.55rem .6rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.hn-table tr:last-child td { border-bottom:none; }
.hn-table tr:hover td { background:#f8faff; }
.hn-table tr.riesgo td { background:#fff5f5; }
.hn-table tr.riesgo:hover td { background:#fee2e2; }
.hn-table tr[data-hidden="1"] { display:none; }

/* Badges de período */
.pn { display:inline-block; min-width:32px; text-align:center;
    font-size:.75rem; font-weight:700; border-radius:6px; padding:.15rem .28rem; }
.pn-ok  { background:#dcfce7; color:#15803d; }
.pn-med { background:#fef9c3; color:#92400e; }
.pn-low { background:#fee2e2; color:#dc2626; }
.pn-nil { background:#f1f5f9; color:#94a3b8; }

/* Tendencia */
.tend { font-size:.8rem; font-weight:800; }
.tend-up      { color:#15803d; }
.tend-down    { color:#dc2626; }
.tend-neutral { color:#94a3b8; }

/* Sparkline SVG */
.sparkline { display:block; }

/* Nota final badge */
.nf-badge { display:inline-block; min-width:40px; text-align:center;
    font-weight:900; font-size:.82rem; border-radius:7px; padding:.2rem .38rem; }
.nf-ex  { background:#dbeafe; color:#1d4ed8; }
.nf-ok  { background:#dcfce7; color:#15803d; }
.nf-med { background:#fef9c3; color:#92400e; }
.nf-low { background:#fee2e2; color:#dc2626; }
.nf-nil { background:#f1f5f9; color:#94a3b8; }

@media(max-width:640px){
    .hn-table .col-spark, .hn-table .col-ficha { display:none; }
}
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.rendimiento', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-activity" style="color:#1e3a8a;"></i>
            Comparativa de Rendimiento
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.historial-notas.pdf', $asignacion) }}"
       target="_blank"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-file-earmark-pdf-fill"></i>PDF
    </a>
</div>

{{-- KPIs: promedio del grupo por período + resumen de tendencias --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.6rem;margin-bottom:1rem;">

    @foreach($periodos as $p)
    @php $prom = $promediosPeriodo[$p->numero] ?? null; @endphp
    <div class="hn-kpi">
        <div class="hn-kpi-label">{{ $p->nombre }}</div>
        <div class="hn-kpi-val" style="color:{{ $prom === null ? '#94a3b8' : ($prom >= 70 ? '#15803d' : ($prom >= 65 ? '#92400e' : '#dc2626')) }};">
            {{ $prom !== null ? number_format($prom, 1) : '—' }}
        </div>
        <div class="hn-kpi-sub">prom. grupo</div>
    </div>
    @endforeach

    <div class="hn-kpi" style="border-color:#f59e0b;">
        <div class="hn-kpi-label" style="color:#d97706;">En riesgo</div>
        <div class="hn-kpi-val" style="color:#d97706;">{{ $enRiesgoCount }}</div>
        <div class="hn-kpi-sub">estudiantes</div>
    </div>

    <div class="hn-kpi" style="border-color:#10b981;">
        <div class="hn-kpi-label" style="color:#059669;">Mejorando</div>
        <div class="hn-kpi-val" style="color:#059669;">{{ $mejorandoCount }}</div>
        <div class="hn-kpi-sub">tendencia ↑</div>
    </div>

    <div class="hn-kpi" style="border-color:#ef4444;">
        <div class="hn-kpi-label" style="color:#dc2626;">Declinando</div>
        <div class="hn-kpi-val" style="color:#dc2626;">{{ $declinandoCount }}</div>
        <div class="hn-kpi-sub">tendencia ↓</div>
    </div>

</div>

{{-- Gráfica de evolución del grupo --}}
@if($periodos->count() >= 2)
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-graph-up" style="color:#3b82f6;"></i>
        <h3>Evolución del promedio grupal</h3>
    </div>
    <div style="padding:.75rem 1rem;height:170px;position:relative;">
        <canvas id="chartHistorial"></canvas>
    </div>
</div>

{{-- Gráfica multi-línea: evolución por estudiante --}}
@php $totalConNota = $filas->filter(fn($f) => !$f['sinNota'])->count(); @endphp
@if($totalConNota > 0)
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-people-fill" style="color:#6366f1;"></i>
            <h3 style="margin:0;">Evolución individual — P1 → P{{ $periodos->count() }}</h3>
        </div>
        <span style="font-size:.7rem;color:#94a3b8;">{{ $totalConNota }} estudiante(s) con notas</span>
    </div>
    <div style="padding:.75rem 1rem;height:240px;position:relative;">
        <canvas id="chartEstudiantes"></canvas>
    </div>
</div>
@endif

{{-- Top Mejoras / Top Descensos --}}
@if($topMejoras->isNotEmpty() || $topDescensos->isNotEmpty())
<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">

    {{-- Top Mejoras --}}
    <div class="prt-card" style="padding:0;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#dcfce7,#f0fdf4);padding:.65rem 1rem;border-bottom:1px solid #bbf7d0;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-arrow-up-circle-fill" style="color:#16a34a;font-size:.9rem;"></i>
            <span style="font-size:.78rem;font-weight:800;color:#15803d;">Top Mejoras</span>
        </div>
        <div style="padding:.5rem 0;">
        @foreach($topMejoras as $fila)
        @if($fila['diff'] !== null && $fila['diff'] > 0)
        <div style="display:flex;align-items:center;gap:.5rem;padding:.35rem 1rem;border-bottom:1px solid #f0fdf4;">
            <span style="font-size:.75rem;font-weight:700;color:#1e293b;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
            </span>
            <span style="background:#dcfce7;color:#15803d;border-radius:99px;font-size:.72rem;font-weight:800;padding:.12rem .45rem;flex-shrink:0;">
                +{{ $fila['diff'] }} pts
            </span>
        </div>
        @endif
        @endforeach
        @if($topMejoras->filter(fn($f) => $f['diff'] !== null && $f['diff'] > 0)->isEmpty())
        <div style="text-align:center;padding:.75rem;font-size:.75rem;color:#94a3b8;">Sin mejoras registradas</div>
        @endif
        </div>
    </div>

    {{-- Top Descensos --}}
    <div class="prt-card" style="padding:0;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#fee2e2,#fff5f5);padding:.65rem 1rem;border-bottom:1px solid #fecaca;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-arrow-down-circle-fill" style="color:#dc2626;font-size:.9rem;"></i>
            <span style="font-size:.78rem;font-weight:800;color:#dc2626;">Top Descensos</span>
        </div>
        <div style="padding:.5rem 0;">
        @foreach($topDescensos as $fila)
        @if($fila['diff'] !== null && $fila['diff'] < 0)
        <div style="display:flex;align-items:center;gap:.5rem;padding:.35rem 1rem;border-bottom:1px solid #fff5f5;">
            <span style="font-size:.75rem;font-weight:700;color:#1e293b;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
            </span>
            <span style="background:#fee2e2;color:#dc2626;border-radius:99px;font-size:.72rem;font-weight:800;padding:.12rem .45rem;flex-shrink:0;">
                {{ $fila['diff'] }} pts
            </span>
        </div>
        @endif
        @endforeach
        @if($topDescensos->filter(fn($f) => $f['diff'] !== null && $f['diff'] < 0)->isEmpty())
        <div style="text-align:center;padding:.75rem;font-size:.75rem;color:#94a3b8;">Sin descensos registrados</div>
        @endif
        </div>
    </div>

</div>
@endif
@endif

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.85rem;">
    <span class="hn-filtro active" style="color:#6366f1;" data-filtro="todos" onclick="setFiltro(this)">
        <i class="bi bi-people-fill"></i>Todos ({{ $filas->count() }})
    </span>
    <span class="hn-filtro" style="color:#f59e0b;" data-filtro="riesgo" onclick="setFiltro(this)">
        <i class="bi bi-exclamation-triangle-fill"></i>En riesgo ({{ $enRiesgoCount }})
    </span>
    <span class="hn-filtro" style="color:#10b981;" data-filtro="mejorando" onclick="setFiltro(this)">
        <i class="bi bi-arrow-up-circle-fill"></i>Mejorando ({{ $mejorandoCount }})
    </span>
    <span class="hn-filtro" style="color:#ef4444;" data-filtro="declinando" onclick="setFiltro(this)">
        <i class="bi bi-arrow-down-circle-fill"></i>Declinando ({{ $declinandoCount }})
    </span>
    <span class="hn-filtro" style="color:#94a3b8;" data-filtro="sin-nota" onclick="setFiltro(this)">
        <i class="bi bi-dash-circle"></i>Sin nota ({{ $filas->where('sinNota', true)->count() }})
    </span>
</div>

{{-- Tabla de estudiantes --}}
<div class="prt-card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="hn-table" id="tablaHistorial">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th class="col-spark">Evolución</th>
                    @foreach($periodos as $p)
                    <th style="text-align:center;">{{ $p->nombre }}</th>
                    @endforeach
                    <th style="text-align:center;">Final</th>
                    <th style="text-align:center;">Tendencia</th>
                    <th class="col-ficha"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($filas as $i => $fila)
            @php
                $mat  = $fila['matricula'];
                $nf   = $fila['notaFinal'];
                $tend = $fila['tendencia'];
                $diff = $fila['diff'];

                // Clase de la fila para filtros
                $rowClasses = 'est-row';
                if ($fila['enRiesgo'])      $rowClasses .= ' riesgo';
                $rowDataFiltro = 'todos';
                if ($fila['sinNota'])       $rowDataFiltro = 'sin-nota';
                elseif ($fila['enRiesgo'])  $rowDataFiltro .= ' riesgo';
                if ($tend === 'up')         $rowDataFiltro .= ' mejorando';
                if ($tend === 'down')       $rowDataFiltro .= ' declinando';

                // Clase badge nota final
                $nfClass = 'nf-nil';
                if ($nf !== null) {
                    if ($nf >= 90)     $nfClass = 'nf-ex';
                    elseif ($nf >= 70) $nfClass = 'nf-ok';
                    elseif ($nf >= 65) $nfClass = 'nf-med';
                    else               $nfClass = 'nf-low';
                }

                // Sparkline SVG (60×30)
                $sparkPoints = [];
                $sparkNotas  = [];
                foreach ($periodos as $p) {
                    $v = $fila['notasPeriodo'][$p->numero] ?? null;
                    $sparkNotas[] = $v;
                }
                $hasAnyNota = collect($sparkNotas)->filter(fn($v) => $v !== null)->isNotEmpty();
                $sparkSvg = '';
                if ($hasAnyNota && count($sparkNotas) >= 2) {
                    $w = 60; $h = 30; $pad = 3;
                    $n = count($sparkNotas);
                    $xStep = ($n > 1) ? ($w - $pad*2) / ($n - 1) : 0;
                    $pts = [];
                    foreach ($sparkNotas as $idx => $sv) {
                        $x = $pad + $idx * $xStep;
                        $y = $sv !== null ? $h - $pad - (($sv / 100) * ($h - $pad*2)) : null;
                        $pts[] = ['x' => $x, 'y' => $y, 'v' => $sv];
                    }
                    // Build polyline from consecutive non-null points
                    $segments = [];
                    $current = [];
                    foreach ($pts as $pt) {
                        if ($pt['y'] !== null) {
                            $current[] = $pt['x'] . ',' . round($pt['y'], 1);
                        } else {
                            if (count($current) >= 2) $segments[] = implode(' ', $current);
                            $current = [];
                        }
                    }
                    if (count($current) >= 2) $segments[] = implode(' ', $current);

                    $lineColor = $tend === 'up' ? '#10b981' : ($tend === 'down' ? '#ef4444' : '#6366f1');
                    $sparkSvg = '<svg class="sparkline" width="60" height="30" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg">';
                    // Línea de referencia 65
                    $refY = $h - $pad - ((65 / 100) * ($h - $pad*2));
                    $sparkSvg .= '<line x1="'.$pad.'" y1="'.round($refY,1).'" x2="'.($w-$pad).'" y2="'.round($refY,1).'" stroke="#fca5a5" stroke-width="1" stroke-dasharray="2,2"/>';
                    foreach ($segments as $seg) {
                        $sparkSvg .= '<polyline points="'.$seg.'" fill="none" stroke="'.$lineColor.'" stroke-width="2" stroke-linejoin="round"/>';
                    }
                    // Puntos
                    foreach ($pts as $pt) {
                        if ($pt['y'] !== null) {
                            $dotColor = ($pt['v'] !== null && $pt['v'] < 65) ? '#ef4444' : $lineColor;
                            $sparkSvg .= '<circle cx="'.round($pt['x'],1).'" cy="'.round($pt['y'],1).'" r="2.5" fill="'.$dotColor.'" stroke="#fff" stroke-width="1"/>';
                        }
                    }
                    $sparkSvg .= '</svg>';
                }
            @endphp
            <tr class="{{ $rowClasses }}" data-filtro="{{ $rowDataFiltro }}">
                <td style="color:#94a3b8;font-size:.7rem;font-weight:700;">{{ $mat->numero_orden ?? ($i + 1) }}</td>
                <td>
                    <div style="font-weight:700;color:#1e293b;font-size:.78rem;">
                        {{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}
                    </div>
                    @if($mat->estudiante?->matricula)
                    <div style="font-size:.65rem;color:#94a3b8;">{{ $mat->estudiante->matricula }}</div>
                    @endif
                </td>
                <td class="col-spark">
                    @if($sparkSvg)
                        {!! $sparkSvg !!}
                    @else
                        <span style="color:#94a3b8;font-size:.7rem;">—</span>
                    @endif
                </td>
                @foreach($periodos as $p)
                @php
                    $nota = $fila['notasPeriodo'][$p->numero] ?? null;
                    $pnClass = 'pn-nil';
                    if ($nota !== null) {
                        if ($nota >= 70)     $pnClass = 'pn-ok';
                        elseif ($nota >= 65) $pnClass = 'pn-med';
                        else                 $pnClass = 'pn-low';
                    }
                @endphp
                <td style="text-align:center;">
                    <span class="pn {{ $pnClass }}">{{ $nota !== null ? number_format($nota, 1) : '—' }}</span>
                </td>
                @endforeach
                <td style="text-align:center;">
                    <span class="nf-badge {{ $nfClass }}">{{ $nf !== null ? number_format($nf, 1) : '—' }}</span>
                </td>
                <td style="text-align:center;">
                    @if($tend === 'up')
                        <span class="tend tend-up" title="Mejoró {{ $diff > 0 ? '+' : '' }}{{ $diff }} pts">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                            @if($diff !== null) <small style="font-size:.65rem;">+{{ $diff }}</small> @endif
                        </span>
                    @elseif($tend === 'down')
                        <span class="tend tend-down" title="Bajó {{ $diff }} pts">
                            <i class="bi bi-arrow-down-circle-fill"></i>
                            @if($diff !== null) <small style="font-size:.65rem;">{{ $diff }}</small> @endif
                        </span>
                    @else
                        <span class="tend tend-neutral" title="Estable">
                            <i class="bi bi-dash-circle"></i>
                        </span>
                    @endif
                </td>
                <td class="col-ficha">
                    <a href="{{ route('portal.docente.estudiantes.ficha', [$asignacion, $mat]) }}"
                       style="color:#3b82f6;font-size:.72rem;text-decoration:none;white-space:nowrap;"
                       title="Ver ficha completa">
                        <i class="bi bi-person-vcard"></i>
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ── Chart ──────────────────────────────────────────────────────────
    const ctx = document.getElementById('chartHistorial');
    if (ctx) {
        const labels = {!! $chartLabels !!};
        const data   = {!! $chartData !!};

        const colors  = data.map(v => v === null ? 'transparent' : (v >= 70 ? 'rgba(16,185,129,.85)' : (v >= 65 ? 'rgba(234,179,8,.85)' : 'rgba(239,68,68,.85)')));
        const borders = data.map(v => v === null ? 'transparent' : (v >= 70 ? '#059669' : (v >= 65 ? '#ca8a04' : '#dc2626')));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Promedio del grupo',
                    data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: colors,
                    pointBorderColor: borders,
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                    spanGaps: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: c => ' Promedio grupo: ' + (c.parsed.y !== null ? c.parsed.y + ' pts' : 'sin datos')
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { size: 10 } },
                        // Línea de referencia en 65
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                },
                // Anotación de línea de referencia 65
                plugins: {
                    legend: { display: false },
                    annotation: undefined,
                }
            },
            plugins: [{
                id: 'refLine',
                afterDraw(chart) {
                    const { ctx, scales: { y, x } } = chart;
                    const yPx = y.getPixelForValue(65);
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(x.left, yPx);
                    ctx.lineTo(x.right, yPx);
                    ctx.strokeStyle = 'rgba(239,68,68,.35)';
                    ctx.setLineDash([4, 4]);
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                    ctx.restore();
                }
            }]
        });
    }

    // ── Chart multi-línea por estudiante ──────────────────────────────
    const ctxEst = document.getElementById('chartEstudiantes');
    if (ctxEst) {
        const datasetsEst = {!! $chartEstudiantesJson ?? '[]' !!};
        const labelsEst   = {!! $chartLabels !!};
        new Chart(ctxEst, {
            type: 'line',
            data: { labels: labelsEst, datasets: datasetsEst },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: datasetsEst.length <= 12,
                        position: 'bottom',
                        labels: { font: { size: 9 }, boxWidth: 10, padding: 6 }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => ' ' + c.dataset.label + ': ' + (c.parsed.y !== null ? c.parsed.y + ' pts' : '—')
                        }
                    }
                },
                scales: {
                    y: { min: 0, max: 100, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            },
            plugins: [{
                id: 'refLineEst',
                afterDraw(chart) {
                    const { ctx, scales: { y, x } } = chart;
                    const yPx = y.getPixelForValue(65);
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(x.left, yPx);
                    ctx.lineTo(x.right, yPx);
                    ctx.strokeStyle = 'rgba(239,68,68,.3)';
                    ctx.setLineDash([4, 4]);
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    ctx.restore();
                }
            }]
        });
    }

    // ── Filtros ────────────────────────────────────────────────────────
    let filtroActivo = 'todos';

    function setFiltro(el) {
        filtroActivo = el.dataset.filtro;
        document.querySelectorAll('.hn-filtro').forEach(f => f.classList.remove('active'));
        el.classList.add('active');
        aplicarFiltro();
    }

    function aplicarFiltro() {
        document.querySelectorAll('#tablaHistorial tbody tr').forEach(tr => {
            if (filtroActivo === 'todos') {
                tr.removeAttribute('data-hidden');
                tr.style.display = '';
            } else {
                const filtros = (tr.dataset.filtro || '').split(' ');
                const visible = filtros.includes(filtroActivo);
                tr.style.display = visible ? '' : 'none';
            }
        });
    }

    // Exponer para onclick en HTML
    window.setFiltro = setFiltro;
})();
</script>
@endpush
