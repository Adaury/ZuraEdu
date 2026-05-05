@extends('layouts.admin')
@section('page-title', 'Situación Final de Estudiantes')

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.reportes.index') }}" class="text-decoration-none">Reportes</a></li>
        <li class="breadcrumb-item active">Situación Final</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-person-check me-2"></i>Situación Final de Estudiantes
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">{{ $schoolYear?->nombre }}</p>
    </div>
    @if(request('grupo_id'))
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reportes.situacion.pdf', ['grupo_id'=>request('grupo_id')]) }}"
           target="_blank" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.reportes.situacion.excel', ['grupo_id'=>request('grupo_id')]) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
    @endif
</div>

{{-- Group selector --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.reportes.situacion') }}" class="d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Grupo / Sección</label>
                <select name="grupo_id" class="form-select form-select-sm" style="min-width:220px;" onchange="this.form.submit()">
                    <option value="">— Selecciona un grupo —</option>
                    @foreach($grupos as $g)
                    <option value="{{ $g->id }}" {{ request('grupo_id') == $g->id ? 'selected' : '' }}>
                        {{ $g->nombre_completo ?? $g->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($grupo && count($datos))
<div class="card border-0 shadow-sm">
    <div class="card-header border-0 py-3 px-4" style="background:#fff;">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0">{{ $grupo->nombre_completo ?? $grupo->nombre }}</h6>
            <div class="d-flex gap-2">
                <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.78rem;">
                    Aprobados: {{ collect($datos)->where('situacion_general','Aprobado')->count() }}
                </span>
                <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.78rem;">
                    Con reprobadas: {{ collect($datos)->where('situacion_general','Con materias reprobadas')->count() }}
                </span>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.83rem;">
            <thead style="background:#f8faff;">
                <tr>
                    <th style="padding:.6rem 1rem;color:#374151;">#</th>
                    <th style="padding:.6rem 1rem;color:#374151;">Estudiante</th>
                    <th class="text-center" style="padding:.6rem .5rem;color:#15803d;">Aprobadas (A)</th>
                    <th class="text-center" style="padding:.6rem .5rem;color:#991b1b;">Reprobadas (R)</th>
                    <th class="text-center" style="padding:.6rem .5rem;color:#6b7280;">Sin Registro</th>
                    <th class="text-center" style="padding:.6rem .5rem;color:#374151;">Total Materias</th>
                    <th class="text-center" style="padding:.6rem .5rem;color:#374151;">% Aprobación</th>
                    <th class="text-center" style="padding:.6rem 1rem;color:#374151;">Situación General</th>
                </tr>
            </thead>
            <tbody>
            @foreach($datos as $i => $d)
            <tr style="{{ $d['reprobadas'] > 0 ? 'background:#fff8f8;' : '' }}">
                <td style="padding:.5rem 1rem;color:#9ca3af;font-weight:600;">{{ $i+1 }}</td>
                <td style="padding:.5rem 1rem;font-weight:600;color:#1e293b;">
                    {{ $d['estudiante']->nombre_completo ?? ($d['estudiante']->name ?? '—') }}
                </td>
                <td class="text-center" style="padding:.5rem;font-weight:700;color:#15803d;">{{ $d['aprobadas'] }}</td>
                <td class="text-center" style="padding:.5rem;font-weight:700;color:{{ $d['reprobadas'] > 0 ? '#991b1b' : '#9ca3af' }};">{{ $d['reprobadas'] ?: '—' }}</td>
                <td class="text-center" style="padding:.5rem;color:#9ca3af;">{{ $d['sin_registro'] ?: '—' }}</td>
                <td class="text-center" style="padding:.5rem;color:#374151;">{{ $d['total'] }}</td>
                <td class="text-center" style="padding:.5rem;">
                    <div class="progress" style="height:6px;width:80px;margin:0 auto;">
                        <div class="progress-bar {{ $d['pct_aprobadas'] >= 80 ? 'bg-success' : ($d['pct_aprobadas'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                             style="width:{{ $d['pct_aprobadas'] }}%"></div>
                    </div>
                    <small style="font-size:.73rem;color:#374151;">{{ $d['pct_aprobadas'] }}%</small>
                </td>
                <td class="text-center" style="padding:.5rem 1rem;">
                    @if($d['situacion_general'] === 'Aprobado')
                        <span class="badge" style="background:#dcfce7;color:#15803d;">✓ Aprobado</span>
                    @elseif($d['situacion_general'] === 'Con materias reprobadas')
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">✗ Con reprobadas</span>
                    @else
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;">Sin registro</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif($grupo)
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-2">No hay registros de calificaciones para este grupo.</p>
</div>
@endif

@endsection
