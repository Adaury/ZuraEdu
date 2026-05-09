@extends('layouts.admin')
@section('page-title', 'Nómina — Resumen Anual ' . $anio)

@section('content')

<x-breadcrumb :items="[
    ['label'=>'Dashboard','url'=>route('admin.dashboard')],
    ['label'=>'Nómina','url'=>route('admin.nomina.index')],
    ['label'=>'Resumen Anual '.$anio],
]"/>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2" style="color:#0f766e;"></i>Resumen Anual de Nómina</h4>
        <p class="text-muted small mb-0">Consolidado mensual · <strong>{{ $anio }}</strong> · {{ $totalEmpleados }} empleados activos</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <form method="GET" action="{{ route('admin.nomina.resumen-anual') }}" class="d-flex gap-2 align-items-center">
            <label class="form-label small fw-semibold mb-0">Año:</label>
            <input type="number" name="anio" value="{{ $anio }}" min="2020" max="{{ now()->year + 1 }}"
                   class="form-control form-control-sm" style="width:90px;" onchange="this.form.submit()">
        </form>
        <a href="{{ route('admin.nomina.resumen-anual.pdf', ['anio'=>$anio]) }}" target="_blank"
           class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.nomina.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@php
$totalBruto = $meses->sum('bruto');
$totalDeduc = $meses->sum('deduc');
$totalNeto  = $meses->sum('neto');
$mesesConDatos = $meses->filter(fn($m) => $m['bruto'] > 0);
@endphp

{{-- Chips de totales anuales --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total Bruto Anual','RD$ '.number_format($totalBruto,2),'#0f766e','bi-cash-stack','#d1fae5'],
        ['Total Deducciones','RD$ '.number_format($totalDeduc,2),'#dc2626','bi-dash-circle','#fee2e2'],
        ['Total Neto Anual','RD$ '.number_format($totalNeto,2),'#2563eb','bi-wallet2','#dbeafe'],
        ['Meses con datos',$mesesConDatos->count().' de 12','#7c3aed','bi-calendar-check','#ede9fe'],
    ] as [$lbl,$val,$clr,$icn,$bg])
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:42px;height:42px;background:{{ $bg }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.1rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.05rem;color:{{ $clr }};">{{ $val }}</div>
                    <div class="text-muted small">{{ $lbl }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabla mensual --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Mes</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Empleados</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Total Bruto</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Deducciones</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Total Neto</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Pagados</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;"></th>
    </tr>
</thead>
<tbody>
@foreach($meses as $m)
@php $sinDatos = $m['bruto'] == 0 && $m['empleados'] == 0; @endphp
<tr class="{{ $sinDatos ? 'opacity-50' : '' }}">
    <td class="px-4 py-3">
        <div class="fw-semibold">{{ $m['nombre'] }} {{ $anio }}</div>
        <div style="font-size:.72rem;color:#9ca3af;font-family:monospace;">{{ $m['mes'] }}</div>
    </td>
    <td class="py-3 text-center">
        @if(!$sinDatos)
        <span class="badge rounded-pill" style="background:#0f766e18;color:#0f766e;">{{ $m['empleados'] }}</span>
        @else
        <span class="text-muted small">—</span>
        @endif
    </td>
    <td class="py-3 text-end">
        @if(!$sinDatos)
        <span class="fw-bold" style="color:#0f766e;">RD$ {{ number_format($m['bruto'],2) }}</span>
        @else <span class="text-muted small">—</span> @endif
    </td>
    <td class="py-3 text-end">
        @if(!$sinDatos)
        <span class="text-danger fw-semibold">-RD$ {{ number_format($m['deduc'],2) }}</span>
        @else <span class="text-muted small">—</span> @endif
    </td>
    <td class="py-3 text-end">
        @if(!$sinDatos)
        <span class="fw-bold" style="color:#2563eb;font-size:.95rem;">RD$ {{ number_format($m['neto'],2) }}</span>
        @else <span class="text-muted small">—</span> @endif
    </td>
    <td class="py-3 text-center">
        @if(!$sinDatos)
        <span class="{{ $m['pagados'] === $m['empleados'] ? 'text-success' : 'text-warning' }} fw-semibold">
            {{ $m['pagados'] }} / {{ $m['empleados'] }}
        </span>
        @else <span class="text-muted small">—</span> @endif
    </td>
    <td class="py-3">
        @if(!$sinDatos)
        <a href="{{ route('admin.nomina.index', ['mes'=>$m['mes']]) }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:7px;padding:.3rem .65rem;font-size:.75rem;">
            <i class="bi bi-arrow-right"></i>
        </a>
        @endif
    </td>
</tr>
@endforeach
</tbody>
@if($mesesConDatos->isNotEmpty())
<tfoot style="background:#F0FDF4;border-top:2px solid #86EFAC;">
    <tr>
        <td class="px-4 py-3 fw-bold" style="color:#065f46;">TOTALES {{ $anio }}</td>
        <td class="py-3 text-center fw-bold">—</td>
        <td class="py-3 text-end fw-bold" style="color:#065f46;">RD$ {{ number_format($totalBruto,2) }}</td>
        <td class="py-3 text-end fw-bold text-danger">-RD$ {{ number_format($totalDeduc,2) }}</td>
        <td class="py-3 text-end fw-bold" style="color:#2563eb;font-size:1rem;">RD$ {{ number_format($totalNeto,2) }}</td>
        <td colspan="2"></td>
    </tr>
</tfoot>
@endif
</table>
</div>
</div>

@endsection
