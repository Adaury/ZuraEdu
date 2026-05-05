@extends('layouts.admin')

@section('page-title', 'Comparativo por Período')

@push('styles')
<style>
    .comp-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1.5rem;
    }
    [data-theme="dark"] .comp-card { background: #1e293b; border-color: #334155; }

    .var-badge {
        display: inline-flex; align-items: center; gap: .2rem;
        font-size: .72rem; font-weight: 700; padding: .15rem .45rem;
        border-radius: 99px;
    }
    .var-pos { background: #dcfce7; color: #15803d; }
    .var-neg { background: #fee2e2; color: #b91c1c; }
    .var-neu { background: #f1f5f9; color: #64748b; }

    .chart-wrap { position: relative; height: 340px; }
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
            <i class="bi bi-bar-chart-steps me-2"></i>Comparativo por Período
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            {{ $schoolYear->nombre }} — Promedios P1 → P4 por asignatura
        </p>
    </div>
    <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Filtro --}}
<div class="comp-card mb-4">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-semibold mb-0" style="font-size:.85rem;">Grupo:</label>
        <select name="grupo_id" class="form-select form-select-sm" style="max-width:240px;" onchange="this.form.submit()">
            <option value="">— Seleccionar —</option>
            @foreach($grupos as $g)
            <option value="{{ $g->id }}" {{ $grupoId == $g->id ? 'selected' : '' }}>
                {{ $g->nombre_completo ?? $g->grado?->nombre . ' ' . $g->seccion?->nombre }}
            </option>
            @endforeach
        </select>
        @if($grupoId)
        <span class="text-muted" style="font-size:.8rem;">
            <i class="bi bi-info-circle me-1"></i>Mostrando promedios grupales por período
        </span>
        @endif
    </form>
</div>

@if($grupoId && count($tablaData) > 0)

{{-- Gráfica de barras --}}
<div class="comp-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Chart.js</div>
            <div class="fw-bold" style="color:#111827;">Promedios por asignatura y período</div>
        </div>
        <i class="bi bi-bar-chart-fill" style="color:#2563eb;font-size:1.4rem;"></i>
    </div>
    <div class="chart-wrap">
        <canvas id="chartComparativo"></canvas>
    </div>
</div>

{{-- Tabla --}}
<div class="comp-card">
    <div class="fw-bold mb-3" style="color:#111827;">
        <i class="bi bi-table me-2" style="color:#2563eb;"></i>Detalle por Asignatura
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.84rem;">
            <thead style="background:#f8fafc;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">
                <tr>
                    <th class="px-3 py-2">Asignatura</th>
                    <th class="px-3 py-2 text-center">P1</th>
                    <th class="px-3 py-2 text-center">P2</th>
                    <th class="px-3 py-2 text-center">P3</th>
                    <th class="px-3 py-2 text-center">P4</th>
                    <th class="px-3 py-2 text-center">P1→P2</th>
                    <th class="px-3 py-2 text-center">P2→P3</th>
                    <th class="px-3 py-2 text-center">P3→P4</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tablaData as $row)
                @php
                    $periodos = ['p1'=>$row['p1'], 'p2'=>$row['p2'], 'p3'=>$row['p3'], 'p4'=>$row['p4']];
                    $variaciones = [];
                    $pKeys = array_keys($periodos);
                    for ($i = 0; $i < 3; $i++) {
                        $a = $periodos[$pKeys[$i]];
                        $b = $periodos[$pKeys[$i+1]];
                        if ($a !== null && $b !== null) {
                            $diff = round($b - $a, 1);
                            $variaciones[] = $diff;
                        } else {
                            $variaciones[] = null;
                        }
                    }
                @endphp
                <tr>
                    <td class="px-3 py-2 fw-semibold">{{ $row['asignatura'] }}</td>
                    @foreach(['p1','p2','p3','p4'] as $pk)
                    <td class="px-3 py-2 text-center">
                        @if($row[$pk] !== null)
                        <span class="fw-bold" style="color:{{ $row[$pk] >= 70 ? '#15803d' : ($row[$pk] >= 60 ? '#b45309' : '#b91c1c') }};">
                            {{ number_format($row[$pk], 1) }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    @endforeach
                    @foreach($variaciones as $var)
                    <td class="px-3 py-2 text-center">
                        @if($var !== null)
                        <span class="var-badge {{ $var > 0 ? 'var-pos' : ($var < 0 ? 'var-neg' : 'var-neu') }}">
                            {{ $var > 0 ? '↑' : ($var < 0 ? '↓' : '—') }}
                            {{ $var != 0 ? abs($var) : '' }}
                        </span>
                        @else
                        <span class="text-muted" style="font-size:.75rem;">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($grupoId)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay calificaciones registradas para este grupo aún.
</div>
@else
<div class="alert alert-secondary">
    <i class="bi bi-hand-index me-2"></i>Selecciona un grupo para ver el comparativo.
</div>
@endif

@endif
@endsection

@push('scripts')
@if(!empty($chartData) && count($chartData) > 0 && isset($chartData['labels']) && count($chartData['labels']) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ctx = document.getElementById('chartComparativo');
    if (!ctx) return;

    const data = {!! json_encode($chartData) !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(ds => ({
                ...ds,
                borderRadius: 4,
                borderSkipped: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { size: 12 }, padding: 16 },
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y ?? '—'}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10 },
                        maxRotation: 35,
                        minRotation: 20,
                    },
                    grid: { display: false },
                },
                y: {
                    min: 0,
                    max: 100,
                    ticks: { color: '#6b7280', font: { size: 11 } },
                    grid: { color: '#f1f5f9' },
                },
            },
        },
    });
})();
</script>
@endif
@endpush
