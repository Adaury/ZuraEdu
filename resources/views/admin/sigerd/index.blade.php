@extends('layouts.admin')

@section('page-title', 'SIGERD — Integración MINERD')

@push('styles')
<style>
    /* ── Stat cards ─── */
    .stat-card {
        border-radius: 12px;
        padding: 1.1rem 1.3rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 1px 4px rgba(30,58,110,.05);
        transition: box-shadow .15s;
    }
    .stat-card:hover { box-shadow: 0 4px 12px rgba(30,58,110,.1); }
    .stat-icon {
        width: 46px; height: 46px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .stat-label { font-size: .72rem; font-weight: 700; text-transform: uppercase;
                  letter-spacing: .07em; color: #6b7280; margin-bottom: 1px; }
    .stat-value { font-size: 1.55rem; font-weight: 900; line-height: 1; color: #111827; }
    .stat-sub   { font-size: .72rem; color: #9ca3af; margin-top: 2px; }

    /* ── Export cards ─── */
    .export-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
        height: 100%;
        transition: box-shadow .15s;
    }
    .export-card:hover { box-shadow: 0 4px 16px rgba(30,58,110,.1); }
    .export-card .card-header {
        display: flex; align-items: center; gap: .6rem;
        padding: .85rem 1.1rem;
        font-size: .82rem; font-weight: 700; letter-spacing: .04em;
        border-bottom: 1px solid rgba(255,255,255,.15);
    }
    .export-card .card-body { padding: 1.1rem; }
    .export-card label { font-size: .73rem; font-weight: 700; text-transform: uppercase;
                         letter-spacing: .06em; color: #6b7280; margin-bottom: .3rem; }
    .export-card .form-select,
    .export-card .form-control { font-size: .82rem; border-radius: 8px; border-color: #d1d5db; }
    .export-card .form-select:focus,
    .export-card .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }

    /* ── Header gradient ─── */
    .sigerd-header {
        background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
        border-radius: 14px;
        padding: 1.3rem 1.6rem;
        color: #fff;
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .sigerd-header h4 { font-weight: 800; font-size: 1.05rem; margin: 0; }
    .sigerd-header small { font-size: .78rem; opacity: .8; }

    /* ── Log table ─── */
    .log-table-wrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(30,58,110,.04);
    }
    .log-table-wrap .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .71rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #2563eb;
        padding: .7rem 1rem;
        white-space: nowrap;
    }
    .log-table-wrap .table tbody td {
        padding: .65rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .82rem;
    }
    .log-table-wrap .table tbody tr:last-child td { border-bottom: none; }
    .log-table-wrap .table tbody tr:hover td { background: #fafbff; }

    /* ── Section title ─── */
    .section-title {
        font-size: .7rem; font-weight: 800; letter-spacing: .09em;
        text-transform: uppercase; color: #6b7280;
        display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem;
    }
    .section-title::after {
        content: ''; flex: 1; height: 1px; background: #e5e7eb;
    }

    /* ── Validation result ─── */
    #resultado-validacion-nomina .val-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#065f46;
                                            border-radius:8px; padding:.5rem .8rem; font-size:.81rem; }
    #resultado-validacion-nomina .val-err { border-radius:8px; font-size:.81rem; }

    /* ── Dark mode ─── */
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .export-card,
    [data-theme="dark"] .log-table-wrap { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .stat-label { color: #94a3b8; }
    [data-theme="dark"] .stat-value { color: #f1f5f9; }
    [data-theme="dark"] .stat-sub   { color: #64748b; }
    [data-theme="dark"] .section-title { color: #94a3b8; }
    [data-theme="dark"] .section-title::after { background: #334155; }
    [data-theme="dark"] .log-table-wrap .table thead th { background: #0f172a; color: #93c5fd; border-color: #334155; }
    [data-theme="dark"] .log-table-wrap .table tbody td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .log-table-wrap .table tbody tr:hover td { background: #0f172a; }
    [data-theme="dark"] .export-card label { color: #94a3b8; }
    [data-theme="dark"] .export-card .form-select,
    [data-theme="dark"] .export-card .form-control { background: #0f172a; border-color: #475569; color: #e2e8f0; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'SIGERD'],
]" />

{{-- Alerta configuración ─────────────────────────────────────────────── --}}
@if(!$config)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Módulo sin configurar.</strong>
        Defina el código y nombre del centro antes de exportar.
        <a href="{{ route('admin.sigerd.configuracion') }}" class="alert-link ms-1">Ir a Configuración →</a>
    </div>
</div>
@endif

{{-- Header del centro ───────────────────────────────────────────────── --}}
@if($config)
<div class="sigerd-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span style="background:rgba(255,255,255,.15);border-radius:8px;padding:.3rem .55rem;">
                <i class="bi bi-building-fill"></i>
            </span>
            <h4>{{ $config->nombre_centro ?? 'Centro Educativo' }}</h4>
        </div>
        <div class="d-flex gap-3 flex-wrap" style="opacity:.85;font-size:.78rem;">
            @if($config->codigo_centro)
            <span><i class="bi bi-hash me-1"></i>{{ $config->codigo_centro }}</span>
            @endif
            @if($config->distrito)
            <span><i class="bi bi-geo-alt me-1"></i>{{ $config->distrito }}</span>
            @endif
            @if($config->regional)
            <span><i class="bi bi-diagram-3 me-1"></i>{{ $config->regional }}</span>
            @endif
            @if($schoolYear)
            <span><i class="bi bi-calendar3 me-1"></i>{{ $schoolYear->nombre }}</span>
            @endif
        </div>
    </div>
    <a href="{{ route('admin.sigerd.configuracion') }}" class="btn btn-light btn-sm text-primary fw-600 flex-shrink-0">
        <i class="bi bi-gear me-1"></i>Configuración
    </a>
</div>
@endif

{{-- Stat cards ──────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-label">Grupos</div>
                <div class="stat-value">{{ $grupos->count() }}</div>
                <div class="stat-sub">Año activo</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="bi bi-collection-fill"></i>
            </div>
            <div>
                <div class="stat-label">Períodos</div>
                <div class="stat-value">{{ $periodos->count() }}</div>
                <div class="stat-sub">Configurados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="stat-label">Último Export</div>
                <div class="stat-value" style="font-size:1.05rem;">
                    {{ $ultimosLogs->first()?->created_at?->format('d/m/Y') ?? '—' }}
                </div>
                <div class="stat-sub">{{ $ultimosLogs->first()?->created_at?->format('H:i') ?? 'Nunca' }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <div class="stat-label">Exportaciones</div>
                <div class="stat-value">{{ $ultimosLogs->count() }}</div>
                <div class="stat-sub">Recientes</div>
            </div>
        </div>
    </div>
</div>

{{-- Formularios de exportación ──────────────────────────────────────── --}}
<p class="section-title"><i class="bi bi-download"></i> Exportaciones MINERD</p>

<div class="row g-3 mb-4">

    {{-- Nómina de Matrícula ─── --}}
    <div class="col-md-6">
        <div class="export-card">
            <div class="card-header" style="background:linear-gradient(90deg,#1d4ed8,#3b82f6);color:#fff;">
                <i class="bi bi-people-fill"></i> Nómina de Matrícula
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                    @csrf
                    <input type="hidden" name="tipo" value="nomina_matricula">
                    <div class="mb-2">
                        <label>Grupo</label>
                        <select name="grupo_id" class="form-select form-select-sm">
                            <option value="">Todos los grupos</option>
                            @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $grupo->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Formato</label>
                        <select name="formato" class="form-select form-select-sm">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-validar-nomina"
                                class="btn btn-outline-primary btn-sm" style="border-radius:8px;">
                            <i class="bi bi-check-circle me-1"></i>Validar
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
                            <i class="bi bi-download me-1"></i>Exportar
                        </button>
                    </div>
                </form>
                <div id="resultado-validacion-nomina" class="mt-2"></div>
            </div>
        </div>
    </div>

    {{-- Libro de Calificaciones ─── --}}
    <div class="col-md-6">
        <div class="export-card">
            <div class="card-header" style="background:linear-gradient(90deg,#059669,#10b981);color:#fff;">
                <i class="bi bi-journal-check"></i> Libro de Calificaciones
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                    @csrf
                    <input type="hidden" name="tipo" value="calificaciones">
                    <div class="mb-2">
                        <label>Grupo <span class="text-danger">*</span></label>
                        <select name="grupo_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar grupo —</option>
                            @foreach($grupos as $g)
                            <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Período</label>
                        <select name="periodo_id" class="form-select form-select-sm">
                            <option value="">Todos los períodos</option>
                            @foreach($periodos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Formato</label>
                        <select name="formato" class="form-select form-select-sm">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" style="border-radius:8px;">
                        <i class="bi bi-download me-1"></i>Exportar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Nómina de Docentes ─── --}}
    <div class="col-md-6">
        <div class="export-card">
            <div class="card-header" style="background:linear-gradient(90deg,#7c3aed,#a78bfa);color:#fff;">
                <i class="bi bi-person-badge"></i> Nómina de Docentes
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                    @csrf
                    <input type="hidden" name="tipo" value="docentes">
                    <div class="mb-3">
                        <label>Formato</label>
                        <select name="formato" class="form-select form-select-sm">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <p class="text-muted" style="font-size:.78rem;margin-bottom:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Exporta todos los docentes del año escolar activo con sus asignaciones.
                    </p>
                    <button type="submit" class="btn btn-sm text-white fw-600" style="background:#7c3aed;border-radius:8px;">
                        <i class="bi bi-download me-1"></i>Exportar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Registro de Asistencia ─── --}}
    <div class="col-md-6">
        <div class="export-card">
            <div class="card-header" style="background:linear-gradient(90deg,#b45309,#f59e0b);color:#fff;">
                <i class="bi bi-calendar-check"></i> Registro de Asistencia
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                    @csrf
                    <input type="hidden" name="tipo" value="asistencia">
                    <div class="mb-2">
                        <label>Grupo</label>
                        <select name="grupo_id" class="form-select form-select-sm">
                            <option value="">Todos los grupos</option>
                            @foreach($grupos as $g)
                            <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label>Desde</label>
                            <input type="date" name="desde" class="form-control form-control-sm"
                                   value="{{ now()->startOfYear()->toDateString() }}">
                        </div>
                        <div class="col-6">
                            <label>Hasta</label>
                            <input type="date" name="hasta" class="form-control form-control-sm"
                                   value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Formato</label>
                        <select name="formato" class="form-select form-select-sm">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm fw-600" style="border-radius:8px;">
                        <i class="bi bi-download me-1"></i>Exportar
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- Historial de exportaciones ──────────────────────────────────────── --}}
@if($ultimosLogs->count())
<p class="section-title"><i class="bi bi-clock-history"></i> Historial Reciente</p>
<div class="log-table-wrap">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Grupo</th>
                    <th>Formato</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th style="text-align:right;">Registros</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ultimosLogs as $log)
            @php
                $tipoBadge = match($log->tipo) {
                    'nomina_matricula' => ['label' => 'Matrícula',       'bg' => '#dbeafe', 'color' => '#1d4ed8'],
                    'calificaciones'   => ['label' => 'Calificaciones',  'bg' => '#dcfce7', 'color' => '#16a34a'],
                    'docentes'         => ['label' => 'Docentes',        'bg' => '#ede9fe', 'color' => '#7c3aed'],
                    'asistencia'       => ['label' => 'Asistencia',      'bg' => '#fef3c7', 'color' => '#b45309'],
                    default            => ['label' => $log->tipo,        'bg' => '#f3f4f6', 'color' => '#6b7280'],
                };
                $fmtBadge = match($log->formato) {
                    'excel' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
                    'pdf'   => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                    'csv'   => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                };
            @endphp
            <tr>
                <td>
                    <span style="background:{{ $tipoBadge['bg'] }};color:{{ $tipoBadge['color'] }};
                                 font-size:.68rem;font-weight:700;padding:.2rem .6rem;border-radius:20px;
                                 white-space:nowrap;">
                        {{ $tipoBadge['label'] }}
                    </span>
                </td>
                <td>{{ $log->grupo?->nombre_completo ?? '<span class="text-muted">Todos</span>' }}</td>
                <td>
                    <span style="background:{{ $fmtBadge['bg'] }};color:{{ $fmtBadge['color'] }};
                                 font-size:.68rem;font-weight:700;padding:.2rem .55rem;border-radius:20px;">
                        {{ strtoupper($log->formato) }}
                    </span>
                </td>
                <td class="text-muted" style="font-size:.8rem;">
                    {{ $log->created_at?->format('d/m/Y') }}
                    <span style="color:#9ca3af;">{{ $log->created_at?->format('H:i') }}</span>
                </td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td style="text-align:right;font-weight:700;">
                    {{ number_format($log->total_registros ?? 0) }}
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
document.getElementById('btn-validar-nomina').addEventListener('click', function () {
    const card      = this.closest('.export-card');
    const grupoId   = card.querySelector('[name=grupo_id]').value;
    const resultDiv = document.getElementById('resultado-validacion-nomina');
    resultDiv.innerHTML = '<span class="text-secondary" style="font-size:.8rem;"><i class="bi bi-hourglass-split me-1"></i>Validando datos…</span>';

    fetch('{{ route("admin.sigerd.validar") }}?tipo=nomina_matricula&grupo_id=' + encodeURIComponent(grupoId))
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                resultDiv.innerHTML = `<div class="val-ok"><i class="bi bi-check-circle-fill me-1"></i><strong>${data.total}</strong> registros revisados — sin errores.</div>`;
            } else {
                let html = `<div class="alert alert-danger val-err p-2 mb-0"><strong>${data.errores.length} error(es):</strong><ul class="mb-0 ps-3 mt-1">`;
                data.errores.forEach(e => { html += `<li>${e.descripcion ?? e}</li>`; });
                html += '</ul></div>';
                resultDiv.innerHTML = html;
            }
        })
        .catch(() => {
            resultDiv.innerHTML = '<span class="text-danger" style="font-size:.8rem;"><i class="bi bi-x-circle me-1"></i>Error al conectar.</span>';
        });
});
</script>
@endpush

@endsection
