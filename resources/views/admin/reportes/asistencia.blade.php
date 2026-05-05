@extends('layouts.admin')
@section('page-title', 'Reporte de Asistencia')

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.reportes.index') }}" class="text-decoration-none">Reportes</a></li>
        <li class="breadcrumb-item active">Asistencia</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0" style="color:var(--primary);">
        <i class="bi bi-calendar2-check me-2"></i>Reporte de Asistencia Institucional
    </h4>
    @if(request('grupo_id'))
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reportes.asistencia.pdf', ['grupo_id' => request('grupo_id')]) }}"
           target="_blank" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.reportes.asistencia.excel', ['grupo_id' => request('grupo_id')]) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.reportes.asistencia') }}" class="d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Grupo</label>
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
    <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="background:#fff;">
        <h6 class="fw-bold mb-0">{{ $grupo->nombre_completo ?? $grupo->nombre }}</h6>
        <div class="d-flex gap-2">
            <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.78rem;">
                Regular (≥75%): {{ collect($datos)->where('estado','Regular')->count() }}
            </span>
            <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.78rem;">
                Crítica (&lt;75%): {{ collect($datos)->where('estado','Crítica')->count() }}
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.83rem;">
            <thead style="background:#f8faff;">
                <tr>
                    <th style="padding:.6rem 1rem;">#</th>
                    <th style="padding:.6rem 1rem;">Estudiante</th>
                    <th class="text-center">% Promedio Asistencia</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Indicador Visual</th>
                </tr>
            </thead>
            <tbody>
            @foreach($datos as $i => $d)
            <tr>
                <td style="padding:.5rem 1rem;color:#9ca3af;font-weight:600;">{{ $i+1 }}</td>
                <td style="padding:.5rem 1rem;font-weight:600;">
                    {{ $d['estudiante']->nombre_completo ?? '—' }}
                </td>
                <td class="text-center" style="font-weight:700;
                    color:{{ $d['avg_asistencia'] === null ? '#9ca3af' : ($d['avg_asistencia'] >= 75 ? '#15803d' : '#991b1b') }};">
                    {{ $d['avg_asistencia'] !== null ? $d['avg_asistencia'].'%' : '—' }}
                </td>
                <td class="text-center">
                    @if($d['estado'] === 'Regular')
                        <span class="badge" style="background:#dcfce7;color:#15803d;">Regular</span>
                    @elseif($d['estado'] === 'Crítica')
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">⚠ Crítica</span>
                    @else
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;">Sin datos</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($d['avg_asistencia'] !== null)
                    <div class="progress" style="height:8px;width:120px;margin:0 auto;">
                        <div class="progress-bar {{ $d['avg_asistencia'] >= 75 ? 'bg-success' : 'bg-danger' }}"
                             style="width:{{ min($d['avg_asistencia'],100) }}%"></div>
                    </div>
                    @else
                    <span style="font-size:.75rem;color:#d1d5db;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
