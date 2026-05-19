@extends('layouts.admin')

@section('page-title', 'Resumen de Matrículas')

@push('styles')
<style>
    .resumen-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
        overflow: hidden;
    }
    .stat-block {
        border-radius: 10px;
        padding: .85rem 1rem;
        text-align: center;
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #2563eb;
        padding: .7rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .65rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }
    .grupo-chip {
        background: #eef2ff;
        color: var(--primary);
        border-radius: 6px;
        padding: .15rem .55rem;
        font-size: .78rem;
        font-weight: 700;
    }
    .progress-bar-wrap {
        background: #e5e7eb;
        border-radius: 6px;
        height: 8px;
        overflow: hidden;
        min-width: 70px;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width .5s ease;
    }
    .ciclo-header td {
        background: #eff6ff !important;
        font-weight: 800;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #1e40af;
        padding: .45rem 1rem !important;
        border-bottom: 1px solid #bfdbfe !important;
    }
    .tfoot-row td {
        background: #f8fafc !important;
        font-weight: 800;
        border-top: 2px solid #e5e7eb !important;
        font-size: .85rem;
        color: #1e293b;
    }

    [data-theme="dark"] .resumen-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table thead th {
        background: #1e3a8a !important;
        border-color: #334155 !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .table tbody td {
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .table tbody tr:hover td { background: #334155 !important; }
    [data-theme="dark"] .ciclo-header td {
        background: #1e3a8a !important;
        color: #93c5fd !important;
        border-color: #1e40af !important;
    }
    [data-theme="dark"] .tfoot-row td {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .grupo-chip {
        background: rgba(59,130,246,.18) !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .progress-bar-wrap { background: #334155 !important; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.matriculas.index') }}" class="text-decoration-none">Matrículas</a></li>
        <li class="breadcrumb-item active">Resumen</li>
    </ol>
</nav>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 p-slide-up">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-bar-chart-line me-2"></i>Resumen de Matrículas
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar: <strong>{{ $schoolYear->nombre }}</strong>
            @else
                Sin año escolar activo
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.matriculas.lista-pdf') }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.matriculas.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.matriculas.index') }}" class="btn btn-sm"
           style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@php
    $totalActivas    = $totales['activa']      ?? 0;
    $totalRetiradas  = $totales['retirada']    ?? 0;
    $totalTransfer   = $totales['transferida'] ?? 0;
    $totalGlobal     = collect($totales)->sum();
@endphp

{{-- KPI cards --}}
<div class="row g-3 mb-4 p-slide-up p-delay-1">
    <div class="col-6 col-md-3">
        <div class="resumen-card p-3 text-center">
            <div style="font-size:1.9rem;font-weight:900;color:#1d4ed8;">{{ $totalGlobal }}</div>
            <div style="font-size:.75rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;">Total Matrículas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="resumen-card p-3 text-center">
            <div style="font-size:1.9rem;font-weight:900;color:#059669;">{{ $totalActivas }}</div>
            <div style="font-size:.75rem;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.05em;">Activas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="resumen-card p-3 text-center">
            <div style="font-size:1.9rem;font-weight:900;color:#dc2626;">{{ $totalRetiradas }}</div>
            <div style="font-size:.75rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.05em;">Retiradas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="resumen-card p-3 text-center">
            @if($inscPendientes > 0)
                <a href="{{ route('admin.inscripciones.index', ['estado' => 'pendiente']) }}"
                   class="text-decoration-none d-block">
                    <div style="font-size:1.9rem;font-weight:900;color:#d97706;">{{ $inscPendientes }}</div>
                    <div style="font-size:.75rem;font-weight:700;color:#d97706;text-transform:uppercase;letter-spacing:.05em;">Inscr. Pendientes</div>
                </a>
            @else
                <div style="font-size:1.9rem;font-weight:900;color:#9ca3af;">0</div>
                <div style="font-size:.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Inscr. Pendientes</div>
            @endif
        </div>
    </div>
</div>

{{-- Tabla por grupo --}}
<div class="resumen-card p-slide-up p-delay-2">
    @if($grupos->isEmpty())
        <div class="text-center py-5 px-3">
            <i class="bi bi-grid" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
            <h6 class="fw-semibold text-muted">No hay grupos para este año escolar</h6>
            <a href="{{ route('admin.grupos.create') }}" class="btn btn-sm mt-2" style="background:var(--primary);color:#fff;border-radius:8px;">
                <i class="bi bi-plus-lg me-1"></i>Crear Grupo
            </a>
        </div>
    @else
        @php
            $gruposPorCiclo = $grupos->groupBy(fn($g) => $g->grado->ciclo ?? 'primer_ciclo');
            $nivelesMap = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
            $cicloLabels = ['primer_ciclo' => 'Primer Ciclo (1ro – 3ro)', 'segundo_ciclo' => 'Segundo Ciclo (4to – 6to)'];
        @endphp
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th class="text-center">Activas</th>
                        <th class="text-center">Retiradas</th>
                        <th class="text-center">Transferidas</th>
                        <th class="text-center">Total</th>
                        <th>Capacidad</th>
                        <th style="min-width:110px;">Ocupación</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['primer_ciclo', 'segundo_ciclo'] as $ciclo)
                        @if($gruposPorCiclo->has($ciclo))
                            <tr class="ciclo-header">
                                <td colspan="8">
                                    <i class="bi bi-{{ $ciclo === 'primer_ciclo' ? '1' : '2' }}-circle me-1"></i>
                                    {{ $cicloLabels[$ciclo] ?? $ciclo }}
                                </td>
                            </tr>
                            @foreach($gruposPorCiclo[$ciclo] as $grupo)
                                @php
                                    $datos    = $conteosPorGrupo->get($grupo->id, collect());
                                    $activas  = $datos->firstWhere('estado', 'activa')?->total     ?? 0;
                                    $retirad  = $datos->firstWhere('estado', 'retirada')?->total    ?? 0;
                                    $transfer = $datos->firstWhere('estado', 'transferida')?->total ?? 0;
                                    $total    = $activas + $retirad + $transfer;
                                    $cap      = $grupo->capacidad ?? 0;
                                    $pct      = $cap > 0 ? min(100, round($activas / $cap * 100)) : null;
                                    $barColor = $pct === null ? '#9ca3af' : ($pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#d97706' : '#059669'));
                                    $pref     = $nivelesMap[$grupo->grado->nivel ?? 0] ?? ($grupo->grado->nivel . 'mo');
                                    $gLabel   = $pref . ' ' . ($grupo->seccion->nombre ?? '');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="grupo-chip">{{ $gLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span style="font-weight:700;color:#059669;">{{ $activas }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($retirad > 0)
                                            <span style="font-weight:700;color:#dc2626;">{{ $retirad }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($transfer > 0)
                                            <span style="font-weight:700;color:#d97706;">{{ $transfer }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ $total }}</strong>
                                    </td>
                                    <td class="text-center" style="font-size:.8rem;color:#6b7280;">
                                        {{ $cap > 0 ? $cap : '—' }}
                                    </td>
                                    <td>
                                        @if($pct !== null)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress-bar-wrap flex-grow-1">
                                                    <div class="progress-bar-fill"
                                                         style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                                                </div>
                                                <span style="font-size:.75rem;font-weight:700;color:{{ $barColor }};white-space:nowrap;">
                                                    {{ $pct }}%
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size:.78rem;">Sin capacidad</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.matriculas.index', ['grupo_id' => $grupo->id]) }}"
                                           class="btn btn-sm"
                                           style="background:#f0f4f8;color:var(--primary);border:1px solid #e5e7eb;
                                                  border-radius:6px;padding:.2rem .55rem;font-size:.75rem;">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="tfoot-row">
                        <td><strong>TOTAL GENERAL</strong></td>
                        <td class="text-center" style="color:#059669;"><strong>{{ $totalActivas }}</strong></td>
                        <td class="text-center" style="color:#dc2626;"><strong>{{ $totalRetiradas }}</strong></td>
                        <td class="text-center" style="color:#d97706;"><strong>{{ $totalTransfer }}</strong></td>
                        <td class="text-center"><strong>{{ $totalGlobal }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

@endsection
