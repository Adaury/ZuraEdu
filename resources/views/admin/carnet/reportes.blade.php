@extends('layouts.admin')
@section('page-title', 'Carnet+ — Reportes')

@push('styles')
<style>
    .kpi-card {
        background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
        box-shadow: 0 1px 4px rgba(0,0,0,.07); padding: 1.25rem 1.5rem;
    }
    .kpi-value { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .kpi-label { font-size: .8rem; color: #6b7280; font-weight: 600; margin-top: .2rem; }
    .risk-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .6rem 0; border-bottom: 1px solid #f3f4f6;
    }
    .risk-row:last-child { border-bottom: none; }
    [data-theme="dark"] .kpi-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Carnet+',   'url' => route('admin.carnet.index')],
    ['label' => 'Reportes'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-bar-chart-fill me-2"></i>Dashboard Asistencia — Carnet+
    </h4>
    <form class="d-flex gap-2 align-items-center">
        <input type="date" name="fecha" class="form-control form-control-sm" style="max-width:160px;"
               value="{{ $fecha->toDateString() }}">
        <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Ver</button>
    </form>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-value" style="color:#16a34a;">{{ $entradas }}</div>
            <div class="kpi-label"><i class="bi bi-box-arrow-in-right me-1 text-success"></i>Entradas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-value" style="color:#dc2626;">{{ $ausentes }}</div>
            <div class="kpi-label"><i class="bi bi-person-x me-1 text-danger"></i>Ausentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-value" style="color:#d97706;">{{ $tardanzas }}</div>
            <div class="kpi-label"><i class="bi bi-clock me-1 text-warning"></i>Tardanzas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-value" style="color:#6366f1;">
                {{ $total > 0 ? round(($entradas / $total) * 100) : 0 }}%
            </div>
            <div class="kpi-label"><i class="bi bi-percent me-1" style="color:#6366f1;"></i>Asistencia</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Actividad por hora --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock me-1 text-primary"></i>Actividad por Hora</h6>
                <div id="chartHora"></div>
            </div>
        </div>
    </div>

    {{-- Tendencia 7 días --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-1 text-success"></i>Tendencia (últimos 7 días)</h6>
                <div id="chartTend"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Donut estado del día --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-1" style="color:#7c3aed;"></i>Estado del día</h6>
                <div id="chartDonut"></div>
                <div class="d-flex justify-content-center gap-3 mt-2" style="font-size:.8rem;">
                    <span><span class="badge bg-success">&nbsp;</span> Presentes: {{ $entradas - $tardanzas }}</span>
                    <span><span class="badge bg-warning">&nbsp;</span> Tardanzas: {{ $tardanzas }}</span>
                    <span><span class="badge bg-danger">&nbsp;</span> Ausentes: {{ $ausentes }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Estudiantes en riesgo --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-exclamation-triangle me-1 text-danger"></i>Estudiantes en riesgo de abandono
                    <span class="badge ms-2" style="background:#fee2e2;color:#991b1b;font-size:.72rem;">{{ $ausRecurrentes->count() }} detectados</span>
                </h6>
            </div>
            <div class="card-body">
                @forelse($ausRecurrentes as $carnet)
                <div class="risk-row">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($carnet->nombre_completo, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:.88rem;">{{ $carnet->nombre_completo }}</div>
                        <div class="text-muted" style="font-size:.76rem;">{{ $carnet->matricula?->grupo?->nombre_completo ?? '—' }}</div>
                    </div>
                    <span class="badge bg-danger" style="font-size:.75rem;">Alto riesgo</span>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.85rem;">
                    <i class="bi bi-check-circle text-success" style="font-size:1.5rem;display:block;margin-bottom:.3rem;"></i>
                    No hay estudiantes en riesgo alto esta semana.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
<script>
(function() {
    // Actividad por hora
    new ApexCharts(document.getElementById('chartHora'), {
        chart: { type: 'bar', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Accesos', data: {!! json_encode($horaSeries) !!} }],
        xaxis: { categories: {!! json_encode($horaLabels) !!}, labels: { style: { fontSize: '11px' } } },
        colors: ['#2563eb'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f3f4f6' },
    }).render();

    // Tendencia 7 días
    new ApexCharts(document.getElementById('chartTend'), {
        chart: { type: 'area', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Entradas', data: {!! json_encode($tendValues) !!} }],
        xaxis: { categories: {!! json_encode($tendDias) !!}, labels: { style: { fontSize: '11px' } } },
        colors: ['#16a34a'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f3f4f6' },
    }).render();

    // Donut
    new ApexCharts(document.getElementById('chartDonut'), {
        chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
        series: [{{ $entradas - $tardanzas }}, {{ $tardanzas }}, {{ $ausentes }}],
        labels: ['Presentes', 'Tardanzas', 'Ausentes'],
        colors: ['#16a34a', '#d97706', '#dc2626'],
        legend: { position: 'bottom', fontSize: '12px' },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
    }).render();
})();
</script>
@endpush
