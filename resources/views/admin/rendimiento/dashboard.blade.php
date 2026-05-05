@extends('layouts.admin')

@section('page-title', 'Dashboard de Rendimiento')

@push('styles')
<style>
    .kpi-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        transition: box-shadow .18s;
    }
    .kpi-card:hover { box-shadow: 0 4px 20px rgba(30,58,110,.08); }
    .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .kpi-value { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .kpi-label { font-size: .78rem; color: #6b7280; font-weight: 500; margin-top: .25rem; }
    .semaforo-bar {
        height: 8px; border-radius: 4px;
        background: linear-gradient(90deg, #22c55e 0%, #f59e0b 60%, #ef4444 100%);
    }
    .group-row { border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; }
    .group-row:hover { background: #f8faff; }
    .progress-bar-custom { height: 6px; border-radius: 3px; }

    [data-theme="dark"] .kpi-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .kpi-label { color: #94a3b8; }
    [data-theme="dark"] .group-row { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .group-row:hover { background: #162032; }
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
            <i class="bi bi-bar-chart-line me-2"></i>Dashboard de Rendimiento
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">{{ $schoolYear->nombre }} — Visión institucional</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rendimiento.semaforo') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-circle-fill me-1" style="color:#22c55e;font-size:.5rem;"></i>Semáforo
        </a>
        <a href="{{ route('admin.rendimiento.pdf', request()->query()) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.excel', request()->query()) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <form method="POST" action="{{ route('admin.rendimiento.recalcular') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Recalcular
            </button>
        </form>
    </div>
</div>

{{-- Filtro período --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0" style="font-size:.85rem;">Período:</label>
            <select name="periodo_id" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                <option value="">Anual (todos los períodos)</option>
                @foreach($periodos as $p)
                <option value="{{ $p->id }}" {{ $periodoId == $p->id ? 'selected' : '' }}>
                    {{ $p->nombre }}
                </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card d-flex align-items-center gap-3">
            <div class="kpi-icon" style="background:#dbeafe;">
                <i class="bi bi-graph-up" style="color:#1d4ed8;"></i>
            </div>
            <div>
                <div class="kpi-value" style="color:#1d4ed8;">
                    {{ $promedioInstitucional ? number_format($promedioInstitucional, 1) : '—' }}
                </div>
                <div class="kpi-label">Promedio Institucional</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card d-flex align-items-center gap-3">
            <div class="kpi-icon" style="background:#dcfce7;">
                <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>
            </div>
            <div>
                <div class="kpi-value" style="color:#16a34a;">{{ $tasaAprobacion ?? '—' }}%</div>
                <div class="kpi-label">Tasa de Aprobación</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card d-flex align-items-center gap-3">
            <div class="kpi-icon" style="background:#fee2e2;">
                <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;"></i>
            </div>
            <div>
                <div class="kpi-value" style="color:#dc2626;">{{ $totalRiesgo }}</div>
                <div class="kpi-label">Estudiantes en Riesgo</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card d-flex align-items-center gap-3">
            <div class="kpi-icon" style="background:#fef3c7;">
                <i class="bi bi-flag-fill" style="color:#d97706;"></i>
            </div>
            <div>
                <div class="kpi-value" style="color:#d97706;">{{ $gruposAlerta }}</div>
                <div class="kpi-label">Grupos en Alerta</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabla de grupos --}}
@if($cacheData->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No hay datos de rendimiento calculados aún. Haz clic en "Recalcular" para generar las métricas.
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="fw-bold mb-0">Rendimiento por Grupo</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="font-size:.78rem;background:#f8fafc;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">
                    <tr>
                        <th class="px-3 py-2">Grupo</th>
                        <th class="px-3 py-2 text-center">Promedio</th>
                        <th class="px-3 py-2 text-center">Estudiantes</th>
                        <th class="px-3 py-2">Distribución</th>
                        <th class="px-3 py-2 text-center">En Riesgo</th>
                        <th class="px-3 py-2 text-center">Semáforo</th>
                    </tr>
                </thead>
                <tbody style="font-size:.84rem;">
                    @foreach($cacheData as $cache)
                    <tr>
                        <td class="px-3 py-2 fw-semibold">
                            {{ optional($cache->grupo)->nombre_corto ?? 'Grupo ' . $cache->grupo_id }}
                        </td>
                        <td class="px-3 py-2 text-center fw-bold">
                            <span style="color:{{ $cache->semaforo === 'success' ? '#16a34a' : ($cache->semaforo === 'warning' ? '#d97706' : '#dc2626') }};">
                                {{ $cache->promedio_grupo ? number_format($cache->promedio_grupo, 1) : '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center">{{ $cache->total_estudiantes }}</td>
                        <td class="px-3 py-2" style="min-width:140px;">
                            <div class="d-flex" style="height:8px;border-radius:4px;overflow:hidden;">
                                @if($cache->total_estudiantes > 0)
                                <div style="width:{{ $cache->pct_excelente }}%;background:#22c55e;" title="Excelente: {{ $cache->pct_excelente }}%"></div>
                                <div style="width:{{ $cache->pct_bueno }}%;background:#84cc16;" title="Bueno: {{ $cache->pct_bueno }}%"></div>
                                <div style="width:{{ $cache->pct_regular }}%;background:#f59e0b;" title="Regular: {{ $cache->pct_regular }}%"></div>
                                <div style="width:{{ $cache->pct_bajo }}%;background:#ef4444;" title="Bajo: {{ $cache->pct_bajo }}%"></div>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between mt-1" style="font-size:.65rem;color:#9ca3af;">
                                <span>{{ number_format($cache->pct_excelente, 0) }}% Exc</span>
                                <span>{{ number_format($cache->pct_bajo, 0) }}% Bajo</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($cache->total_riesgo > 0)
                            <span class="badge text-bg-danger">{{ $cache->total_riesgo }}</span>
                            @else
                            <span class="text-success"><i class="bi bi-check-circle"></i></span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="badge text-bg-{{ $cache->semaforo }}">
                                {{ $cache->semaforo === 'success' ? '●' : ($cache->semaforo === 'warning' ? '●' : '●') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex gap-3 mt-3" style="font-size:.78rem;color:#6b7280;">
    <span><span style="color:#22c55e;">●</span> Excelente ≥90</span>
    <span><span style="color:#84cc16;">●</span> Bueno ≥80</span>
    <span><span style="color:#f59e0b;">●</span> Regular ≥70</span>
    <span><span style="color:#ef4444;">●</span> En Riesgo &lt;70</span>
</div>
@endif

{{-- ── Gráfica: Evolución promedio por período ────────────────────── --}}
@if(!isset($sinAnio) && $periodos->count() > 1 && $cacheData->isNotEmpty())
@php
    // Construir promedio institucional por período desde CalificacionAcademica
    $promediosPorPeriodo = [];
    foreach ($periodos as $p) {
        $promP = \App\Models\CalificacionAcademica::where('school_year_id', $schoolYear->id)
            ->where('publicado', true)
            ->whereNotNull("nota_final")
            ->avg('nota_final');
        $promediosPorPeriodo[$p->nombre] = round($promP ?? 0, 1);
    }
@endphp
<div class="card border-0 shadow-sm mt-4" style="border-radius:16px;">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Evolución</div>
                <div style="font-size:1rem;font-weight:800;color:#111827;">Promedio Institucional por Período</div>
            </div>
            <i class="bi bi-graph-up-arrow" style="color:var(--primary);font-size:1.4rem;"></i>
        </div>
        <canvas id="chartEvolucion" height="80"></canvas>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartEvolucion'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_keys($promediosPorPeriodo)) !!},
        datasets: [{
            label: 'Promedio',
            data: {!! json_encode(array_values($promediosPorPeriodo)) !!},
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,.12)',
            borderWidth: 3,
            pointRadius: 6,
            pointBackgroundColor: '#2563eb',
            fill: true,
            tension: .35,
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` Promedio: ${ctx.parsed.y}` } },
        },
        scales: {
            y: {
                min: 50, max: 100,
                ticks: { color: '#6b7280', font: { size: 11 } },
                grid: { color: '#f1f5f9' },
            },
            x: { ticks: { color: '#6b7280', font: { size: 11 } }, grid: { display: false } },
        },
    },
});
</script>
@endpush
@endif

@endif
@endsection
