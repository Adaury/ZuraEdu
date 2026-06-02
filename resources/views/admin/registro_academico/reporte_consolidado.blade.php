@extends('layouts.admin')
@section('page-title', 'Reporte Consolidado')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-bar-chart-steps me-2" style="color:var(--secondary);"></i>Reporte Consolidado de Matrícula
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            Distribución por grado y sección
            @if($schoolYear) — <strong>{{ $schoolYear->nombre }}</strong> @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.registro-academico.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('admin.registro-academico.reporte-consolidado.excel', ['year_id' => $schoolYear?->id]) }}"
           class="btn btn-sm btn-outline-success" style="border-radius:8px;">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.registro-academico.reporte-consolidado.pdf', ['year_id' => $schoolYear?->id]) }}"
           class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
    </div>
</div>

{{-- Selector año escolar --}}
<form method="GET" class="d-flex gap-2 mb-4 align-items-center">
    <label style="font-size:.84rem;font-weight:600;">Año Escolar:</label>
    <select name="year_id" class="form-select form-select-sm" style="max-width:200px;border-radius:8px;" onchange="this.form.submit()">
        @foreach($schoolYears as $sy)
        <option value="{{ $sy->id }}" {{ $schoolYear?->id === $sy->id ? 'selected' : '' }}>{{ $sy->nombre }}</option>
        @endforeach
    </select>
</form>

{{-- Totales generales --}}
<div class="row g-3 mb-4">
    @foreach([['Total Matrícula','total','#1d4ed8','bi-people-fill'],['Activos','activos','#065f46','bi-check-circle-fill'],['Retirados','retirados','#991b1b','bi-person-dash-fill'],['Transferidos','transferidos','#92400e','bi-arrow-left-right']] as [$label,$key,$color,$icon])
    <div class="col-6 col-md-3">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.25rem;">
            <div style="font-size:.73rem;color:#6b7280;font-weight:600;text-transform:uppercase;">{{ $label }}</div>
            <div style="font-size:1.9rem;font-weight:800;color:{{ $color }};">{{ number_format($totales[$key]) }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabla por grado --}}
@if($reporte->isEmpty())
<div class="text-center py-5" style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;">
    <i class="bi bi-bar-chart" style="font-size:3rem;opacity:.25;"></i>
    <p class="mt-3 text-muted">No hay datos de matrícula para este año escolar.</p>
</div>
@else
@foreach($reporte as $gradoNombre => $grupos)
<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:1.25rem;">
    <div style="padding:.85rem 1.25rem;background:#eff6ff;border-bottom:1px solid #bfdbfe;">
        <span style="font-weight:800;color:#1e3a6e;font-size:.9rem;">
            <i class="bi bi-mortarboard me-1"></i>{{ $gradoNombre }}
        </span>
        <span style="float:right;font-size:.8rem;color:#1d4ed8;font-weight:700;">
            Total: {{ $grupos->sum('total') }} estudiantes
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.84rem;">
            <thead style="background:#f8faff;">
                <tr>
                    <th class="ps-4">Sección</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Masculino</th>
                    <th class="text-center">Femenino</th>
                    <th class="text-center">Activos</th>
                    <th class="text-center">Retirados</th>
                    <th class="text-center">Transferidos</th>
                    <th class="text-center pe-4">% Activos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupos as $g)
                @php $pct = $g->total > 0 ? round($g->activos / $g->total * 100) : 0; @endphp
                <tr>
                    <td class="ps-4 fw-semibold">{{ $gradoNombre }} {{ $g->seccion }}</td>
                    <td class="text-center fw-bold">{{ $g->total }}</td>
                    <td class="text-center" style="color:#1d4ed8;">{{ $g->masculino }}</td>
                    <td class="text-center" style="color:#9333ea;">{{ $g->femenino }}</td>
                    <td class="text-center" style="color:#065f46;">{{ $g->activos }}</td>
                    <td class="text-center" style="color:{{ $g->retirados > 0 ? '#991b1b' : '#6b7280' }};">{{ $g->retirados }}</td>
                    <td class="text-center" style="color:{{ $g->transferidos > 0 ? '#92400e' : '#6b7280' }};">{{ $g->transferidos }}</td>
                    <td class="text-center pe-4">
                        <span style="background:{{ $pct >= 90 ? '#d1fae5' : ($pct >= 75 ? '#fef3c7' : '#fee2e2') }};color:{{ $pct >= 90 ? '#065f46' : ($pct >= 75 ? '#92400e' : '#991b1b') }};border-radius:20px;padding:.15rem .55rem;font-size:.75rem;font-weight:700;">
                            {{ $pct }}%
                        </span>
                    </td>
                </tr>
                @endforeach
                {{-- Subtotal por grado --}}
                <tr style="background:#f0f7ff;font-weight:700;font-size:.83rem;">
                    <td class="ps-4">Subtotal {{ $gradoNombre }}</td>
                    <td class="text-center">{{ $grupos->sum('total') }}</td>
                    <td class="text-center">{{ $grupos->sum('masculino') }}</td>
                    <td class="text-center">{{ $grupos->sum('femenino') }}</td>
                    <td class="text-center">{{ $grupos->sum('activos') }}</td>
                    <td class="text-center">{{ $grupos->sum('retirados') }}</td>
                    <td class="text-center">{{ $grupos->sum('transferidos') }}</td>
                    <td class="text-center pe-4">
                        @php $subPct = $grupos->sum('total') > 0 ? round($grupos->sum('activos') / $grupos->sum('total') * 100) : 0; @endphp
                        {{ $subPct }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach

{{-- Total general --}}
<div style="background:#1e3a6e;border-radius:14px;padding:1rem 1.5rem;color:#fff;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
    <span style="font-weight:800;font-size:.95rem;"><i class="bi bi-sigma me-2"></i>TOTAL GENERAL</span>
    <div class="d-flex gap-4 flex-wrap" style="font-size:.85rem;">
        <span>Total: <strong>{{ number_format($totales['total']) }}</strong></span>
        <span>Activos: <strong style="color:#6ee7b7;">{{ number_format($totales['activos']) }}</strong></span>
        <span>Retirados: <strong style="color:#fca5a5;">{{ number_format($totales['retirados']) }}</strong></span>
        <span>Transferidos: <strong style="color:#fde68a;">{{ number_format($totales['transferidos']) }}</strong></span>
        <span>M: <strong>{{ number_format($totales['masculino']) }}</strong></span>
        <span>F: <strong>{{ number_format($totales['femenino']) }}</strong></span>
    </div>
</div>
@endif
@endsection
