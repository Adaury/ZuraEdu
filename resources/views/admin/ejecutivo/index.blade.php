@extends('layouts.admin')
@section('page-title', 'Dashboard Ejecutivo')

@push('styles')
<style>
/* ── KPI Cards ── */
.kpi-card { border-radius:14px; padding:1.25rem 1.4rem; color:#fff; display:flex; align-items:center; gap:1rem; }
.kpi-icon { width:48px; height:48px; border-radius:12px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.kpi-label { font-size:.72rem; font-weight:600; opacity:.85; text-transform:uppercase; letter-spacing:.05em; }
.kpi-value { font-size:1.75rem; font-weight:900; line-height:1; margin:.15rem 0; }
.kpi-sub   { font-size:.72rem; opacity:.75; }
.kpi-delta { font-size:.7rem; font-weight:700; padding:.1rem .4rem; border-radius:99px; background:rgba(255,255,255,.18); }
.kpi-delta.up   { background:rgba(34,197,94,.25); }
.kpi-delta.down { background:rgba(239,68,68,.25); }

/* ── Chart Cards ── */
.chart-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(15,23,42,.07); padding:1.25rem 1.4rem; height:100%; }
.chart-title { font-size:.82rem; font-weight:700; color:#1e293b; margin-bottom:1rem; display:flex; align-items:center; gap:.4rem; }

/* ── Table ── */
.exec-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.exec-table th { background:#f8fafc; font-weight:700; color:#374151; padding:.5rem .75rem; text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; }
.exec-table td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; color:#1e293b; }
.exec-table tr:last-child td { border-bottom:none; }

/* ── Semáforo ── */
.sem-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.sem-ok     { background:#16a34a; }
.sem-warn   { background:#d97706; }
.sem-danger { background:#dc2626; }

/* ── Period filter ── */
.period-pill { border:1.5px solid #e2e8f0; border-radius:99px; padding:.3rem .85rem; font-size:.75rem; font-weight:600; color:#374151; text-decoration:none; display:inline-block; transition:all .15s; }
.period-pill:hover { background:#eff6ff; border-color:#2563eb; color:#2563eb; }
.period-pill.active { background:#2563eb; border-color:#2563eb; color:#fff; }

/* ── Riesgo ── */
.riesgo-badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; font-weight:700; padding:.25rem .6rem; border-radius:99px; }
.riesgo-alto   { background:#fef2f2; color:#dc2626; }
.riesgo-medio  { background:#fffbeb; color:#d97706; }
.riesgo-bajo   { background:#f0fdf4; color:#16a34a; }

/* ── Docentes mini-KPI ── */
.doc-kpi { border-radius:10px; padding:.9rem 1rem; text-align:center; }

[data-theme="dark"] .chart-card { background:#1e293b; }
[data-theme="dark"] .chart-title { color:#e2e8f0; }
[data-theme="dark"] .exec-table th { background:#0f172a; color:#94a3b8; }
[data-theme="dark"] .exec-table td { color:#e2e8f0; border-color:#334155; }
[data-theme="dark"] .riesgo-alto  { background:#450a0a; color:#fca5a5; }
[data-theme="dark"] .riesgo-medio { background:#451a03; color:#fcd34d; }
[data-theme="dark"] .riesgo-bajo  { background:#052e16; color:#86efac; }
</style>
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#0f1f3d,#1e3a6e);">
    <div class="card-body py-3 px-4 text-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-bar-chart-line-fill" style="font-size:1.4rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Dashboard Ejecutivo</h5>
                    <p class="mb-0" style="font-size:.83rem;opacity:.85;">
                        Resumen institucional — {{ $schoolYear?->nombre ?? 'Año actual' }}
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span style="font-size:.72rem;opacity:.7;">Generado: {{ now()->format('d/m/Y H:i') }}</span>
                <a href="{{ route('admin.ejecutivo.excel', request()->query()) }}"
                   class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.75rem;">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="{{ route('admin.ejecutivo.pdf', request()->query()) }}" target="_blank"
                   class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.75rem;">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtro por período ─────────────────────────────────────────── --}}
@if($periodos->isNotEmpty())
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <span class="text-muted" style="font-size:.78rem;font-weight:600;">Período:</span>
    <a href="{{ request()->url() }}" class="period-pill {{ !$periodoId ? 'active' : '' }}">Anual</a>
    @foreach($periodos as $p)
    <a href="{{ request()->url() }}?periodo_id={{ $p->id }}"
       class="period-pill {{ $periodoId == $p->id ? 'active' : '' }}">
        {{ $p->nombre }}
    </a>
    @endforeach
</div>
@endif

{{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);">
            <div class="kpi-icon"><i class="bi bi-people-fill" style="font-size:1.3rem;"></i></div>
            <div>
                <div class="kpi-label">Estudiantes</div>
                <div class="kpi-value">{{ number_format($totalEstudiantes) }}</div>
                <div class="kpi-sub">{{ $totalDocentes }} docentes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card" style="background:linear-gradient(135deg,#065f46,#059669);">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow" style="font-size:1.3rem;"></i></div>
            <div>
                <div class="kpi-label">Promedio Inst.</div>
                <div class="kpi-value">{{ $promedioInstitucional ? number_format($promedioInstitucional, 1) : '—' }}</div>
                <div class="d-flex align-items-center gap-1 flex-wrap" style="margin-top:.15rem;">
                    <span class="kpi-sub">{{ $tasaAprobacion }}% aprobación</span>
                    @if(!empty($comparativa['promedio']))
                        @php $d = $comparativa['promedio']; @endphp
                        <span class="kpi-delta {{ $d >= 0 ? 'up' : 'down' }}">
                            {{ $d >= 0 ? '▲' : '▼' }} {{ abs($d) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card" style="background:linear-gradient(135deg,#7c2d12,#c2410c);">
            <div class="kpi-icon"><i class="bi bi-calendar-check-fill" style="font-size:1.3rem;"></i></div>
            <div>
                <div class="kpi-label">Asistencia (mes)</div>
                <div class="kpi-value">{{ $pctAsistencia !== null ? $pctAsistencia . '%' : '—' }}</div>
                <div class="kpi-sub">{{ number_format($asistenciaMes['ausente'] ?? 0) }} ausencias</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        @if($statsPagos)
        <div class="kpi-card" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);">
            <div class="kpi-icon"><i class="bi bi-cash-coin" style="font-size:1.3rem;"></i></div>
            <div>
                <div class="kpi-label">Cobrado</div>
                <div class="kpi-value" style="font-size:1.3rem;">RD${{ number_format($statsPagos['cobrado'], 0) }}</div>
                <div class="kpi-sub">RD${{ number_format($statsPagos['pendiente'] + $statsPagos['vencido'], 0) }} pendiente</div>
            </div>
        </div>
        @else
        <div class="kpi-card" style="background:linear-gradient(135deg,#155e75,#0891b2);">
            <div class="kpi-icon"><i class="bi bi-mortarboard-fill" style="font-size:1.3rem;"></i></div>
            <div>
                <div class="kpi-label">Tasa de Aprobación</div>
                <div class="kpi-value">{{ $tasaAprobacion }}%</div>
                <div class="d-flex align-items-center gap-1 flex-wrap" style="margin-top:.15rem;">
                    <span class="kpi-sub">{{ $rendimiento->sum('total_aprobados') }} aprobados</span>
                    @if(!empty($comparativa['tasa']))
                        @php $dt = $comparativa['tasa']; @endphp
                        <span class="kpi-delta {{ $dt >= 0 ? 'up' : 'down' }}">
                            {{ $dt >= 0 ? '▲' : '▼' }} {{ abs($dt) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Row 2: Promedios por Grado + Distribución Desempeño ─────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="chart-card">
            <div class="chart-title">
                <span style="width:10px;height:10px;background:#2563eb;border-radius:3px;display:inline-block;"></span>
                Promedio Académico por Grado
            </div>
            <canvas id="chartGrados" height="180"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card">
            <div class="chart-title">
                <span style="width:10px;height:10px;background:#7c3aed;border-radius:3px;display:inline-block;"></span>
                Distribución del Desempeño
            </div>
            <canvas id="chartDesempeno" height="180"></canvas>
        </div>
    </div>
</div>

{{-- ── Row 3: Asistencia tendencia + Pagos (o Matrículas) ─────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="chart-title">
                <span style="width:10px;height:10px;background:#16a34a;border-radius:3px;display:inline-block;"></span>
                Tendencia de Asistencia — Últimos 6 Meses
            </div>
            <canvas id="chartAsistencia" height="160"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-title">
                <span style="width:10px;height:10px;background:#d97706;border-radius:3px;display:inline-block;"></span>
                {{ $statsPagos ? 'Estado de Pagos' : 'Matrículas por Grado' }}
            </div>
            @if($statsPagos)
            <canvas id="chartPagos" height="180"></canvas>
            @else
            <canvas id="chartMatriculas" height="180"></canvas>
            @endif
        </div>
    </div>
</div>

{{-- ── Row 4: Top/Bottom grupos + Matrículas por grado ─────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="chart-card">
            <div class="chart-title">
                <i class="bi bi-mortarboard" style="color:#2563eb;"></i>
                Matrículas Activas por Grado
            </div>
            <canvas id="chartMatriculasGrado" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="chart-card">
            <div class="chart-title">
                <i class="bi bi-trophy" style="color:#d97706;"></i>
                Ranking de Grupos por Promedio
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <div style="font-size:.72rem;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem;">🏆 Mejores</div>
                    <table class="exec-table">
                        <thead><tr><th>Grupo</th><th>Prom.</th><th></th></tr></thead>
                        <tbody>
                            @forelse($topGrupos as $r)
                            <tr>
                                <td style="font-weight:600;">
                                    {{ $r->grupo?->grado?->nombre ?? '—' }}
                                    {{ $r->grupo?->seccion?->nombre ?? '' }}
                                </td>
                                <td>
                                    <span style="font-weight:700;color:{{ $r->promedio_grupo >= 80 ? '#16a34a' : ($r->promedio_grupo >= 70 ? '#d97706' : '#dc2626') }}">
                                        {{ number_format($r->promedio_grupo, 1) }}
                                    </span>
                                </td>
                                <td><span class="sem-dot {{ $r->semaforo === 'danger' ? 'sem-danger' : ($r->semaforo === 'warning' ? 'sem-warn' : 'sem-ok') }}"></span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted" style="font-size:.75rem;">Sin datos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-6">
                    <div style="font-size:.72rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem;">⚠ Atención</div>
                    <table class="exec-table">
                        <thead><tr><th>Grupo</th><th>Prom.</th><th></th></tr></thead>
                        <tbody>
                            @forelse($bottomGrupos as $r)
                            <tr>
                                <td style="font-weight:600;">
                                    {{ $r->grupo?->grado?->nombre ?? '—' }}
                                    {{ $r->grupo?->seccion?->nombre ?? '' }}
                                </td>
                                <td>
                                    <span style="font-weight:700;color:{{ $r->promedio_grupo >= 80 ? '#16a34a' : ($r->promedio_grupo >= 70 ? '#d97706' : '#dc2626') }}">
                                        {{ number_format($r->promedio_grupo, 1) }}
                                    </span>
                                </td>
                                <td><span class="sem-dot {{ $r->semaforo === 'danger' ? 'sem-danger' : ($r->semaforo === 'warning' ? 'sem-warn' : 'sem-ok') }}"></span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted" style="font-size:.75rem;">Sin datos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 5: Rendimiento por Asignatura (NUEVO) ───────────────────── --}}
@if($promediosPorAsignatura->isNotEmpty())
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="chart-card h-100">
            <div class="chart-title">
                <i class="bi bi-book" style="color:#2563eb;"></i>
                Rendimiento por Asignatura
                <span class="ms-auto text-muted" style="font-size:.7rem;font-weight:400;">ordenado por promedio ↑</span>
            </div>
            <canvas id="chartAsignaturas" style="max-height:320px;"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card h-100">
            <div class="chart-title">
                <i class="bi bi-list-ol" style="color:#2563eb;"></i>
                Detalle por Asignatura
            </div>
            <div style="max-height:300px;overflow-y:auto;">
                <table class="exec-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Asignatura</th>
                            <th style="text-align:right;">Promedio</th>
                            <th style="text-align:right;">Alumnos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($promediosPorAsignatura as $i => $asig)
                        @php $prom = (float)$asig->promedio; @endphp
                        <tr>
                            <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                            <td style="font-weight:600;">{{ $asig->nombre }}</td>
                            <td style="text-align:right;">
                                <span style="font-weight:700;color:{{ $prom >= 80 ? '#16a34a' : ($prom >= 70 ? '#d97706' : '#dc2626') }}">
                                    {{ number_format($prom, 1) }}
                                </span>
                            </td>
                            <td style="text-align:right;color:#64748b;">{{ $asig->total_estudiantes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Row 6: Riesgo Académico + Docentes (NUEVO) ──────────────────── --}}
<div class="row g-3 mb-3">

    {{-- Panel de Riesgo --}}
    <div class="col-lg-7">
        <div class="chart-card h-100">
            <div class="chart-title">
                <i class="bi bi-shield-exclamation" style="color:#dc2626;"></i>
                Panel de Riesgo Académico
                <span class="ms-auto badge" style="background:#fef2f2;color:#dc2626;font-size:.7rem;">
                    {{ $riesgoData['totalEnRiesgo'] }} estudiante{{ $riesgoData['totalEnRiesgo'] != 1 ? 's' : '' }} en riesgo
                </span>
            </div>
            @if($riesgoData['totalEnRiesgo'] > 0)
            <div style="font-size:.75rem;color:#64748b;margin-bottom:.75rem;">
                Estudiantes con calificación &lt;70 en 2 o más asignaturas
            </div>
            <table class="exec-table">
                <thead>
                    <tr>
                        <th>Grado</th>
                        <th style="text-align:right;">En Riesgo</th>
                        <th style="text-align:right;">Total Grado</th>
                        <th style="text-align:center;">Alerta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riesgoData['riesgoPorGrado']->sortKeysUsing('strnatcmp') as $grado => $countRiesgo)
                    @php
                        $totalGrado = $matriculasPorGrado[$grado] ?? 0;
                        $pct = $totalGrado > 0 ? round($countRiesgo / $totalGrado * 100) : 0;
                        $nivel = $pct >= 20 ? 'alto' : ($pct >= 10 ? 'medio' : 'bajo');
                    @endphp
                    <tr>
                        <td style="font-weight:600;">{{ $grado }}</td>
                        <td style="text-align:right;font-weight:700;color:{{ $pct >= 20 ? '#dc2626' : ($pct >= 10 ? '#d97706' : '#16a34a') }};">
                            {{ $countRiesgo }}
                        </td>
                        <td style="text-align:right;color:#64748b;">{{ $totalGrado }}</td>
                        <td style="text-align:center;">
                            <span class="riesgo-badge riesgo-{{ $nivel }}">
                                {{ $pct }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-4" style="color:#16a34a;">
                <i class="bi bi-check-circle-fill" style="font-size:2rem;"></i>
                <div style="font-size:.85rem;font-weight:600;margin-top:.5rem;">
                    Sin estudiantes en riesgo crítico
                </div>
                <div style="font-size:.75rem;color:#64748b;">
                    Todos los estudiantes tienen calificaciones aceptables
                </div>
            </div>
            @endif
            @if($riesgoData['totalEnRiesgo'] > 0)
            <div class="mt-3">
                <a href="{{ route('admin.rendimiento.rezagados') }}" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;">
                    <i class="bi bi-arrow-right-circle me-1"></i>Ver estudiantes rezagados
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Indicadores de Docentes --}}
    <div class="col-lg-5">
        <div class="chart-card h-100">
            <div class="chart-title">
                <i class="bi bi-person-badge" style="color:#7c3aed;"></i>
                Indicadores de Docentes
            </div>
            <div class="row g-2">
                <div class="col-12">
                    <div class="doc-kpi" style="background:#ede9fe;">
                        <div style="font-size:2rem;font-weight:900;color:#7c3aed;">{{ $statsDocentes['activos'] }}</div>
                        <div style="font-size:.75rem;font-weight:700;color:#4c1d95;">Docentes Activos</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="doc-kpi" style="background:#f0fdf4;">
                        <div style="font-size:1.7rem;font-weight:900;color:#16a34a;">{{ $statsDocentes['con_notas'] }}</div>
                        <div style="font-size:.72rem;font-weight:600;color:#14532d;">Con notas publicadas</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="doc-kpi" style="background:{{ $statsDocentes['sin_notas'] > 0 ? '#fef2f2' : '#f0fdf4' }};">
                        <div style="font-size:1.7rem;font-weight:900;color:{{ $statsDocentes['sin_notas'] > 0 ? '#dc2626' : '#16a34a' }};">
                            {{ $statsDocentes['sin_notas'] }}
                        </div>
                        <div style="font-size:.72rem;font-weight:600;color:{{ $statsDocentes['sin_notas'] > 0 ? '#7f1d1d' : '#14532d' }};">
                            Sin notas publicadas
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($comparativa['nombre']))
            <div class="mt-3 p-2 rounded" style="background:#f8fafc;font-size:.72rem;color:#64748b;">
                <i class="bi bi-clock-history me-1"></i>
                Comparativa vs <strong>{{ $comparativa['nombre'] }}</strong>:
                @if(!empty($comparativa['promedio']))
                    Promedio
                    <span style="font-weight:700;color:{{ $comparativa['promedio'] >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $comparativa['promedio'] >= 0 ? '+' : '' }}{{ $comparativa['promedio'] }}
                    </span>
                @endif
                @if(!empty($comparativa['tasa']))
                    &nbsp;| Aprobación
                    <span style="font-weight:700;color:{{ $comparativa['tasa'] >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $comparativa['tasa'] >= 0 ? '+' : '' }}{{ $comparativa['tasa'] }}%
                    </span>
                @endif
            </div>
            @endif
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                    <i class="bi bi-speedometer2 me-1"></i>Rendimiento completo
                </a>
                <a href="{{ route('admin.alertas.index') }}" class="btn btn-sm btn-outline-warning" style="font-size:.75rem;">
                    <i class="bi bi-bell me-1"></i>Alertas
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 7: Disciplina + Pre-matrícula ───────────────────────────── --}}
<div class="row g-3 mb-3">
    @if(!empty($disciplinaPorTipo))
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-title">
                <i class="bi bi-exclamation-triangle" style="color:#dc2626;"></i>
                Faltas Disciplinarias por Tipo
            </div>
            <canvas id="chartDisciplina" height="200"></canvas>
        </div>
    </div>
    @endif
    @if(!empty($preMatriculaStats))
    <div class="col-lg-{{ empty($disciplinaPorTipo) ? '12' : '6' }}">
        <div class="chart-card h-100">
            <div class="chart-title">
                <i class="bi bi-person-plus" style="color:#0891b2;"></i>
                Estado de Pre-Matrículas
            </div>
            <div class="row g-3 mt-1">
                <div class="col-4 text-center">
                    <div style="font-size:2rem;font-weight:900;color:#d97706;">{{ $preMatriculaStats['pendientes'] }}</div>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">Pendientes</div>
                </div>
                <div class="col-4 text-center">
                    <div style="font-size:2rem;font-weight:900;color:#16a34a;">{{ $preMatriculaStats['aprobadas'] }}</div>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">Aprobadas</div>
                </div>
                <div class="col-4 text-center">
                    <div style="font-size:2rem;font-weight:900;color:#dc2626;">{{ $preMatriculaStats['rechazadas'] }}</div>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">Rechazadas</div>
                </div>
            </div>
            @if($preMatriculaStats['pendientes'] > 0)
            <div class="mt-3 text-center">
                <a href="{{ route('admin.pre-matricula.index') }}" class="btn btn-sm btn-outline-warning" style="font-size:.78rem;">
                    <i class="bi bi-arrow-right-circle me-1"></i>Revisar pendientes
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ── Data from PHP ──────────────────────────────────────────────────────────
const gradosLabels    = @json($promediosPorGrado->keys()->values());
const gradosData      = @json($promediosPorGrado->values()->values());
const desempenoLabels = @json(array_keys($distribucionDesempeno));
const desempenoData   = @json(array_values($distribucionDesempeno));
const asistLabels     = @json($tendenciaAsistencia['labels']);
const asistPresente   = @json($tendenciaAsistencia['data']['presente']);
const asistTardanza   = @json($tendenciaAsistencia['data']['tardanza']);
const asistAusente    = @json($tendenciaAsistencia['data']['ausente']);
const matriculaLabels = @json($matriculasPorGrado->keys()->values());
const matriculaData   = @json($matriculasPorGrado->values()->values());
const asigLabels      = @json($promediosPorAsignatura->pluck('nombre'));
const asigData        = @json($promediosPorAsignatura->pluck('promedio')->map(fn($v) => (float)$v));
@if($statsPagos)
const pagosData = @json([$statsPagos['cobrado'], $statsPagos['pendiente'], $statsPagos['vencido']]);
@endif
@if(!empty($disciplinaPorTipo))
const discLabels = @json(array_keys($disciplinaPorTipo));
const discData   = @json(array_values($disciplinaPorTipo));
@endif

// Defaults Chart.js
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#64748b';

// ── 1. Promedios por Grado (Bar) ──────────────────────────────────────────
new Chart(document.getElementById('chartGrados'), {
    type: 'bar',
    data: {
        labels: gradosLabels,
        datasets: [{
            label: 'Promedio',
            data: gradosData,
            backgroundColor: gradosData.map(v =>
                v >= 80 ? '#2563eb' : v >= 70 ? '#d97706' : '#dc2626'
            ),
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` Promedio: ${ctx.parsed.y.toFixed(1)}` } }
        },
        scales: {
            y: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { callback: v => v.toFixed(0) } },
            x: { grid: { display: false } }
        }
    }
});

// ── 2. Distribución Desempeño (Donut) ─────────────────────────────────────
new Chart(document.getElementById('chartDesempeno'), {
    type: 'doughnut',
    data: {
        labels: desempenoLabels,
        datasets: [{
            data: desempenoData,
            backgroundColor: ['#16a34a', '#2563eb', '#d97706', '#dc2626'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true, cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toFixed(1)}%` } }
        }
    }
});

// ── 3. Tendencia Asistencia (Line) ─────────────────────────────────────────
new Chart(document.getElementById('chartAsistencia'), {
    type: 'line',
    data: {
        labels: asistLabels,
        datasets: [
            { label: 'Presentes', data: asistPresente, borderColor: '#16a34a', backgroundColor: '#16a34a22', tension: .3, fill: true, pointRadius: 4, pointBackgroundColor: '#16a34a' },
            { label: 'Tardanzas', data: asistTardanza, borderColor: '#d97706', backgroundColor: 'transparent', tension: .3, fill: false, pointRadius: 4, pointBackgroundColor: '#d97706', borderDash: [4,3] },
            { label: 'Ausentes',  data: asistAusente,  borderColor: '#dc2626', backgroundColor: '#dc262622', tension: .3, fill: true, pointRadius: 4, pointBackgroundColor: '#dc2626' }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { padding: 10, boxWidth: 12 } } },
        scales: {
            y: { grid: { color: '#f1f5f9' }, min: 0 },
            x: { grid: { display: false } }
        }
    }
});

// ── 4a. Pagos (Donut) ──────────────────────────────────────────────────────
@if($statsPagos)
new Chart(document.getElementById('chartPagos'), {
    type: 'doughnut',
    data: {
        labels: ['Cobrado', 'Pendiente', 'Vencido'],
        datasets: [{ data: pagosData, backgroundColor: ['#16a34a','#d97706','#dc2626'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
        responsive: true, cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: RD$${ctx.parsed.toLocaleString('es-DO')}` } }
        }
    }
});
@else
new Chart(document.getElementById('chartMatriculas'), {
    type: 'doughnut',
    data: {
        labels: matriculaLabels,
        datasets: [{ data: matriculaData, backgroundColor: ['#2563eb','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#6b7280'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { padding: 8, boxWidth: 10, font: { size: 10 } } } } }
});
@endif

// ── 5. Matrículas por Grado (Bar horizontal) ──────────────────────────────
new Chart(document.getElementById('chartMatriculasGrado'), {
    type: 'bar',
    data: {
        labels: matriculaLabels,
        datasets: [{ label: 'Estudiantes', data: matriculaData, backgroundColor: '#2563eb88', borderColor: '#2563eb', borderWidth: 1.5, borderRadius: 4 }]
    },
    options: {
        indexAxis: 'y', responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: '#f1f5f9' }, min: 0 },
            y: { grid: { display: false } }
        }
    }
});

// ── 6. Rendimiento por Asignatura (Bar horizontal) ────────────────────────
@if($promediosPorAsignatura->isNotEmpty())
new Chart(document.getElementById('chartAsignaturas'), {
    type: 'bar',
    data: {
        labels: asigLabels,
        datasets: [{
            label: 'Promedio',
            data: asigData,
            backgroundColor: asigData.map(v => v >= 80 ? '#2563eb88' : v >= 70 ? '#d9770688' : '#dc262644'),
            borderColor:      asigData.map(v => v >= 80 ? '#2563eb'   : v >= 70 ? '#d97706'   : '#dc2626'),
            borderWidth: 1.5,
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` Promedio: ${ctx.parsed.x.toFixed(1)}` } }
        },
        scales: {
            x: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { callback: v => v } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});
@endif

// ── 7. Disciplina (Bar) ────────────────────────────────────────────────────
@if(!empty($disciplinaPorTipo))
new Chart(document.getElementById('chartDisciplina'), {
    type: 'bar',
    data: {
        labels: discLabels.map(l => l.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())),
        datasets: [{ label: 'Faltas', data: discData, backgroundColor: '#dc262666', borderColor: '#dc2626', borderWidth: 1.5, borderRadius: 4 }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#f1f5f9' }, min: 0, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});
@endif
</script>
@endpush
