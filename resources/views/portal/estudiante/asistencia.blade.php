@extends('layouts.portal-estudiante')

@section('title', 'Mi Asistencia')

@section('activeKey', 'asistencia')

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-clipboard-check me-2"></i>Mi Asistencia</h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('portal.estudiante.asistencia.pdf') }}" target="_blank" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('portal.estudiante.asistencia.excel') }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('portal.estudiante.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Inicio
        </a>
    </div>
</div>

@if(! $matricula || $resumenAsistencia['total'] === 0)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No hay registros de asistencia disponibles.</p>
    </div>
</div>
@else
@php
    $pct      = $resumenAsistencia['porcentaje'];
    $pctColor = $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444');
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#1e3a6e;">{{ $resumenAsistencia['total'] }}</div>
            <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Total clases</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#10b981;">{{ $resumenAsistencia['presentes'] }}</div>
            <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Presentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#ef4444;">{{ $resumenAsistencia['ausentes'] }}</div>
            <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Ausentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:{{ $pctColor }};">{{ $pct !== null ? $pct . '%' : '—' }}</div>
            <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Asistencia</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
            <span class="fw-semibold">Asistencia general</span>
            <span style="color:{{ $pctColor }};font-weight:700;">{{ $pct }}%</span>
        </div>
        <div style="height:10px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $pct ?? 0 }}%;background:{{ $pctColor }};border-radius:99px;"></div>
        </div>
        @if($pct < 80)
        <div class="mt-2" style="font-size:.75rem;color:#ef4444;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>Tu asistencia está por debajo del mínimo requerido (80%).
        </div>
        @endif
    </div>
</div>

@if(!empty($resumenAsistencia['por_materia']))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="fw-bold mb-0"><i class="bi bi-journal-check me-2 text-primary"></i>Por materia</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th>Materia</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Presentes</th>
                        <th class="text-center">Ausentes</th>
                        <th style="min-width:120px;">Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($resumenAsistencia['por_materia'] as $mat)
                @php $mc = $mat['porcentaje'] >= 80 ? '#10b981' : ($mat['porcentaje'] >= 60 ? '#f59e0b' : '#ef4444'); @endphp
                <tr>
                    <td class="fw-semibold">{{ $mat['asignatura'] }}</td>
                    <td class="text-center">{{ $mat['total'] }}</td>
                    <td class="text-center text-success fw-semibold">{{ $mat['presentes'] }}</td>
                    <td class="text-center text-danger fw-semibold">{{ $mat['ausentes'] }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;height:7px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
                                <div style="height:100%;width:{{ $mat['porcentaje'] }}%;background:{{ $mc }};border-radius:99px;"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;color:{{ $mc }};min-width:38px;">{{ $mat['porcentaje'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endif
@endsection
