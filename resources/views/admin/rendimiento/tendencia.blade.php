@extends('layouts.admin')

@section('page-title', 'Tendencia por Grupo')

@push('styles')
<style>
    .tend-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1.5rem;
    }
    [data-theme="dark"] .tend-card { background: #1e293b; border-color: #334155; }

    .tend-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .4rem 1rem; border-radius: 99px;
        font-weight: 700; font-size: .9rem;
    }
    .tend-up   { background: #dcfce7; color: #15803d; }
    .tend-down { background: #fee2e2; color: #b91c1c; }
    .tend-flat { background: #f1f5f9; color: #64748b; }

    .chart-wrap { position: relative; height: 300px; }

    .periodo-pill {
        display: inline-flex; flex-direction: column; align-items: center;
        padding: .75rem 1.25rem; border-radius: 12px;
        border: 2px solid #e5e7eb; min-width: 80px;
    }
    .periodo-pill.activo { border-color: #2563eb; background: #eff6ff; }
    [data-theme="dark"] .periodo-pill { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .periodo-pill.activo { background: #1e3a5f; border-color: #3b82f6; }
</style>
@endpush

@section('content')

@if(!empty($sinAnio))
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No hay un año escolar activo configurado.</div>
@else

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-graph-up-arrow me-2"></i>Tendencia por Grupo
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            {{ $schoolYear->nombre }} — Evolución del promedio general P1 → P4
        </p>
    </div>
    <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Filtro --}}
<div class="tend-card mb-4">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-semibold mb-0" style="font-size:.85rem;">Grupo:</label>
        <select name="grupo_id" class="form-select form-select-sm" style="max-width:260px;" onchange="this.form.submit()">
            <option value="">— Seleccionar —</option>
            @foreach($grupos as $g)
            <option value="{{ $g->id }}" {{ $grupoId == $g->id ? 'selected' : '' }}>
                {{ $g->nombre_completo ?? $g->grado?->nombre . ' ' . $g->seccion?->nombre }}
            </option>
            @endforeach
        </select>
    </form>
</div>

@if($grupoId)

@php
    $conDatos = array_filter($promediosPeriodos, fn($v) => $v !== null);
@endphp

@if(count($conDatos) > 0)

{{-- Indicador de tendencia --}}
@if($tendencia !== null)
<div class="tend-card mb-4 d-flex align-items-center gap-4 flex-wrap">
    <div>
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Tendencia General</div>
        <div class="fw-bold mt-1" style="color:#111827;font-size:1rem;">Primer período con datos → Último período con datos</div>
    </div>
    <div>
        @if($tendencia['positiva'])
        <span class="tend-badge tend-up">
            <i class="bi bi-arrow-up-circle-fill"></i>
            +{{ $tendencia['valor'] }} pts &nbsp;Tendencia positiva
        </span>
        @elseif($tendencia['valor'] < 0)
        <span class="tend-badge tend-down">
            <i class="bi bi-arrow-down-circle-fill"></i>
            {{ $tendencia['valor'] }} pts &nbsp;Tendencia negativa
        </span>
        @else
        <span class="tend-badge tend-flat">
            <i class="bi bi-dash-circle-fill"></i>
            Sin variación
        </span>
        @endif
    </div>
</div>
@endif

{{-- Pills resumen --}}
<div class="d-flex gap-3 mb-4 flex-wrap">
    @foreach($promediosPeriodos as $label => $val)
    <div class="periodo-pill {{ $val !== null ? 'activo' : '' }}">
        <span style="font-size:.72rem;font-weight:600;color:#6b7280;">{{ $label }}</span>
        <span class="fw-bold mt-1" style="font-size:1.15rem;color:{{ $val !== null ? '#1d4ed8' : '#9ca3af' }};">
            {{ $val !== null ? number_format($val, 1) : '—' }}
        </span>
    </div>
    @endforeach
</div>

{{-- Gráfica de líneas --}}
<div class="tend-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="fw-bold" style="color:#111827;">
            <i class="bi bi-activity me-2" style="color:#2563eb;"></i>Evolución del Promedio General
        </div>
        <i class="bi bi-graph-up" style="color:#2563eb;font-size:1.3rem;"></i>
    </div>
    <div class="chart-wrap">
        <canvas id="chartTendencia"></canvas>
    </div>

    {{-- Zona inferior: nota mínima / máxima --}}
    @php
        $vals = array_filter(array_values($promediosPeriodos), fn($v) => $v !== null);
        $min  = count($vals) ? min($vals) : null;
        $max  = count($vals) ? max($vals) : null;
    @endphp
    @if($min !== null)
    <div class="d-flex gap-4 mt-3 flex-wrap" style="font-size:.8rem;color:#6b7280;">
        <span><strong style="color:#dc2626;">Mín:</strong> {{ number_format($min, 1) }}</span>
        <span><strong style="color:#16a34a;">Máx:</strong> {{ number_format($max, 1) }}</span>
        @if(count($vals) >= 2)
        <span><strong>Rango:</strong> {{ number_format($max - $min, 1) }} pts</span>
        @endif
    </div>
    @endif
</div>

@else
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay calificaciones registradas para este grupo aún.
</div>
@endif

@else
<div class="alert alert-secondary">
    <i class="bi bi-hand-index me-2"></i>Selecciona un grupo para ver la tendencia.
</div>
@endif

@endif
@endsection

@push('scripts')
@if(!empty($chartData) && isset($chartData['labels']) && count(array_filter($chartData['data'] ?? [], fn($v) => $v !== null)) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ctx = document.getElementById('chartTendencia');
    if (!ctx) return;

    const raw = {!! json_encode($chartData) !!};

    // Filtrar nulls para que Chart.js no dibuje puntos vacíos como 0
    const labels  = raw.labels;
    const data    = raw.data;

    // Calcular gradiente de color según tendencia
    const filled = data.filter(v => v !== null);
    const isUp   = filled.length >= 2 && filled[filled.length - 1] >= filled[0];
    const color  = isUp ? '#16a34a' : '#dc2626';
    const bgAlpha = isUp ? 'rgba(22,163,74,.10)' : 'rgba(220,38,38,.10)';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio del grupo',
                data: data,
                borderColor: color,
                backgroundColor: bgAlpha,
                borderWidth: 3,
                pointRadius: 7,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                fill: true,
                tension: .3,
                spanGaps: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y !== null
                            ? ` Promedio: ${ctx.parsed.y}`
                            : ' Sin datos',
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: '#6b7280', font: { size: 12, weight: '600' } },
                    grid: { display: false },
                },
                y: {
                    min: 40,
                    max: 100,
                    ticks: { color: '#6b7280', font: { size: 11 }, stepSize: 10 },
                    grid: { color: '#f1f5f9' },
                },
            },
        },
    });
})();
</script>
@endif
@endpush
