@extends('layouts.admin')
@section('page-title', 'Dashboard KPIs — Director')

@section('content')
<div
    x-data="kpiDashboard()"
    x-init="init()"
>

{{-- ── Cabecera ─────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:#1e3a8a;">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard KPIs
        </h4>
        <small class="text-muted">
            Actualizado: <span x-text="updatedAt">{{ $kpis['updated_at'] }}</span>
        </small>
    </div>
    <button
        @click="actualizarKpis()"
        :disabled="cargando"
        class="btn btn-primary btn-sm d-flex align-items-center gap-2"
        style="border-radius:10px;padding:.45rem 1.1rem;"
    >
        <span x-show="!cargando"><i class="bi bi-arrow-clockwise"></i> Actualizar</span>
        <span x-show="cargando" class="d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm"></span> Cargando…
        </span>
    </button>
</div>

{{-- ── Alerta de error ──────────────────────────────────────────────────── --}}
<div x-show="error" x-transition class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" style="border-radius:10px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span x-text="error"></span>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FILA 1: Asistencia + Notas pendientes + Alertas                       --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-3">

    {{-- ── Widget 1: Asistencia del día ─────────────────────────────── --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            {{-- Cabecera colapsable --}}
            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#0ea5e9,#0369a1);cursor:pointer;"
                @click="toggleWidget('asistencia')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-person-check-fill me-2"></i>Asistencia de Hoy
                </span>
                <i class="bi text-white" :class="widgets.asistencia ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.asistencia" x-collapse>
                <div class="card-body px-3 py-3">
                    {{-- Dona Chart.js --}}
                    <div style="max-width:180px;margin:0 auto 12px;">
                        <canvas id="chartAsistencia" height="180"></canvas>
                    </div>

                    {{-- Leyenda --}}
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="rounded p-2" style="background:#dcfce7;">
                                <div class="fw-bold fs-5" style="color:#16a34a;" x-text="kpis.asistencia_hoy.presentes">{{ $kpis['asistencia_hoy']['presentes'] }}</div>
                                <div class="text-muted" style="font-size:.72rem;">Presentes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-2" style="background:#fee2e2;">
                                <div class="fw-bold fs-5" style="color:#dc2626;" x-text="kpis.asistencia_hoy.ausentes">{{ $kpis['asistencia_hoy']['ausentes'] }}</div>
                                <div class="text-muted" style="font-size:.72rem;">Ausentes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-2" style="background:#fef9c3;">
                                <div class="fw-bold fs-5" style="color:#ca8a04;" x-text="kpis.asistencia_hoy.tardanzas">{{ $kpis['asistencia_hoy']['tardanzas'] }}</div>
                                <div class="text-muted" style="font-size:.72rem;">Tardanzas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-2" style="background:#e0f2fe;">
                                <div class="fw-bold fs-5" style="color:#0369a1;" x-text="kpis.asistencia_hoy.justificados">{{ $kpis['asistencia_hoy']['justificados'] }}</div>
                                <div class="text-muted" style="font-size:.72rem;">Justificados</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 text-center">
                        <span class="badge" style="background:#0369a1;font-size:.8rem;">
                            <i class="bi bi-percent me-1"></i>
                            <span x-text="kpis.asistencia_hoy.pct_asistencia + '%'">{{ $kpis['asistencia_hoy']['pct_asistencia'] }}%</span>
                            asistencia
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Widget 2: Notas pendientes ────────────────────────────────── --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#f59e0b,#d97706);cursor:pointer;"
                @click="toggleWidget('notas')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-journal-x me-2"></i>Notas Pendientes
                </span>
                <i class="bi text-white" :class="widgets.notas ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.notas" x-collapse>
                <div class="card-body px-3 py-3">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:52px;height:52px;background:#fef3c7;">
                            <span class="fw-bold fs-4" style="color:#d97706;" x-text="kpis.notas_pendientes.total">{{ $kpis['notas_pendientes']['total'] }}</span>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.85rem;">Asignaciones sin notas</div>
                            <div class="text-muted" style="font-size:.75rem;">
                                Período: <span x-text="kpis.notas_pendientes.periodo ?? '—'">{{ $kpis['notas_pendientes']['periodo'] ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Lista de docentes rezagados --}}
                    <div style="max-height:210px;overflow-y:auto;">
                        <template x-if="kpis.notas_pendientes.docentes && kpis.notas_pendientes.docentes.length > 0">
                            <ul class="list-unstyled mb-0">
                                <template x-for="(d, i) in kpis.notas_pendientes.docentes" :key="i">
                                    <li class="d-flex align-items-center justify-content-between py-1 border-bottom"
                                        style="font-size:.82rem;">
                                        <span>
                                            <i class="bi bi-person-fill me-1 text-warning"></i>
                                            <span x-text="d.nombre"></span>
                                        </span>
                                        <span class="badge bg-warning text-dark ms-2" x-text="d.pendientes + ' asig.'"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <template x-if="!kpis.notas_pendientes.docentes || kpis.notas_pendientes.docentes.length === 0">
                            <div class="text-center text-success py-3" style="font-size:.85rem;">
                                <i class="bi bi-check-circle-fill fs-4 d-block mb-1"></i>
                                Todos los docentes están al día
                            </div>
                        </template>
                    </div>

                    {{-- Blade fallback (visible sin JS) --}}
                    @if(empty($kpis['notas_pendientes']['docentes']))
                        <noscript>
                            <div class="text-center text-success py-2" style="font-size:.85rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Todos al día
                            </div>
                        </noscript>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Widget 3: Alertas activas ─────────────────────────────────── --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#ef4444,#b91c1c);cursor:pointer;"
                @click="toggleWidget('alertas')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-bell-fill me-2"></i>Alertas Activas
                </span>
                <span class="badge bg-white text-danger fw-bold ms-auto me-2" x-text="kpis.alertas_activas.total">{{ $kpis['alertas_activas']['total'] }}</span>
                <i class="bi text-white" :class="widgets.alertas ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.alertas" x-collapse>
                <div class="card-body px-3 py-3">

                    <template x-if="Object.keys(kpis.alertas_activas.por_tipo).length === 0">
                        <div class="text-center text-success py-4" style="font-size:.85rem;">
                            <i class="bi bi-shield-check fs-3 d-block mb-2 text-success"></i>
                            Sin alertas pendientes
                        </div>
                    </template>

                    <template x-if="Object.keys(kpis.alertas_activas.por_tipo).length > 0">
                        <div>
                            <template x-for="(data, tipo) in kpis.alertas_activas.por_tipo" :key="tipo">
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.83rem;">
                                    <span>
                                        <i class="bi bi-dot me-1 fs-5" :class="{
                                            'text-danger':  data.niveles && data.niveles.danger,
                                            'text-warning': !data.niveles?.danger && data.niveles?.warning,
                                            'text-info':    !data.niveles?.danger && !data.niveles?.warning
                                        }"></i>
                                        <span x-text="data.label"></span>
                                    </span>
                                    <span class="badge rounded-pill" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;" x-text="data.total"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Blade fallback --}}
                    @if(!empty($kpis['alertas_activas']['por_tipo']))
                    <div id="alertas-blade">
                        @foreach($kpis['alertas_activas']['por_tipo'] as $tipo => $data)
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.83rem;"
                             x-cloak>
                            <span>
                                <i class="bi bi-dot me-1 fs-5 text-danger"></i>
                                {{ $data['label'] }}
                            </span>
                            <span class="badge rounded-pill" style="background:#fef2f2;color:#991b1b;">{{ $data['total'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="mt-2 text-end">
                        <a href="{{ route('admin.alertas.index') }}" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;border-radius:8px;">
                            Ver todas <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FILA 2: Pagos del mes + Situación estudiantes                         --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-3">

    {{-- ── Widget 4: Pagos del mes ───────────────────────────────────── --}}
    @if($kpis['pagos_mes']['activo'] ?? false)
    <div class="col-12 col-md-6">
        <div class="card shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#10b981,#059669);cursor:pointer;"
                @click="toggleWidget('pagos')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-cash-coin me-2"></i>Pagos del Mes
                    <small class="opacity-75 fw-normal ms-1" x-text="kpis.pagos_mes.mes_label">{{ $kpis['pagos_mes']['mes_label'] }}</small>
                </span>
                <i class="bi text-white" :class="widgets.pagos ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.pagos" x-collapse>
                <div class="card-body px-3 py-3">

                    {{-- Gráfica de barras --}}
                    <div style="height:160px;" class="mb-3">
                        <canvas id="chartPagos"></canvas>
                    </div>

                    {{-- Resumen numérico --}}
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="rounded p-2" style="background:#d1fae5;">
                                <div class="fw-bold" style="color:#065f46;font-size:.9rem;">
                                    RD$ <span x-text="formatMonto(kpis.pagos_mes.cobrado)">{{ number_format($kpis['pagos_mes']['cobrado'], 0, ',', '.') }}</span>
                                </div>
                                <div class="text-muted" style="font-size:.7rem;">Cobrado</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded p-2" style="background:#fef9c3;">
                                <div class="fw-bold" style="color:#713f12;font-size:.9rem;">
                                    RD$ <span x-text="formatMonto(kpis.pagos_mes.pendiente)">{{ number_format($kpis['pagos_mes']['pendiente'], 0, ',', '.') }}</span>
                                </div>
                                <div class="text-muted" style="font-size:.7rem;">Pendiente</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded p-2" style="background:#fee2e2;">
                                <div class="fw-bold" style="color:#7f1d1d;font-size:.9rem;">
                                    RD$ <span x-text="formatMonto(kpis.pagos_mes.vencido)">{{ number_format($kpis['pagos_mes']['vencido'], 0, ',', '.') }}</span>
                                </div>
                                <div class="text-muted" style="font-size:.7rem;">Vencido</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.75rem;">
                            <span>Cobrado</span>
                            <span x-text="kpis.pagos_mes.pct_cobrado + '%'">{{ $kpis['pagos_mes']['pct_cobrado'] }}%</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:99px;">
                            <div class="progress-bar bg-success"
                                 :style="`width:${kpis.pagos_mes.pct_cobrado}%`"
                                 style="width:{{ $kpis['pagos_mes']['pct_cobrado'] }}%;border-radius:99px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Widget 5: Situación de estudiantes ───────────────────────── --}}
    <div class="col-12 @if($kpis['pagos_mes']['activo'] ?? false) col-md-6 @endif">
        <div class="card shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#7c3aed,#5b21b6);cursor:pointer;"
                @click="toggleWidget('situacion')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-bar-chart-fill me-2"></i>Situación Académica
                </span>
                <i class="bi text-white" :class="widgets.situacion ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.situacion" x-collapse>
                <div class="card-body px-3 py-3">

                    <div class="row g-3 align-items-center">
                        <div class="col-5">
                            <canvas id="chartSituacion" height="200"></canvas>
                        </div>
                        <div class="col-7">
                            <div class="mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.82rem;">
                                    <span><span class="badge bg-success me-1">A</span> Aprobados</span>
                                    <strong x-text="kpis.situacion_estudiantes.aprobados">{{ $kpis['situacion_estudiantes']['aprobados'] }}</strong>
                                </div>
                                <div class="progress" style="height:6px;border-radius:99px;">
                                    <div class="progress-bar bg-success"
                                         :style="`width:${pctSituacion(kpis.situacion_estudiantes.aprobados)}%`"
                                         style="width:{{ $kpis['situacion_estudiantes']['total'] > 0 ? round($kpis['situacion_estudiantes']['aprobados'] / $kpis['situacion_estudiantes']['total'] * 100, 1) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.82rem;">
                                    <span><span class="badge bg-danger me-1">R</span> Reprobados</span>
                                    <strong x-text="kpis.situacion_estudiantes.reprobados">{{ $kpis['situacion_estudiantes']['reprobados'] }}</strong>
                                </div>
                                <div class="progress" style="height:6px;border-radius:99px;">
                                    <div class="progress-bar bg-danger"
                                         :style="`width:${pctSituacion(kpis.situacion_estudiantes.reprobados)}%`"
                                         style="width:{{ $kpis['situacion_estudiantes']['total'] > 0 ? round($kpis['situacion_estudiantes']['reprobados'] / $kpis['situacion_estudiantes']['total'] * 100, 1) : 0 }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.82rem;">
                                    <span><span class="badge bg-secondary me-1">—</span> Sin nota</span>
                                    <strong x-text="kpis.situacion_estudiantes.sin_nota">{{ $kpis['situacion_estudiantes']['sin_nota'] }}</strong>
                                </div>
                                <div class="progress" style="height:6px;border-radius:99px;">
                                    <div class="progress-bar bg-secondary"
                                         :style="`width:${pctSituacion(kpis.situacion_estudiantes.sin_nota)}%`"
                                         style="width:{{ $kpis['situacion_estudiantes']['total'] > 0 ? round($kpis['situacion_estudiantes']['sin_nota'] / $kpis['situacion_estudiantes']['total'] * 100, 1) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="mt-2 text-muted" style="font-size:.75rem;">
                                Total matriculados: <strong x-text="kpis.situacion_estudiantes.total">{{ $kpis['situacion_estudiantes']['total'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FILA 3: Ranking de grupos (Top 3 / Bottom 3)                          --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card shadow-sm" style="border-radius:16px;border:1px solid #e2e8f0;">

            <div
                class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                style="border-radius:16px 16px 0 0;background:linear-gradient(135deg,#0f172a,#1e3a8a);cursor:pointer;"
                @click="toggleWidget('grupos')"
            >
                <span class="fw-semibold text-white fs-6">
                    <i class="bi bi-trophy-fill me-2"></i>Rendimiento por Grupos
                </span>
                <i class="bi text-white" :class="widgets.grupos ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </div>

            <div x-show="widgets.grupos" x-collapse>
                <div class="card-body px-3 py-3">
                    <div class="row g-3">

                        {{-- Top 3 --}}
                        <div class="col-12 col-md-6">
                            <h6 class="fw-semibold mb-2" style="color:#16a34a;font-size:.85rem;">
                                <i class="bi bi-arrow-up-circle-fill me-1"></i> Mejor Rendimiento
                            </h6>
                            <template x-if="kpis.grupos_ranking.top && kpis.grupos_ranking.top.length > 0">
                                <div>
                                    <template x-for="(g, i) in kpis.grupos_ranking.top" :key="i">
                                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.84rem;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                      style="width:24px;height:24px;padding:0;background:#dcfce7;color:#15803d;"
                                                      x-text="i + 1"></span>
                                                <span x-text="g.nombre"></span>
                                                <small class="text-muted" x-text="`(${g.estudiantes} est.)`"></small>
                                            </div>
                                            <span class="fw-bold" style="color:#15803d;" x-text="g.promedio"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!kpis.grupos_ranking.top || kpis.grupos_ranking.top.length === 0">
                                <p class="text-muted" style="font-size:.82rem;">Sin datos disponibles</p>
                            </template>

                            {{-- Blade fallback --}}
                            @if(!empty($kpis['grupos_ranking']['top']))
                            <div x-cloak>
                                @foreach($kpis['grupos_ranking']['top'] as $i => $g)
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.84rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                              style="width:24px;height:24px;padding:0;background:#dcfce7;color:#15803d;">{{ $i + 1 }}</span>
                                        <span>{{ $g['nombre'] }}</span>
                                        <small class="text-muted">({{ $g['estudiantes'] }} est.)</small>
                                    </div>
                                    <span class="fw-bold text-success">{{ $g['promedio'] }}</span>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted x-cloak" style="font-size:.82rem;">Sin datos disponibles</p>
                            @endif
                        </div>

                        {{-- Bottom 3 --}}
                        <div class="col-12 col-md-6">
                            <h6 class="fw-semibold mb-2" style="color:#dc2626;font-size:.85rem;">
                                <i class="bi bi-arrow-down-circle-fill me-1"></i> Menor Rendimiento
                            </h6>
                            <template x-if="kpis.grupos_ranking.bottom && kpis.grupos_ranking.bottom.length > 0">
                                <div>
                                    <template x-for="(g, i) in kpis.grupos_ranking.bottom" :key="i">
                                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.84rem;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                      style="width:24px;height:24px;padding:0;background:#fee2e2;color:#991b1b;"
                                                      x-text="i + 1"></span>
                                                <span x-text="g.nombre"></span>
                                                <small class="text-muted" x-text="`(${g.estudiantes} est.)`"></small>
                                            </div>
                                            <span class="fw-bold" style="color:#dc2626;" x-text="g.promedio"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!kpis.grupos_ranking.bottom || kpis.grupos_ranking.bottom.length === 0">
                                <p class="text-muted" style="font-size:.82rem;">Sin datos disponibles</p>
                            </template>

                            @if(!empty($kpis['grupos_ranking']['bottom']))
                            <div x-cloak>
                                @foreach($kpis['grupos_ranking']['bottom'] as $i => $g)
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.84rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                              style="width:24px;height:24px;padding:0;background:#fee2e2;color:#991b1b;">{{ $i + 1 }}</span>
                                        <span>{{ $g['nombre'] }}</span>
                                        <small class="text-muted">({{ $g['estudiantes'] }} est.)</small>
                                    </div>
                                    <span class="fw-bold text-danger">{{ $g['promedio'] }}</span>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted x-cloak" style="font-size:.82rem;">Sin datos disponibles</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}
@endsection

@push('scripts')
<script>
// ─── Datos iniciales desde PHP/Blade (evita primer fetch) ────────────────────
const kpisInit = @json($kpis);

// ─── Alpine.js Component ─────────────────────────────────────────────────────
function kpiDashboard() {
    return {
        kpis:      kpisInit,
        updatedAt: kpisInit.updated_at,
        cargando:  false,
        error:     null,

        // Estado colapsado/expandido de cada widget (todos abiertos por defecto)
        widgets: {
            asistencia: true,
            notas:      true,
            alertas:    true,
            pagos:      true,
            situacion:  true,
            grupos:     true,
        },

        // Gráficas Chart.js
        chartAsistencia: null,
        chartPagos:      null,
        chartSituacion:  null,

        init() {
            this.$nextTick(() => {
                this.initCharts();
            });
        },

        toggleWidget(key) {
            this.widgets[key] = !this.widgets[key];
        },

        // ── Formato de montos ─────────────────────────────────────────────
        formatMonto(val) {
            if (val == null) return '0';
            return Number(val).toLocaleString('es-DO', { maximumFractionDigits: 0 });
        },

        // ── Porcentaje para barras de situación ───────────────────────────
        pctSituacion(val) {
            const total = this.kpis.situacion_estudiantes?.total ?? 0;
            if (!total) return 0;
            return Math.round(val / total * 100);
        },

        // ── Actualizar KPIs vía fetch ─────────────────────────────────────
        async actualizarKpis() {
            this.cargando = true;
            this.error    = null;
            try {
                const res = await fetch('{{ route("admin.kpis.data") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                this.kpis      = data;
                this.updatedAt = data.updated_at;
                this.$nextTick(() => this.updateCharts());
            } catch (e) {
                this.error = 'Error al actualizar los datos. Intente de nuevo.';
                console.error(e);
            } finally {
                this.cargando = false;
            }
        },

        // ── Inicializar gráficas ──────────────────────────────────────────
        initCharts() {
            this.initChartAsistencia();
            @if($kpis['pagos_mes']['activo'] ?? false)
            this.initChartPagos();
            @endif
            this.initChartSituacion();
        },

        initChartAsistencia() {
            const ctx = document.getElementById('chartAsistencia');
            if (!ctx) return;
            const d = this.kpis.asistencia_hoy;
            this.chartAsistencia = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels:   ['Presentes', 'Ausentes', 'Tardanzas', 'Justificados'],
                    datasets: [{
                        data:            [d.presentes, d.ausentes, d.tardanzas, d.justificados],
                        backgroundColor: ['#22c55e', '#ef4444', '#eab308', '#38bdf8'],
                        borderWidth:     2,
                        borderColor:     '#fff',
                        hoverOffset:     6,
                    }]
                },
                options: {
                    cutout:  '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.raw}`
                            }
                        }
                    }
                }
            });
        },

        initChartPagos() {
            const ctx = document.getElementById('chartPagos');
            if (!ctx) return;
            const d = this.kpis.pagos_mes;
            this.chartPagos = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels:   ['Cobrado', 'Pendiente', 'Vencido'],
                    datasets: [{
                        label:           'RD$',
                        data:            [d.cobrado, d.pendiente, d.vencido],
                        backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                        borderRadius:    8,
                        borderWidth:     0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` RD$ ${Number(ctx.raw).toLocaleString('es-DO')}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: 11 },
                                callback: val => 'RD$ ' + Number(val).toLocaleString('es-DO', { notation: 'compact' }),
                            },
                            grid: { color: '#f1f5f9' },
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        },

        initChartSituacion() {
            const ctx = document.getElementById('chartSituacion');
            if (!ctx) return;
            const d = this.kpis.situacion_estudiantes;
            this.chartSituacion = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels:   ['Aprobados', 'Reprobados', 'Sin nota'],
                    datasets: [{
                        data:            [d.aprobados, d.reprobados, d.sin_nota],
                        backgroundColor: ['#22c55e', '#ef4444', '#94a3b8'],
                        borderWidth:     2,
                        borderColor:     '#fff',
                        hoverOffset:     6,
                    }]
                },
                options: {
                    cutout:  '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.raw}`
                            }
                        }
                    }
                }
            });
        },

        // ── Actualizar gráficas tras fetch ────────────────────────────────
        updateCharts() {
            if (this.chartAsistencia) {
                const d = this.kpis.asistencia_hoy;
                this.chartAsistencia.data.datasets[0].data = [d.presentes, d.ausentes, d.tardanzas, d.justificados];
                this.chartAsistencia.update();
            }
            if (this.chartPagos) {
                const d = this.kpis.pagos_mes;
                this.chartPagos.data.datasets[0].data = [d.cobrado, d.pendiente, d.vencido];
                this.chartPagos.update();
            }
            if (this.chartSituacion) {
                const d = this.kpis.situacion_estudiantes;
                this.chartSituacion.data.datasets[0].data = [d.aprobados, d.reprobados, d.sin_nota];
                this.chartSituacion.update();
            }
        },
    };
}
</script>
@endpush
