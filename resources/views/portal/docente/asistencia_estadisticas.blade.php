@extends('layouts.portal')
@section('page-title', 'Estadísticas de Asistencia')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'asistencia', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
.ae-pct-bar-wrap {
    height: 6px;
    background: #e5e7eb;
    border-radius: 99px;
    overflow: hidden;
    margin-top: .25rem;
    min-width: 60px;
}
.ae-pct-bar {
    height: 6px;
    border-radius: 99px;
    transition: width .4s;
}
.ae-badge {
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    border-radius: 99px;
    padding: .15rem .5rem;
    font-size: .65rem;
    font-weight: 700;
    white-space: nowrap;
}
.ae-ok   { background: #dcfce7; color: #15803d; }
.ae-med  { background: #fef9c3; color: #b45309; }
.ae-low  { background: #fee2e2; color: #991b1b; }
.ae-nil  { background: #f3f4f6; color: #6b7280; }
.ae-dia-bar {
    height: 28px;
    border-radius: 6px 6px 0 0;
    background: #fee2e2;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    transition: height .3s;
    min-height: 4px;
}
.ae-dia-label { font-size: .68rem; color: #64748b; font-weight: 600; text-align: center; margin-top: .3rem; }
.ae-table th { font-size: .72rem; font-weight: 700; color: #64748b; padding: .5rem .6rem; white-space: nowrap; }
.ae-table td { font-size: .8rem; padding: .55rem .6rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.ae-table tr:last-child td { border-bottom: none; }
.ae-table tr.critico td:first-child { border-left: 3px solid #dc2626; }
[data-theme="dark"] .ae-pct-bar-wrap { background: #334155; }
[data-theme="dark"] .ae-table td { border-color: #1e293b; }
</style>
@endpush

@section('content')

{{-- Cabecera ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-bar-chart-line-fill" style="color:#2563eb;"></i>
            Estadísticas de Asistencia
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.1rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-shrink:0;">
        <a href="{{ route('portal.docente.asistencia.pdf', $asignacion) }}" target="_blank"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:.38rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <a href="{{ route('portal.docente.asistencia.excel', $asignacion) }}"
           style="background:#166534;color:#fff;border-radius:8px;padding:.38rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>
</div>

{{-- KPIs ────────────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.65rem;margin-bottom:1.1rem;">
    <div class="prt-card" style="padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.8rem;font-weight:900;
            color:{{ $pctGrupo === null ? '#94a3b8' : ($pctGrupo >= 85 ? '#15803d' : ($pctGrupo >= 75 ? '#b45309' : '#991b1b')) }};">
            {{ $pctGrupo !== null ? $pctGrupo . '%' : '—' }}
        </div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Asist. del grupo</div>
    </div>
    <div class="prt-card" style="padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.8rem;font-weight:900;color:{{ $criticos > 0 ? '#dc2626' : '#15803d' }};">
            {{ $criticos }}
        </div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Críticos (&lt;{{ $umbral }}%)</div>
    </div>
    <div class="prt-card" style="padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.8rem;font-weight:900;color:#2563eb;">{{ $diasRegistrados }}</div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Días de clase</div>
    </div>
    <div class="prt-card" style="padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.8rem;font-weight:900;color:#374151;">{{ $matriculas->count() }}</div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Estudiantes</div>
    </div>
</div>

{{-- Evolución mensual ───────────────────────────────────────────────────── --}}
@if(json_decode($chartLabels))
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-graph-up" style="color:#2563eb;"></i>
        <h3>Evolución mensual del grupo</h3>
    </div>
    <div style="padding:1rem;">
        <canvas id="chartAsistencia" height="120"></canvas>
    </div>
</div>
@endif

{{-- Ausencias por día de la semana ─────────────────────────────────────── --}}
@php
$maxDia = max(array_max($ausenciaPorDia), 1);
@endphp
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-week" style="color:#7c3aed;"></i>
        <h3>Ausencias por día de la semana</h3>
    </div>
    <div style="padding:1rem;display:flex;align-items:flex-end;gap:.5rem;justify-content:center;">
        @foreach($diasSemana as $i => $dia)
        @php $n = $ausenciaPorDia[$i]; $h = $maxDia > 0 ? max(4, round($n / $maxDia * 80)) : 4; @endphp
        <div style="display:flex;flex-direction:column;align-items:center;flex:1;max-width:52px;">
            <div style="font-size:.7rem;color:#991b1b;font-weight:700;margin-bottom:.2rem;">{{ $n > 0 ? $n : '' }}</div>
            <div class="ae-dia-bar" style="height:{{ $h }}px;width:100%;background:{{ $n > 0 ? '#fecaca' : '#f1f5f9' }};border:1.5px solid {{ $n > 0 ? '#f87171' : '#e5e7eb' }};"></div>
            <div class="ae-dia-label">{{ $dia }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Tabla por estudiante ────────────────────────────────────────────────── --}}
<div class="prt-card">
    <div class="prt-card-header" style="flex-wrap:wrap;gap:.5rem;">
        <i class="bi bi-people-fill" style="color:#2563eb;"></i>
        <h3>Detalle por estudiante</h3>
        @if($criticos > 0)
        <span style="margin-left:auto;background:#fee2e2;color:#991b1b;border-radius:6px;padding:.2rem .55rem;font-size:.7rem;font-weight:700;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $criticos }} por debajo del {{ $umbral }}%
        </span>
        @endif
    </div>

    {{-- Filtro rápido --}}
    <div style="padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="ae-filter-btn active" onclick="filtrarTabla('todos', this)"
                style="border:none;border-radius:6px;padding:.22rem .6rem;font-size:.7rem;font-weight:700;cursor:pointer;background:var(--primary);color:#fff;">
            Todos
        </button>
        <button class="ae-filter-btn" onclick="filtrarTabla('critico', this)"
                style="border:none;border-radius:6px;padding:.22rem .6rem;font-size:.7rem;font-weight:700;cursor:pointer;background:#fee2e2;color:#991b1b;">
            Críticos
        </button>
        <button class="ae-filter-btn" onclick="filtrarTabla('ok', this)"
                style="border:none;border-radius:6px;padding:.22rem .6rem;font-size:.7rem;font-weight:700;cursor:pointer;background:#dcfce7;color:#15803d;">
            ≥ {{ $umbral }}%
        </button>
    </div>

    <div style="overflow-x:auto;">
        <table class="ae-table w-100">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="min-width:130px;">Estudiante</th>
                    <th style="text-align:center;">Días</th>
                    <th style="text-align:center;color:#15803d;">Pres.</th>
                    <th style="text-align:center;color:#b45309;">Tarde</th>
                    <th style="text-align:center;color:#1d4ed8;">Excusa</th>
                    <th style="text-align:center;color:#991b1b;">Aus.</th>
                    @foreach($periodos as $p)
                    <th style="text-align:center;">{{ $p->nombre }}</th>
                    @endforeach
                    <th style="min-width:100px;">% Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($porEstudiante->sortBy('pct') as $row)
                @php
                    $pct  = $row['pct'];
                    $cls  = $row['critico'] ? 'low' : ($pct >= 85 ? 'ok' : 'med');
                    $color = $row['critico'] ? '#dc2626' : ($pct >= 85 ? '#15803d' : '#b45309');
                @endphp
                <tr class="{{ $row['critico'] ? 'critico' : '' }}" data-tipo="{{ $row['critico'] ? 'critico' : 'ok' }}">
                    <td>
                        <div style="font-weight:600;color:#1e293b;font-size:.82rem;line-height:1.2;">
                            @if($row['critico'])
                            <i class="bi bi-exclamation-circle-fill me-1" style="color:#dc2626;font-size:.75rem;"></i>
                            @endif
                            {{ $row['matricula']->estudiante?->nombre_completo ?? '—' }}
                        </div>
                    </td>
                    <td style="text-align:center;color:#374151;">{{ $row['total'] ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($row['presente'] > 0)
                        <span class="ae-badge ae-ok">{{ $row['presente'] }}</span>
                        @else
                        <span style="color:#d1d5db;">0</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($row['tarde'] > 0)
                        <span class="ae-badge ae-med">{{ $row['tarde'] }}</span>
                        @else
                        <span style="color:#d1d5db;">0</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($row['excusa'] > 0)
                        <span class="ae-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $row['excusa'] }}</span>
                        @else
                        <span style="color:#d1d5db;">0</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($row['ausente'] > 0)
                        <span class="ae-badge ae-low">{{ $row['ausente'] }}</span>
                        @else
                        <span style="color:#d1d5db;">0</span>
                        @endif
                    </td>
                    @foreach($periodos as $p)
                    @php $pd = $row['periodos'][$p->numero] ?? ['pct' => null, 'total' => 0]; @endphp
                    <td style="text-align:center;font-size:.75rem;">
                        @if($pd['pct'] !== null)
                        <span class="ae-badge {{ $pd['pct'] >= $umbral ? 'ae-ok' : 'ae-low' }}">
                            {{ $pd['pct'] }}%
                        </span>
                        @else
                        <span style="color:#d1d5db;font-size:.72rem;">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td>
                        @if($pct !== null)
                        <div style="display:flex;align-items:center;gap:.4rem;">
                            <span style="font-size:.82rem;font-weight:800;color:{{ $color }};min-width:34px;">{{ $pct }}%</span>
                            <div style="flex:1;">
                                <div class="ae-pct-bar-wrap">
                                    <div class="ae-pct-bar" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                                </div>
                            </div>
                        </div>
                        @else
                        <span style="color:#d1d5db;font-size:.75rem;">Sin registros</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 7 + $periodos->count() }}" style="text-align:center;padding:2rem;color:#9ca3af;">
                        Sin estudiantes matriculados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Gráfica de evolución mensual
@if(json_decode($chartLabels))
(function () {
    const ctx = document.getElementById('chartAsistencia');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! $chartLabels !!},
            datasets: [{
                label: '% Asistencia',
                data: {!! $chartData !!},
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.1)',
                fill: true,
                tension: .35,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb',
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y !== null ? ctx.parsed.y + '%' : '—',
                    },
                },
                // Línea de referencia umbral
                annotation: {
                    annotations: {
                        umbral: {
                            type: 'line',
                            yMin: {{ $umbral }},
                            yMax: {{ $umbral }},
                            borderColor: '#dc2626',
                            borderWidth: 1.5,
                            borderDash: [5, 4],
                            label: {
                                display: true,
                                content: '{{ $umbral }}% mín.',
                                font: { size: 10 },
                                color: '#dc2626',
                                position: 'start',
                            },
                        },
                    },
                },
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: v => v + '%',
                        font: { size: 10 },
                        stepSize: 20,
                    },
                    grid: { color: '#f1f5f9' },
                },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
            },
        },
    });
})();
@endif

// Filtrar tabla por tipo
function filtrarTabla(tipo, btn) {
    document.querySelectorAll('.ae-filter-btn').forEach(b => {
        b.style.background = b.dataset.activeStyle || b.style.background;
        b.classList.remove('active');
    });
    btn.classList.add('active');

    document.querySelectorAll('#tablaEstudiantes tr[data-tipo], .ae-table tbody tr[data-tipo]').forEach(tr => {
        if (tipo === 'todos') {
            tr.style.display = '';
        } else if (tipo === 'critico') {
            tr.style.display = tr.dataset.tipo === 'critico' ? '' : 'none';
        } else {
            tr.style.display = tr.dataset.tipo === 'ok' ? '' : 'none';
        }
    });
}
</script>
@endpush
