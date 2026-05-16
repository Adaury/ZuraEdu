@extends('layouts.portal')
@section('page-title', 'Mis Estadísticas — Portal Docente')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'mis-estadisticas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.mis-estadisticas') }}" class="prt-nav-item active">
        <i class="bi bi-bar-chart-fill"></i>Estadísticas
    </a>
    <a href="{{ route('portal.docente.mis-estudiantes') }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.65rem; margin-bottom:1rem; }
.kpi-card { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:.85rem 1rem; }
.kpi-label { font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.15rem; }
.kpi-val   { font-size:1.65rem; font-weight:900; line-height:1; }
.kpi-sub   { font-size:.68rem; color:#64748b; margin-top:.1rem; }

.prog-bar-wrap { display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem; }
.prog-bar-bg   { flex:1; height:8px; background:#f1f5f9; border-radius:99px; overflow:hidden; }
.prog-bar-fill { height:100%; border-radius:99px; transition:width .4s; }

.asig-row { border-bottom:1px solid #f1f5f9; }
.asig-row:last-child { border-bottom:none; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-bar-chart-fill" style="color:#6366f1;"></i> Mis Estadísticas
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $docente->nombre_completo }} · {{ $schoolYear?->nombre ?? 'Sin año escolar activo' }}
        </div>
    </div>
</div>

{{-- KPIs principales --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Estudiantes</div>
        <div class="kpi-val" style="color:#7c3aed;">{{ $totalEstudiantes }}</div>
        <div class="kpi-sub">en mis grupos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Asignaturas</div>
        <div class="kpi-val" style="color:#1d4ed8;">{{ $asignaciones->count() }}</div>
        <div class="kpi-sub">este año</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Aprobados</div>
        <div class="kpi-val" style="color:#15803d;">{{ $totalAprobados }}</div>
        <div class="kpi-sub">nota ≥ 65</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Reprobados</div>
        <div class="kpi-val" style="color:#dc2626;">{{ $totalReprobados }}</div>
        <div class="kpi-sub">nota &lt; 65</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Sin nota</div>
        <div class="kpi-val" style="color:#94a3b8;">{{ $totalSinNota }}</div>
        <div class="kpi-sub">pendientes</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Asistencia</div>
        <div class="kpi-val" style="color:{{ $pctAsistGlobal !== null ? ($pctAsistGlobal >= 80 ? '#15803d' : '#dc2626') : '#94a3b8' }};">
            {{ $pctAsistGlobal !== null ? $pctAsistGlobal.'%' : '—' }}
        </div>
        <div class="kpi-sub">promedio global</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Planificaciones</div>
        <div class="kpi-val" style="color:#d97706;">{{ $totalPlanificaciones + $totalPlanesClase }}</div>
        <div class="kpi-sub">creadas</div>
    </div>
</div>

{{-- Gráfica de promedios --}}
@if(count($estadisticasPorAsignacion) > 0)
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-bar-chart-fill" style="color:#6366f1;"></i>
        <h3>Promedio por asignatura</h3>
    </div>
    <div style="padding:1rem;">
        <div style="position:relative;height:220px;">
            <canvas id="chartPromedios"></canvas>
        </div>
    </div>
</div>

{{-- Aprobados vs Reprobados --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-people-fill" style="color:#10b981;"></i>
        <h3>Aprobados vs. Reprobados por asignatura</h3>
    </div>
    <div style="padding:.75rem 1rem;">
        @foreach($estadisticasPorAsignacion as $e)
        @php $tot = $e['aprobados'] + $e['reprobados'] + $e['sin_nota']; @endphp
        <div style="margin-bottom:.75rem;">
            <div style="display:flex;justify-content:space-between;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.3rem;">
                <span>{{ $e['asignatura'] }} <span style="font-weight:400;color:#94a3b8;">· {{ $e['grupo'] }}</span></span>
                <span style="font-size:.68rem;color:#64748b;">{{ $e['aprobados'] }}A / {{ $e['reprobados'] }}R / {{ $e['sin_nota'] }}S</span>
            </div>
            @php $pctAp = $tot > 0 ? round($e['aprobados']/$tot*100) : 0; $pctRep = $tot > 0 ? round($e['reprobados']/$tot*100) : 0; @endphp
            <div style="display:flex;height:10px;border-radius:99px;overflow:hidden;background:#f1f5f9;gap:1px;">
                <div style="width:{{ $pctAp }}%;background:#22c55e;"></div>
                <div style="width:{{ $pctRep }}%;background:#ef4444;"></div>
                <div style="flex:1;background:#e2e8f0;"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Tabla detallada --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-table" style="color:#2563eb;"></i>
        <h3>Detalle por asignatura</h3>
        <span style="margin-left:auto;font-size:.72rem;color:#64748b;">{{ $asignaciones->count() }} asignaciones</span>
    </div>

    @if(empty($estadisticasPorAsignacion))
    <div style="padding:2.5rem;text-align:center;color:#94a3b8;font-size:.85rem;">
        <i class="bi bi-journal-x" style="font-size:1.8rem;display:block;margin-bottom:.6rem;"></i>
        No tienes asignaciones activas este año.
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:.5rem 1rem;text-align:left;font-weight:700;color:#374151;min-width:140px;">Asignatura</th>
                    <th style="padding:.5rem .6rem;text-align:left;font-weight:700;color:#374151;min-width:100px;">Grupo</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#1e3a8a;min-width:70px;">Promedio</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#15803d;min-width:60px;">Apob.</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#dc2626;min-width:60px;">Repob.</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#94a3b8;min-width:60px;">S/Nota</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#f59e0b;min-width:70px;">Asistencia</th>
                    <th style="padding:.5rem .6rem;text-align:center;font-weight:700;color:#64748b;min-width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticasPorAsignacion as $e)
                @php
                    $cp = $e['promedio'] === null ? '#94a3b8' : ($e['promedio'] >= 70 ? '#15803d' : ($e['promedio'] >= 65 ? '#92400e' : '#dc2626'));
                    $bgp = $e['promedio'] === null ? '#f1f5f9' : ($e['promedio'] >= 70 ? '#dcfce7' : ($e['promedio'] >= 65 ? '#fef9c3' : '#fee2e2'));
                    $ca = $e['pct_asist'] === null ? '#94a3b8' : ($e['pct_asist'] >= 80 ? '#15803d' : '#dc2626');
                    $bga = $e['pct_asist'] === null ? '#f1f5f9' : ($e['pct_asist'] >= 80 ? '#dcfce7' : '#fee2e2');
                @endphp
                <tr class="asig-row" style="transition:background .1s;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                    <td style="padding:.55rem 1rem;font-weight:700;color:#1e293b;">{{ $e['asignatura'] }}</td>
                    <td style="padding:.55rem .6rem;color:#64748b;font-size:.73rem;">{{ $e['grupo'] }}</td>
                    <td style="padding:.55rem .6rem;text-align:center;">
                        <span style="background:{{ $bgp }};color:{{ $cp }};border-radius:7px;padding:.2rem .5rem;font-weight:800;font-size:.8rem;">
                            {{ $e['promedio'] !== null ? $e['promedio'] : '—' }}
                        </span>
                    </td>
                    <td style="padding:.55rem .6rem;text-align:center;font-weight:700;color:#15803d;">{{ $e['aprobados'] }}</td>
                    <td style="padding:.55rem .6rem;text-align:center;font-weight:700;color:#dc2626;">{{ $e['reprobados'] }}</td>
                    <td style="padding:.55rem .6rem;text-align:center;font-weight:700;color:#94a3b8;">{{ $e['sin_nota'] }}</td>
                    <td style="padding:.55rem .6rem;text-align:center;">
                        <span style="background:{{ $bga }};color:{{ $ca }};border-radius:7px;padding:.2rem .5rem;font-weight:800;font-size:.8rem;">
                            {{ $e['pct_asist'] !== null ? $e['pct_asist'].'%' : '—' }}
                        </span>
                    </td>
                    <td style="padding:.55rem .6rem;text-align:center;">
                        <a href="{{ route('portal.docente.rendimiento', $e['asignacion']) }}"
                           style="background:#eff6ff;color:#1d4ed8;border-radius:7px;padding:.25rem .6rem;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
@if(count($estadisticasPorAsignacion) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels   = {!! $chartLabels !!};
    const data     = {!! $chartData !!};
    const aprob    = {!! $chartAprobados !!};
    const reprob   = {!! $chartReprobados !!};

    const colors  = data.map(v => v >= 70 ? 'rgba(34,197,94,.85)' : v >= 65 ? 'rgba(234,179,8,.85)' : 'rgba(239,68,68,.85)');
    const borders = data.map(v => v >= 70 ? '#16a34a' : v >= 65 ? '#ca8a04' : '#dc2626');

    const ctx = document.getElementById('chartPromedios');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Promedio',
                data,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => ' Promedio: ' + c.parsed.y + ' pts  |  ' + aprob[c.dataIndex] + ' aprobados / ' + reprob[c.dataIndex] + ' reprobados'
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 35 } }
            }
        }
    });
})();
</script>
@endif
@endpush
