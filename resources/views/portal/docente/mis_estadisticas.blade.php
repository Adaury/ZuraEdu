@extends('layouts.portal')

@section('page-title', 'Mis Estadísticas — Portal Docente')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    <div class="prt-sidebar-section">Mi Portal</div>
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-sidebar-link">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.mis-estadisticas') }}" class="prt-sidebar-link active">
        <i class="bi bi-bar-chart-fill"></i>Mis Estadísticas
    </a>
    @if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
    <div class="prt-sidebar-section mt-2">Dirección</div>
    <a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
    </a>
    @endif
    <div class="prt-sidebar-section mt-2">Cuenta</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
            <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
        </button>
    </form>
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Mis Estadísticas</h1>
        <div style="font-size:.75rem;color:#64748b;">{{ $docente->nombre_completo }} · {{ $schoolYear?->nombre ?? 'Sin año escolar' }}</div>
    </div>
</div>

{{-- Tarjetas resumen --}}
<div class="prt-stats" style="margin-bottom:1.25rem;">
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $totalEstudiantes }}</div>
            <div class="prt-stat-lbl">Estudiantes</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-journals"></i></div>
        <div>
            <div class="prt-stat-val">{{ $asignaciones->count() }}</div>
            <div class="prt-stat-lbl">Asignaturas</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-calendar-check-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $pctAsistGlobal !== null ? $pctAsistGlobal.'%' : '—' }}</div>
            <div class="prt-stat-lbl">Asist. Promedio</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="bi bi-journal-text"></i></div>
        <div>
            <div class="prt-stat-val">{{ $totalPlanificaciones + $totalPlanesClase }}</div>
            <div class="prt-stat-lbl">Planificaciones</div>
        </div>
    </div>
</div>

{{-- Gráfica de barras: promedio por asignación --}}
@if(count($estadisticasPorAsignacion) > 0)
<div class="prt-card" style="margin-bottom:1.25rem;">
    <div class="prt-card-header">
        <i class="bi bi-bar-chart-fill" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Promedio por Asignatura</h3>
    </div>
    <div class="prt-card-body" style="padding:1rem;">
        <div style="position:relative;height:260px;">
            <canvas id="chartPromedios"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Tabla detallada por asignación --}}
<div class="prt-card" style="margin-bottom:1.25rem;">
    <div class="prt-card-header">
        <i class="bi bi-table" style="color:#2563eb;font-size:1rem;"></i>
        <h3>Detalle por Asignatura</h3>
    </div>
    <div class="prt-card-body" style="padding:0;overflow-x:auto;">
        @if(empty($estadisticasPorAsignacion))
            <div style="padding:2rem;text-align:center;color:#9ca3af;font-size:.84rem;">
                No tienes asignaciones activas este año.
            </div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:.6rem 1rem;text-align:left;font-weight:700;color:#374151;">Asignatura</th>
                    <th style="padding:.6rem .75rem;text-align:left;font-weight:700;color:#374151;">Grupo</th>
                    <th style="padding:.6rem .75rem;text-align:center;font-weight:700;color:#374151;">Promedio</th>
                    <th style="padding:.6rem .75rem;text-align:center;font-weight:700;color:#374151;">% Asistencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticasPorAsignacion as $i => $est)
                @php
                    $bgRow   = $i % 2 === 0 ? '#fff' : '#f8fafc';
                    $clrProm = $est['promedio'] === null ? '#9ca3af' : ($est['promedio'] >= 75 ? '#15803d' : ($est['promedio'] >= 60 ? '#d97706' : '#dc2626'));
                    $clrAsis = $est['pct_asist'] === null ? '#9ca3af' : ($est['pct_asist'] >= 80 ? '#15803d' : ($est['pct_asist'] >= 60 ? '#d97706' : '#dc2626'));
                @endphp
                <tr style="background:{{ $bgRow }};border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.65rem 1rem;font-weight:600;color:var(--prt-text);">{{ $est['asignatura'] }}</td>
                    <td style="padding:.65rem .75rem;color:#64748b;font-size:.76rem;">{{ $est['grupo'] }}</td>
                    <td style="padding:.65rem .75rem;text-align:center;">
                        @if($est['promedio'] !== null)
                            <span style="background:{{ $est['promedio'] >= 75 ? '#dcfce7' : ($est['promedio'] >= 60 ? '#fef9c3' : '#fee2e2') }};color:{{ $clrProm }};border-radius:6px;padding:.2rem .55rem;font-weight:700;font-size:.78rem;">
                                {{ $est['promedio'] }}
                            </span>
                        @else
                            <span style="color:#9ca3af;font-size:.76rem;">Sin datos</span>
                        @endif
                    </td>
                    <td style="padding:.65rem .75rem;text-align:center;">
                        @if($est['pct_asist'] !== null)
                            <span style="background:{{ $est['pct_asist'] >= 80 ? '#dcfce7' : ($est['pct_asist'] >= 60 ? '#fef9c3' : '#fee2e2') }};color:{{ $clrAsis }};border-radius:6px;padding:.2rem .55rem;font-weight:700;font-size:.78rem;">
                                {{ $est['pct_asist'] }}%
                            </span>
                        @else
                            <span style="color:#9ca3af;font-size:.76rem;">Sin datos</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Planificaciones y planes de clase --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.85rem;margin-bottom:1rem;">
    <div class="prt-card" style="margin:0;">
        <div class="prt-card-body" style="padding:1rem;display:flex;align-items:center;gap:.85rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-journal-text" style="color:#7c3aed;font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:900;color:var(--prt-text);">{{ $totalPlanificaciones }}</div>
                <div style="font-size:.72rem;color:#64748b;">Planificaciones (FP)</div>
            </div>
        </div>
    </div>
    <div class="prt-card" style="margin:0;">
        <div class="prt-card-body" style="padding:1rem;display:flex;align-items:center;gap:.85rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-file-earmark-text" style="color:#2563eb;font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:900;color:var(--prt-text);">{{ $totalPlanesClase }}</div>
                <div style="font-size:.72rem;color:#64748b;">Planes de Clase</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@if(count($estadisticasPorAsignacion) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = {!! $chartLabels !!};
    const data   = {!! $chartData !!};

    const colors = data.map(v =>
        v >= 75 ? 'rgba(34,197,94,0.8)' :
        v >= 60 ? 'rgba(234,179,8,0.8)' :
                  'rgba(239,68,68,0.8)'
    );
    const borders = data.map(v =>
        v >= 75 ? '#16a34a' : v >= 60 ? '#ca8a04' : '#dc2626'
    );

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
                        label: ctx => ' Promedio: ' + ctx.parsed.y
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,.06)' },
                    ticks: { font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, maxRotation: 30 }
                }
            }
        }
    });
})();
</script>
@endif
@endpush
