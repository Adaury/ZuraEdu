@extends('layouts.admin')
@section('page-title', 'Estadísticas y Reportes')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.stats-header { background: linear-gradient(135deg,#1e3a6e 0%,#2563eb 100%); color:#fff; border-radius:16px; padding:1.5rem 2rem; margin-bottom:1.75rem; }
.stats-header h4 { font-size:1.35rem; font-weight:800; margin:0; }
.stats-header p  { font-size:.83rem; opacity:.85; margin:.25rem 0 0; }

/* ── KPI tiles ───────────────────────────────────────────────────── */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.9rem; margin-bottom:1.5rem; }
.kpi-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.1rem 1.2rem; position:relative; overflow:hidden; transition:transform .15s,box-shadow .15s; }
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.09); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:var(--kpi-color,#2563eb); border-radius:14px 14px 0 0; }
.kpi-val  { font-size:1.85rem; font-weight:900; line-height:1.1; color:var(--kpi-color,#1d4ed8); }
.kpi-lbl  { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:.3rem; }
.kpi-icon { position:absolute; right:.9rem; top:.8rem; font-size:1.5rem; opacity:.12; }

/* ── Chart cards ─────────────────────────────────────────────────── */
.chart-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.25rem 1.4rem; }
.chart-card .chart-title { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; margin-bottom:.9rem; display:flex; align-items:center; gap:.4rem; }
.chart-card .chart-title i { font-size:.85rem; color:var(--primary); }

/* ── Section titles ──────────────────────────────────────────────── */
.sec-title { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.35rem; margin:1.6rem 0 .9rem; display:flex; align-items:center; gap:.4rem; }

/* ── Top grupos table ────────────────────────────────────────────── */
.rank-table td,.rank-table th { font-size:.82rem; vertical-align:middle; padding:.45rem .7rem; }
.rank-badge { width:26px; height:26px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:800; }

/* ── Pagos summary ───────────────────────────────────────────────── */
.pago-pill { border-radius:12px; padding:.85rem 1.1rem; text-align:center; }
.pago-pill .amount { font-size:1.4rem; font-weight:800; }
.pago-pill .label  { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; opacity:.8; }
</style>
@endpush

@section('content')

{{-- Header ─────────────────────────────────────────────────────────── --}}
<div class="stats-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h4><i class="bi bi-speedometer2 me-2"></i>Dashboard de Estadísticas</h4>
        <p>{{ $inst }} · Año escolar: <strong>{{ $sy?->nombre ?? '—' }}</strong> · Actualizado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.sistema.reporte-ejecutivo') }}" target="_blank"
           class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Inf. Ejecutivo PDF
        </a>
        <a href="{{ route('admin.sistema.reporte-anual') }}" target="_blank"
           class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Resumen Anual PDF
        </a>
        <a href="{{ route('admin.sistema.ficha-institucional') }}" target="_blank"
           class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
            <i class="bi bi-building me-1"></i>Ficha
        </a>
        <a href="{{ route('admin.sistema.estadisticas.excel') }}"
           class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.sistema.actividad') }}"
           class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
            <i class="bi bi-clipboard-data me-1"></i>Log
        </a>
    </div>
</div>

{{-- ══ KPIs Académicos ══ --}}
<div class="sec-title"><i class="bi bi-mortarboard-fill"></i>Panorama Académico</div>
<div class="kpi-grid">
    <div class="kpi-card" style="--kpi-color:#2563eb">
        <i class="bi bi-people-fill kpi-icon"></i>
        <div class="kpi-val">{{ number_format($stats['estudiantes']) }}</div>
        <div class="kpi-lbl">Estudiantes activos</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#059669">
        <i class="bi bi-person-badge-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['docentes'] }}</div>
        <div class="kpi-lbl">Docentes activos</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#7c3aed">
        <i class="bi bi-collection-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['grupos'] }}</div>
        <div class="kpi-lbl">Grupos este año</div>
    </div>
    @if($stats['promedio_global'] !== null)
    <div class="kpi-card" style="--kpi-color:{{ $stats['promedio_global'] >= 70 ? '#16a34a' : ($stats['promedio_global'] >= 60 ? '#d97706' : '#dc2626') }}">
        <i class="bi bi-bar-chart-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['promedio_global'] }}</div>
        <div class="kpi-lbl">Promedio global</div>
    </div>
    @endif
    @if($stats['tasa_aprobacion'] !== null)
    <div class="kpi-card" style="--kpi-color:#16a34a">
        <i class="bi bi-check-circle-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['tasa_aprobacion'] }}%</div>
        <div class="kpi-lbl">Tasa aprobación</div>
    </div>
    @endif
    @if($stats['asist_promedio'] !== null)
    <div class="kpi-card" style="--kpi-color:#0891b2">
        <i class="bi bi-calendar-check-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['asist_promedio'] }}%</div>
        <div class="kpi-lbl">Asistencia promedio</div>
    </div>
    @endif
    <div class="kpi-card" style="--kpi-color:#4f46e5">
        <i class="bi bi-journal-text kpi-icon"></i>
        <div class="kpi-val">{{ $stats['planificaciones'] }}</div>
        <div class="kpi-lbl">Planificaciones</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#f59e0b">
        <i class="bi bi-exclamation-triangle-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['alertas_activas'] }}</div>
        <div class="kpi-lbl">Alertas activas</div>
    </div>
    @if($stats['pre_matriculas_pendientes'] > 0)
    <div class="kpi-card" style="--kpi-color:#d97706">
        <i class="bi bi-hourglass-split kpi-icon"></i>
        <div class="kpi-val">{{ $stats['pre_matriculas_pendientes'] }}</div>
        <div class="kpi-lbl">Pre-matrículas pendientes</div>
    </div>
    @endif
    @if($stats['pagos_pendientes'] !== null)
    <div class="kpi-card" style="--kpi-color:#dc2626">
        <i class="bi bi-credit-card-fill kpi-icon"></i>
        <div class="kpi-val">{{ $stats['pagos_pendientes'] }}</div>
        <div class="kpi-lbl">Pagos pendientes</div>
    </div>
    @endif
</div>

{{-- ══ Gráficas principales (2 columnas) ══ --}}
<div class="row g-3 mb-3">
    {{-- Matrícula por grado --}}
    @if($porGrado->isNotEmpty())
    <div class="col-lg-7">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-bar-chart-steps"></i>Matrícula por Grado</div>
            <canvas id="chartGrados" height="200"></canvas>
        </div>
    </div>
    @endif

    {{-- Aprobados / Reprobados --}}
    @if($stats['aprobados'] + $stats['reprobados'] > 0)
    <div class="col-lg-{{ $porGrado->isNotEmpty() ? '5' : '6' }}">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-pie-chart-fill"></i>Resultados Académicos</div>
            <div class="d-flex justify-content-center">
                <canvas id="chartResultados" style="max-height:220px;max-width:220px;"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-2">
                <span class="badge" style="background:#16a34a;font-size:.75rem;">Aprobados: {{ $stats['aprobados'] }}</span>
                <span class="badge" style="background:#dc2626;font-size:.75rem;">Reprobados: {{ $stats['reprobados'] }}</span>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row g-3 mb-3">
    {{-- Actividad semanal --}}
    <div class="col-lg-8">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-activity"></i>Actividad del Sistema — Últimos 7 días</div>
            <canvas id="chartActividad" height="120"></canvas>
        </div>
    </div>

    {{-- Distribución por sexo --}}
    @if($porSexo->isNotEmpty())
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-gender-ambiguous"></i>Distribución por Sexo</div>
            <div class="d-flex justify-content-center">
                <canvas id="chartSexo" style="max-height:200px;max-width:200px;"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
                @foreach($porSexo as $sx => $cnt)
                <span class="badge" style="background:{{ $sx === 'M' ? '#2563eb' : '#ec4899' }};font-size:.75rem;">
                    {{ $sx === 'M' ? 'Masculino' : ($sx === 'F' ? 'Femenino' : $sx) }}: {{ $cnt }}
                </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ══ Top grupos ══ --}}
@if($topGrupos->isNotEmpty())
<div class="sec-title"><i class="bi bi-trophy-fill"></i>Ranking de Grupos por Rendimiento</div>
<div class="chart-card mb-3">
    <div class="row g-3 align-items-start">
        <div class="col-lg-7">
            <canvas id="chartTopGrupos" height="180"></canvas>
        </div>
        <div class="col-lg-5">
            <table class="table table-sm rank-table mb-0">
                <thead>
                    <tr style="font-size:.7rem;color:#6b7280;">
                        <th>#</th><th>Grupo</th><th class="text-end">Promedio</th><th class="text-end">Aprob.</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($topGrupos as $i => $rc)
                <tr>
                    <td>
                        <span class="rank-badge"
                              style="background:{{ $i === 0 ? '#fbbf24' : ($i === 1 ? '#9ca3af' : ($i === 2 ? '#c2813e' : '#e5e7eb')) }};
                                     color:{{ $i < 3 ? '#fff' : '#374151' }}">
                            {{ $i + 1 }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $rc->grupo?->grado?->nombre ?? '—' }}</strong>
                        {{ $rc->grupo?->seccion?->nombre ?? '' }}
                    </td>
                    <td class="text-end">
                        <span class="badge" style="background:{{ $rc->color_badge === 'success' ? '#16a34a' : ($rc->color_badge === 'warning' ? '#d97706' : '#dc2626') }}">
                            {{ number_format($rc->promedio_grupo ?? 0, 1) }}
                        </span>
                    </td>
                    <td class="text-end text-muted">{{ $rc->pct_regular ?? 0 }}%</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ══ Pagos (si módulo activo) ══ --}}
@if($moduloPagos && $pagosResumen)
<div class="sec-title"><i class="bi bi-credit-card-fill"></i>Estado Financiero</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="pago-pill h-100 d-flex flex-column justify-content-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <div class="amount" style="color:#15803d;">RD$ {{ number_format($pagosResumen['cobrado'], 0, '.', ',') }}</div>
            <div class="label" style="color:#15803d;"><i class="bi bi-check-circle-fill me-1"></i>Cobrado</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="pago-pill h-100 d-flex flex-column justify-content-center" style="background:#fffbeb;border:1px solid #fde68a;">
            <div class="amount" style="color:#b45309;">RD$ {{ number_format($pagosResumen['pendiente'], 0, '.', ',') }}</div>
            <div class="label" style="color:#b45309;"><i class="bi bi-clock-fill me-1"></i>Pendiente</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="pago-pill h-100 d-flex flex-column justify-content-center" style="background:#fef2f2;border:1px solid #fecaca;">
            <div class="amount" style="color:#991b1b;">RD$ {{ number_format($pagosResumen['vencido'], 0, '.', ',') }}</div>
            <div class="label" style="color:#991b1b;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Vencido
                ({{ $pagosResumen['deudores'] }} estudiantes)
            </div>
        </div>
    </div>
</div>
<div class="chart-card mb-3">
    <div class="chart-title"><i class="bi bi-cash-stack"></i>Resumen de Cobros</div>
    <canvas id="chartPagos" height="80"></canvas>
</div>
@endif

{{-- ══ Sistema & Usuarios ══ --}}
<div class="sec-title"><i class="bi bi-shield-lock-fill"></i>Usuarios del Sistema</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-person-fill"></i>Usuarios por Rol</div>
            <div class="d-flex justify-content-center">
                <canvas id="chartRoles" style="max-height:200px;max-width:200px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title"><i class="bi bi-clock-history"></i>Logins por Hora — Hoy</div>
            <canvas id="chartLogins" height="120"></canvas>
        </div>
    </div>
</div>

<div class="kpi-grid mb-4">
    <div class="kpi-card" style="--kpi-color:#2563eb">
        <div class="kpi-val">{{ $stats['usuarios_activos'] }}</div>
        <div class="kpi-lbl">Usuarios activos</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#10b981">
        <div class="kpi-val">{{ $stats['logins_hoy'] }}</div>
        <div class="kpi-lbl">Logins hoy</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#6366f1">
        <div class="kpi-val">{{ $stats['logins_semana'] }}</div>
        <div class="kpi-lbl">Logins esta semana</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#2563eb">
        <div class="kpi-val">{{ $stats['comunicados'] }}</div>
        <div class="kpi-lbl">Comunicados hoy</div>
    </div>
    <div class="kpi-card" style="--kpi-color:#6366f1">
        <div class="kpi-val">{{ $stats['notificaciones_hoy'] }}</div>
        <div class="kpi-lbl">Notificaciones hoy</div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const PALETTE = ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16','#ec4899','#14b8a6'];

// ── Matrícula por grado ───────────────────────────────────────────────
@if($porGrado->isNotEmpty())
new Chart(document.getElementById('chartGrados'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($porGrado->keys()->toArray()) !!},
        datasets: [{
            label: 'Estudiantes',
            data: {!! json_encode($porGrado->values()->toArray()) !!},
            backgroundColor: PALETTE,
            borderRadius: 6,
        }],
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { color:'#6b7280', font:{size:11} }, grid:{color:'#f1f5f9'} },
            y: { ticks: { color:'#374151', font:{size:11} }, grid:{display:false} },
        },
    },
});
@endif

// ── Resultados académicos (doughnut) ──────────────────────────────────
@if($stats['aprobados'] + $stats['reprobados'] > 0)
new Chart(document.getElementById('chartResultados'), {
    type: 'doughnut',
    data: {
        labels: ['Aprobados','Reprobados'],
        datasets: [{ data: [{{ $stats['aprobados'] }},{{ $stats['reprobados'] }}], backgroundColor:['#16a34a','#dc2626'], borderWidth:2 }],
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } },
});
@endif

// ── Actividad semanal ─────────────────────────────────────────────────
@if($actividadPorDia->isNotEmpty())
(function(){
    const labels = {!! json_encode($actividadPorDia->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('D d/m'))->toArray()) !!};
    const data   = {!! json_encode($actividadPorDia->values()->toArray()) !!};
    new Chart(document.getElementById('chartActividad'), {
        type: 'bar',
        data: { labels, datasets: [{ label:'Acciones', data, backgroundColor:'#3b82f6', borderRadius:6 }] },
        options: {
            responsive:true,
            plugins: { legend:{display:false} },
            scales: {
                x: { ticks:{color:'#6b7280',font:{size:11}}, grid:{display:false} },
                y: { ticks:{color:'#6b7280',font:{size:11}}, grid:{color:'#f1f5f9'}, beginAtZero:true },
            },
        },
    });
})();
@endif

// ── Distribución por sexo ─────────────────────────────────────────────
@if($porSexo->isNotEmpty())
new Chart(document.getElementById('chartSexo'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($porSexo->keys()->map(fn($s) => $s === 'M' ? 'Masculino' : ($s === 'F' ? 'Femenino' : $s))->toArray()) !!},
        datasets: [{ data: {!! json_encode($porSexo->values()->toArray()) !!}, backgroundColor:['#2563eb','#ec4899','#10b981'], borderWidth:2 }],
    },
    options: { responsive:true, plugins:{ legend:{display:false} } },
});
@endif

// ── Top grupos (horizontal bar) ───────────────────────────────────────
@if($topGrupos->isNotEmpty())
(function(){
    const labels = {!! json_encode($topGrupos->map(fn($rc) => ($rc->grupo?->grado?->nombre ?? '?') . ' ' . ($rc->grupo?->seccion?->nombre ?? ''))->toArray()) !!};
    const data   = {!! json_encode($topGrupos->map(fn($rc) => round($rc->promedio_grupo ?? 0, 1))->toArray()) !!};
    const colors = data.map(v => v >= 80 ? '#16a34a' : (v >= 70 ? '#d97706' : '#dc2626'));
    new Chart(document.getElementById('chartTopGrupos'), {
        type: 'bar',
        data: { labels, datasets: [{ label:'Promedio', data, backgroundColor: colors, borderRadius:6 }] },
        options: {
            indexAxis: 'y',
            responsive:true,
            plugins: { legend:{display:false} },
            scales: {
                x: { min:0, max:100, ticks:{color:'#6b7280',font:{size:11}}, grid:{color:'#f1f5f9'} },
                y: { ticks:{color:'#374151',font:{size:11}}, grid:{display:false} },
            },
        },
    });
})();
@endif

// ── Pagos ─────────────────────────────────────────────────────────────
@if($moduloPagos && $pagosResumen)
new Chart(document.getElementById('chartPagos'), {
    type: 'bar',
    data: {
        labels: ['Cobrado','Pendiente','Vencido'],
        datasets: [{
            data: [{{ $pagosResumen['cobrado'] }},{{ $pagosResumen['pendiente'] }},{{ $pagosResumen['vencido'] }}],
            backgroundColor:['#16a34a','#d97706','#dc2626'],
            borderRadius: 6,
        }],
    },
    options: {
        responsive:true,
        plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: ctx => 'RD$ ' + ctx.raw.toLocaleString('es-DO') } } },
        scales: {
            x: { ticks:{color:'#374151',font:{size:11}}, grid:{display:false} },
            y: { ticks:{ color:'#6b7280', font:{size:11}, callback: v => 'RD$' + (v/1000).toFixed(0)+'K' }, grid:{color:'#f1f5f9'}, beginAtZero:true },
        },
    },
});
@endif

// ── Roles ─────────────────────────────────────────────────────────────
@if($stats['usuarios_roles']->isNotEmpty())
new Chart(document.getElementById('chartRoles'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($stats['usuarios_roles']->keys()->toArray()) !!},
        datasets: [{ data: {!! json_encode($stats['usuarios_roles']->values()->toArray()) !!}, backgroundColor: PALETTE, borderWidth:2 }],
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{size:10}, boxWidth:12 } } } },
});
@endif

// ── Logins por hora ───────────────────────────────────────────────────
(function(){
    const horaLabels = Array.from({length:24}, (_,i) => i + ':00');
    const horaData = Array(24).fill(0);
    const raw = {!! json_encode($loginsPorHora->toArray()) !!};
    Object.entries(raw).forEach(([h,v]) => horaData[parseInt(h)] = v);
    new Chart(document.getElementById('chartLogins'), {
        type: 'line',
        data: {
            labels: horaLabels,
            datasets: [{
                label:'Logins',
                data: horaData,
                borderColor:'#6366f1',
                backgroundColor:'rgba(99,102,241,.1)',
                fill:true,
                tension:.35,
                pointRadius: horaData.map(v => v > 0 ? 4 : 0),
            }],
        },
        options: {
            responsive:true,
            plugins: { legend:{display:false} },
            scales: {
                x: { ticks:{color:'#6b7280',font:{size:10}, maxTicksLimit:12}, grid:{display:false} },
                y: { beginAtZero:true, ticks:{color:'#6b7280',font:{size:11},stepSize:1}, grid:{color:'#f1f5f9'} },
            },
        },
    });
})();
</script>
@endpush
