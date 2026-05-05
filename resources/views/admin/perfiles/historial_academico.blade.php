@extends('layouts.admin')

@section('page-title', 'Historial Académico — ' . $estudiante->nombre_completo)

@push('styles')
<style>
    .hist-hero {
        background: linear-gradient(135deg, #1e3a6e 0%, #1d4ed8 100%);
        border-radius: 14px;
        color: #fff;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .hist-hero::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,.06);
        border-radius: 50%;
    }
    .hist-hero::after {
        content: '';
        position: absolute; bottom: -30px; right: 80px;
        width: 100px; height: 100px;
        background: rgba(255,255,255,.04);
        border-radius: 50%;
    }
    .hist-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; font-weight: 900; color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.35);
    }
    .year-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow .2s;
        margin-bottom: 1.25rem;
    }
    .year-card:hover { box-shadow: 0 4px 18px rgba(30,58,110,.12); }
    .year-header {
        background: #1e3a6e;
        color: #fff;
        padding: .75rem 1.25rem;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .5rem;
        cursor: pointer;
        user-select: none;
    }
    .year-header:hover { background: #1d4ed8; }
    .year-year  { font-size: .95rem; font-weight: 800; }
    .year-grado { font-size: .8rem; opacity: .85; }
    .year-badges { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
    .badge-prom {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 20px; padding: 3px 12px;
        font-size: .78rem; font-weight: 700; color: #fff;
    }
    .badge-prom.prom-ok  { background: rgba(34,197,94,.25); border-color: rgba(34,197,94,.5); }
    .badge-prom.prom-bad { background: rgba(239,68,68,.25); border-color: rgba(239,68,68,.5); }
    .badge-sit-prom { background: #22c55e; color: #052e16; border-radius: 20px; padding: 3px 10px; font-size: .72rem; font-weight: 700; }
    .badge-sit-rep  { background: #ef4444; color: #fff;    border-radius: 20px; padding: 3px 10px; font-size: .72rem; font-weight: 700; }
    .badge-sit-nd   { background: #94a3b8; color: #fff;    border-radius: 20px; padding: 3px 10px; font-size: .72rem; font-weight: 700; }
    .year-body { padding: 1rem 1.25rem; background: #fff; }
    .stat-row { display: flex; gap: .75rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .stat-chip {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 8px; padding: .4rem .85rem;
        font-size: .8rem; color: #374151;
        display: flex; align-items: center; gap: .35rem;
    }
    .stat-chip i { font-size: .85rem; }
    .stat-chip strong { color: #1e3a6e; }
    .asig-table { font-size: .82rem; }
    .asig-table th {
        background: #f1f5f9; color: #475569;
        font-size: .73rem; text-transform: uppercase;
        letter-spacing: .04em; font-weight: 700;
        padding: .5rem .75rem; border-bottom: 2px solid #e2e8f0;
    }
    .asig-table td { padding: .45rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .nota-ok  { color: #15803d; font-weight: 700; }
    .nota-mid { color: #b45309; font-weight: 700; }
    .nota-bad { color: #dc2626; font-weight: 700; }
    .sit-a { display: inline-flex; align-items: center; gap: 3px; background: #dcfce7; color: #15803d; border-radius: 4px; padding: 2px 8px; font-size: .72rem; font-weight: 700; }
    .sit-r { display: inline-flex; align-items: center; gap: 3px; background: #fee2e2; color: #dc2626; border-radius: 4px; padding: 2px 8px; font-size: .72rem; font-weight: 700; }
    .chart-wrap {
        background: #fff; border: 1.5px solid #e2e8f0;
        border-radius: 14px; padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .chart-title { font-size: .95rem; font-weight: 800; color: #1e3a6e; margin-bottom: 1rem; }
    .summary-kpi {
        background: #f8fafc; border: 1.5px solid #e2e8f0;
        border-radius: 12px; padding: 1.25rem 1rem;
        text-align: center; margin-bottom: 1rem;
    }
    .kpi-num { font-size: 2rem; font-weight: 900; color: #1e3a6e; line-height: 1; }
    .kpi-lbl { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-top: .25rem; }
    .empty-hist { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
    .empty-hist i { font-size: 3rem; display: block; margin-bottom: .75rem; }

    [data-theme="dark"] .year-card   { border-color: #334155; }
    [data-theme="dark"] .year-body   { background: #1e293b; }
    [data-theme="dark"] .asig-table th { background: #0f172a; color: #94a3b8; border-color: #334155; }
    [data-theme="dark"] .asig-table td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .stat-chip   { background: #0f172a; border-color: #334155; color: #e2e8f0; }
    [data-theme="dark"] .stat-chip strong { color: #93c5fd; }
    [data-theme="dark"] .chart-wrap  { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .summary-kpi { background: #0f172a; border-color: #334155; }
    [data-theme="dark"] .kpi-num     { color: #93c5fd; }
    [data-theme="dark"] .hist-hero   { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.perfiles.estudiante', $estudiante) }}">Perfil</a></li>
        <li class="breadcrumb-item active">Historial Académico</li>
    </ol>
</nav>

{{-- Hero --}}
<div class="hist-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="hist-avatar">
            {{ strtoupper(substr($estudiante->nombres ?? 'E', 0, 1)) }}{{ strtoupper(substr($estudiante->apellidos ?? '', 0, 1)) }}
        </div>
        <div>
            <h4 class="mb-0 fw-black" style="letter-spacing:-.01em;">
                {{ $estudiante->nombre_completo }}
            </h4>
            <div style="opacity:.8;font-size:.85rem;">
                <i class="bi bi-person-badge me-1"></i>
                Matrícula: {{ $estudiante->numero_matricula ?? '—' }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i>
                {{ $historial->count() }} año(s) académico(s)
            </div>
        </div>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.perfiles.estudiante.historial-pdf', $estudiante) }}"
               target="_blank"
               class="btn btn-sm"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:8px;backdrop-filter:blur(4px);">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF Oficial
            </a>
            <a href="{{ route('admin.perfiles.estudiante', $estudiante) }}"
               class="btn btn-sm"
               style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:8px;">
                <i class="bi bi-arrow-left me-1"></i>Volver al Perfil
            </a>
        </div>
    </div>
</div>

@if($historial->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="empty-hist">
        <i class="bi bi-journal-x"></i>
        <div style="font-size:1.1rem;font-weight:700;color:#374151;margin-bottom:.5rem;">Sin historial académico</div>
        <div style="font-size:.88rem;">Este estudiante no tiene matrículas registradas.</div>
    </div>
</div>
@else

<div class="row g-3 mb-4">
    {{-- KPI: Total años --}}
    <div class="col-6 col-md-3">
        <div class="summary-kpi">
            <div class="kpi-num">{{ $historial->count() }}</div>
            <div class="kpi-lbl">Años Cursados</div>
        </div>
    </div>
    {{-- KPI: Promedio global --}}
    @php
        $promediosValidos = $historial->pluck('promedio')->filter(fn($p) => $p !== null);
        $promedioGlobal   = $promediosValidos->count() > 0 ? round($promediosValidos->avg(), 1) : null;
        $totalAprobadas   = $historial->sum('aprobadas');
        $totalReprobadas  = $historial->sum('reprobadas');
    @endphp
    <div class="col-6 col-md-3">
        <div class="summary-kpi">
            <div class="kpi-num" style="color:{{ $promedioGlobal >= 70 ? '#15803d' : ($promedioGlobal !== null ? '#dc2626' : '#94a3b8') }};">
                {{ $promedioGlobal !== null ? $promedioGlobal : '—' }}
            </div>
            <div class="kpi-lbl">Promedio Histórico</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-kpi">
            <div class="kpi-num" style="color:#15803d;">{{ $totalAprobadas }}</div>
            <div class="kpi-lbl">Asignaturas Aprobadas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-kpi">
            <div class="kpi-num" style="color:{{ $totalReprobadas > 0 ? '#dc2626' : '#94a3b8' }};">{{ $totalReprobadas }}</div>
            <div class="kpi-lbl">Asignaturas Reprobadas</div>
        </div>
    </div>
</div>

{{-- Gráfica de evolución de promedios --}}
@if($historial->count() >= 2)
<div class="chart-wrap">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="chart-title">
            <i class="bi bi-graph-up-arrow me-2" style="color:#1d4ed8;"></i>
            Evolución del Promedio General
        </div>
        <div style="font-size:.78rem;color:#94a3b8;">Notas finales por año escolar</div>
    </div>
    <div style="position:relative;height:260px;">
        <canvas id="chartHistorial"></canvas>
    </div>
</div>
@endif

{{-- Historial por año (acordeón) --}}
<div x-data="{ openYear: {{ $historial->count() > 0 ? $historial->count() - 1 : 0 }} }">
    @foreach($historial as $idx => $h)
    @php
        $prom = $h['promedio'];
        $promClass = $prom === null ? '' : ($prom >= 80 ? 'prom-ok' : ($prom >= 70 ? 'prom-ok' : 'prom-bad'));
        $sitClass  = $h['situacion_general'] === 'Promovido' ? 'badge-sit-prom' :
                     ($h['situacion_general'] === 'Reprobado' ? 'badge-sit-rep' : 'badge-sit-nd');
    @endphp
    <div class="year-card">
        <div class="year-header" @click="openYear = (openYear === {{ $idx }}) ? -1 : {{ $idx }}">
            <div>
                <div class="year-year">
                    <i class="bi bi-calendar-event me-2"></i>
                    {{ $h['schoolYear']->nombre ?? '—' }}
                </div>
                <div class="year-grado">{{ $h['grado'] }} {{ $h['seccion'] }} · {{ $h['grupo'] }}</div>
            </div>
            <div class="year-badges">
                @if($prom !== null)
                <span class="badge-prom {{ $promClass }}">
                    <i class="bi bi-bar-chart-line me-1"></i>{{ number_format($prom, 1) }}
                </span>
                @endif
                <span class="{{ $sitClass }}">{{ $h['situacion_general'] }}</span>
                <i class="bi" :class="openYear === {{ $idx }} ? 'bi-chevron-up' : 'bi-chevron-down'"
                   style="font-size:1rem;color:rgba(255,255,255,.7);"></i>
            </div>
        </div>

        <div class="year-body" x-show="openYear === {{ $idx }}" x-collapse>

            {{-- Chips de estadísticas --}}
            <div class="stat-row">
                <div class="stat-chip">
                    <i class="bi bi-book" style="color:#1d4ed8;"></i>
                    <strong>{{ $h['total_asig'] }}</strong> asignaturas
                </div>
                <div class="stat-chip">
                    <i class="bi bi-check-circle" style="color:#15803d;"></i>
                    Aprobadas: <strong>{{ $h['aprobadas'] }}</strong>
                </div>
                @if($h['reprobadas'] > 0)
                <div class="stat-chip">
                    <i class="bi bi-x-circle" style="color:#dc2626;"></i>
                    Reprobadas: <strong style="color:#dc2626;">{{ $h['reprobadas'] }}</strong>
                </div>
                @endif
                @if($h['asistencia'] !== null)
                <div class="stat-chip">
                    <i class="bi bi-person-check" style="color:#0891b2;"></i>
                    Asistencia: <strong>{{ $h['asistencia'] }}%</strong>
                </div>
                @endif
                @if($h['promedio'] !== null)
                <div class="stat-chip">
                    <i class="bi bi-calculator" style="color:#7c3aed;"></i>
                    Promedio: <strong>{{ number_format($h['promedio'], 2) }}</strong>
                </div>
                @endif
            </div>

            {{-- Tabla de asignaturas --}}
            @if($h['califs']->isNotEmpty())
            <div class="table-responsive">
                <table class="table asig-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Asignatura</th>
                            <th class="text-center">P1</th>
                            <th class="text-center">P2</th>
                            <th class="text-center">P3</th>
                            <th class="text-center">P4</th>
                            <th class="text-center">Nota Final</th>
                            <th class="text-center">Situación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($h['califs'] as $i => $cal)
                        @php
                            $nf = $cal->nota_final;
                            $nfClass = $nf === null ? '' : ($nf >= 80 ? 'nota-ok' : ($nf >= 70 ? 'nota-mid' : 'nota-bad'));
                        @endphp
                        <tr>
                            <td class="text-muted" style="font-size:.76rem;">{{ $i + 1 }}</td>
                            <td>{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
                            <td class="text-center">
                                @if($cal->avg_comp1_p1 !== null)
                                    <span style="font-size:.8rem;">{{ number_format($cal->avg_comp1_p1, 0) }}</span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($cal->avg_comp1_p2 !== null)
                                    <span style="font-size:.8rem;">{{ number_format($cal->avg_comp1_p2, 0) }}</span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($cal->avg_comp1_p3 !== null)
                                    <span style="font-size:.8rem;">{{ number_format($cal->avg_comp1_p3, 0) }}</span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($cal->avg_comp1_p4 !== null)
                                    <span style="font-size:.8rem;">{{ number_format($cal->avg_comp1_p4, 0) }}</span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="{{ $nfClass }}" style="font-size:.9rem;">
                                    {{ $nf !== null ? number_format($nf, 1) : '—' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($cal->situacion === 'A')
                                    <span class="sit-a"><i class="bi bi-check-circle-fill"></i>Aprobado</span>
                                @elseif($cal->situacion === 'R')
                                    <span class="sit-r"><i class="bi bi-x-circle-fill"></i>Reprobado</span>
                                @else
                                    <span style="color:#94a3b8;font-size:.78rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if($h['promedio'] !== null)
                    <tfoot>
                        <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                            <td colspan="6" class="text-end fw-bold" style="font-size:.8rem;color:#475569;padding:.5rem .75rem;">
                                PROMEDIO GENERAL DEL AÑO:
                            </td>
                            <td class="text-center fw-black" style="font-size:1rem;color:{{ $h['promedio'] >= 70 ? '#15803d' : '#dc2626' }};">
                                {{ number_format($h['promedio'], 2) }}
                            </td>
                            <td class="text-center">
                                @if($h['situacion_general'] === 'Promovido')
                                    <span class="badge-sit-prom">Promovido</span>
                                @elseif($h['situacion_general'] === 'Reprobado')
                                    <span class="badge-sit-rep">Reprobado</span>
                                @else
                                    <span class="badge-sit-nd">—</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            @else
            <div style="text-align:center;padding:1rem;color:#94a3b8;font-size:.85rem;">
                <i class="bi bi-journal-x me-1"></i>Sin calificaciones académicas registradas para este año.
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif {{-- fin @if($historial->isEmpty()) --}}

@endsection

@push('scripts')
@if(!$historial->isEmpty() && $historial->count() >= 2)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels    = @json($chartLabels);
    const promedios = @json($chartPromedios);

    // Colores por punto: verde ≥70, rojo <70
    const pointColors = promedios.map(p =>
        p === null ? '#94a3b8' : (p >= 70 ? '#22c55e' : '#ef4444')
    );

    const ctx = document.getElementById('chartHistorial');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio General',
                data: promedios,
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29,78,216,.08)',
                borderWidth: 3,
                pointBackgroundColor: pointColors,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 7,
                pointHoverRadius: 9,
                fill: true,
                tension: 0.35,
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
                        label: ctx => {
                            const v = ctx.parsed.y;
                            if (v === null) return 'Sin datos';
                            return `Promedio: ${v.toFixed(1)} pts${v >= 70 ? ' ✓' : ' ✗'}`;
                        }
                    },
                    backgroundColor: '#1e3a6e',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 8,
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { font: { size: 11 }, color: '#64748b' },
                },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,.06)' },
                    ticks: {
                        font: { size: 11 }, color: '#64748b',
                        stepSize: 10,
                        callback: v => v + ' pts'
                    },
                    // Línea de aprobación
                }
            },
        },
        plugins: [{
            id: 'aprobLine',
            afterDraw(chart) {
                const { ctx, chartArea: { left, right }, scales: { y } } = chart;
                const yPos = y.getPixelForValue(70);
                ctx.save();
                ctx.setLineDash([6, 4]);
                ctx.strokeStyle = 'rgba(239,68,68,.5)';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(left, yPos);
                ctx.lineTo(right, yPos);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.fillStyle = 'rgba(239,68,68,.7)';
                ctx.font = '10px sans-serif';
                ctx.fillText('Mín. 70', right - 45, yPos - 5);
                ctx.restore();
            }
        }]
    });
});
</script>
@endif
@endpush
