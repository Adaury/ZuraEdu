@extends('layouts.admin')
@section('page-title', 'Asistencia — ' . ($matricula->estudiante?->apellidos ?? '—') . ', ' . ($matricula->estudiante?->nombres ?? ''))

@push('styles')
<style>
    .stat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .stat-label { font-size: .72rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
    .stat-value { font-size: 1.45rem; font-weight: 800; color: #111827; line-height: 1; }
    .pct-bar-wrap { height: 6px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
    .pct-bar { height: 100%; border-radius: 3px; }
    .section-title {
        font-size: .72rem; font-weight: 700; letter-spacing: .1em;
        text-transform: uppercase; color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .35rem; margin-bottom: 1rem;
        display: flex; align-items: center; gap: .4rem;
    }
    .asig-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        margin-bottom: .75rem;
    }
    .asig-name { font-weight: 700; color: #111827; font-size: .92rem; }
    .asig-sub  { font-size: .76rem; color: #9ca3af; }
    .badge-pct {
        font-size: .8rem; font-weight: 700;
        padding: .3rem .75rem; border-radius: 20px;
    }
    .badge-green  { background: #d1fae5; color: #065f46; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-gray   { background: #f3f4f6; color: #6b7280; }

    [data-theme="dark"] .stat-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .stat-value { color: #e2e8f0; }
    [data-theme="dark"] .pct-bar-wrap { background: #334155; }
    [data-theme="dark"] .asig-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .badge-green { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-yellow { background: #1c1000; color: #fcd34d; }
    [data-theme="dark"] .badge-red { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .badge-gray { background: #1e293b; color: #64748b; }
</style>
@endpush

@section('content')

{{-- Breadcrumb / actions --}}
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.asistencia.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Asistencia
    </a>
    <a href="{{ route('admin.matriculas.show', $matricula) }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-person me-1"></i>Ver Matrícula
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
        <a href="{{ route('admin.perfiles.estudiante.asistencia-pdf', $matricula->estudiante) }}"
           target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        @if(request('mes') && request('anio'))
        <a href="{{ route('admin.asistencia.reporteMensual.excel', ['matricula_id' => $matricula->id, 'mes' => request('mes'), 'anio' => request('anio')]) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        @endif
        <span style="font-size:.82rem;color:#6b7280;">
            <i class="bi bi-calendar3 me-1"></i>
            {{ $matricula->grupo->nombre_completo ?? 'Grupo no asignado' }}
        </span>
    </div>
</div>

{{-- Student header --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden;">
    <div style="height:6px;background:linear-gradient(90deg,#1e3a6e,var(--primary));"></div>
    <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#2a4f96,var(--primary));
                    color:#fff;display:flex;align-items:center;justify-content:center;
                    font-weight:800;font-size:1.1rem;flex-shrink:0;">
            {{ substr($matricula->estudiante?->nombres ?? '?', 0, 1) }}{{ substr($matricula->estudiante?->apellidos ?? '?', 0, 1) }}
        </div>
        <div>
            <div style="font-weight:800;font-size:1.05rem;color:#111827;">
                {{ $matricula->estudiante?->apellidos ?? '—' }}, {{ $matricula->estudiante?->nombres ?? '' }}
            </div>
            <div style="font-size:.8rem;color:#6b7280;">
                Matr. <span style="font-family:monospace;color:#2563eb;font-weight:700;">{{ $matricula->estudiante?->numero_matricula ?? '—' }}</span>
                &nbsp;·&nbsp;
                {{ $matricula->grupo->grado->nombre ?? '' }} {{ $matricula->grupo->seccion->nombre ?? '' }}
            </div>
        </div>
        <div class="ms-auto text-end d-none d-md-block">
            <div style="font-size:.72rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">
                Total registros
            </div>
            <div style="font-size:1.6rem;font-weight:800;color:var(--primary);line-height:1;">
                {{ collect($stats)->sum('total') }}
            </div>
        </div>
    </div>
</div>

@php
    /* Totals across all subjects */
    $totalClases    = collect($stats)->sum('total');
    $totalPresente  = collect($stats)->sum('presente');
    $totalAusente   = collect($stats)->sum('ausente');
    $totalTardanza  = collect($stats)->sum('tardanza');
    $totalJust      = collect($stats)->sum('justificado');
    $totalPct       = $totalClases > 0
        ? round((($totalPresente + $totalTardanza + $totalJust) / $totalClases) * 100, 1)
        : null;
@endphp

{{-- Global stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="stat-label">Clases</div>
                <div class="stat-value">{{ $totalClases }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="stat-label">Asistencias</div>
                <div class="stat-value">{{ $totalPresente }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#991b1b;">
                <i class="bi bi-x-circle"></i>
            </div>
            <div>
                <div class="stat-label">Ausencias</div>
                <div class="stat-value">{{ $totalAusente }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon"
                 style="background:{{ $totalPct !== null && $totalPct >= 90 ? '#d1fae5' : ($totalPct >= 75 ? '#fef3c7' : '#fee2e2') }};
                        color:{{ $totalPct !== null && $totalPct >= 90 ? '#065f46' : ($totalPct >= 75 ? '#92400e' : '#991b1b') }};">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <div class="stat-label">% Asistencia</div>
                <div class="stat-value">
                    {{ $totalPct !== null ? $totalPct.'%' : '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Per-subject breakdown --}}
<div class="section-title"><i class="bi bi-journal-text"></i>Detalle por Asignatura</div>

@if(empty($stats))
    <div class="text-center py-5 text-muted">
        <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:.35;display:block;margin-bottom:.75rem;"></i>
        No hay registros de asistencia para este estudiante.
    </div>
@else
    <div class="row g-3">
        @foreach($stats as $asignacionId => $s)
        @php
            $pct    = $s['pct_asistencia'];
            $bClass = $pct === null ? 'badge-gray'
                    : ($pct >= 90 ? 'badge-green' : ($pct >= 75 ? 'badge-yellow' : 'badge-red'));
            $barColor = $pct === null ? '#d1d5db'
                    : ($pct >= 90 ? '#10b981' : ($pct >= 75 ? '#f59e0b' : '#ef4444'));
            $asignatura = optional(optional($s['asignacion'])->asignatura)->nombre ?? 'Sin asignatura';
            $docente    = optional(optional($s['asignacion'])->docente)->nombre_completo ?? '—';
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="asig-card">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <div class="asig-name">{{ $asignatura }}</div>
                        <div class="asig-sub"><i class="bi bi-person me-1"></i>{{ $docente }}</div>
                    </div>
                    <span class="badge-pct {{ $bClass }}">
                        {{ $pct !== null ? $pct.'%' : '—' }}
                    </span>
                </div>
                {{-- Progress bar --}}
                <div class="pct-bar-wrap mb-3">
                    <div class="pct-bar" style="width:{{ $pct ?? 0 }}%;background:{{ $barColor }};"></div>
                </div>
                {{-- Mini stats --}}
                <div class="d-flex gap-3" style="font-size:.78rem;">
                    <div class="text-center">
                        <div style="font-weight:700;color:#111827;">{{ $s['total'] }}</div>
                        <div style="color:#9ca3af;">Total</div>
                    </div>
                    <div class="text-center">
                        <div style="font-weight:700;color:#10b981;">{{ $s['presente'] }}</div>
                        <div style="color:#9ca3af;">Presente</div>
                    </div>
                    <div class="text-center">
                        <div style="font-weight:700;color:#ef4444;">{{ $s['ausente'] }}</div>
                        <div style="color:#9ca3af;">Ausente</div>
                    </div>
                    <div class="text-center">
                        <div style="font-weight:700;color:#f59e0b;">{{ $s['tardanza'] }}</div>
                        <div style="color:#9ca3af;">Tardanza</div>
                    </div>
                    <div class="text-center">
                        <div style="font-weight:700;color:#6366f1;">{{ $s['justificado'] }}</div>
                        <div style="color:#9ca3af;">Justif.</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection
