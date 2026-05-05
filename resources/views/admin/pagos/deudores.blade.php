@extends('layouts.admin')
@section('page-title', 'Reporte de Deudores')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.35rem; font-weight:800; color:var(--primary); margin:0; }

.alerta-bar { background:#fee2e2; border:1px solid #fca5a5; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:.85rem; }
.alerta-bar i { font-size:1.4rem; color:#dc2626; flex-shrink:0; }
.alerta-bar .title { font-weight:800; color:#991b1b; font-size:.95rem; }
.alerta-bar .sub { font-size:.8rem; color:#7f1d1d; margin-top:.1rem; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover { background:#fef2f2; }

.badge-vencido { background:#fee2e2; color:#991b1b; border-radius:20px; padding:.22rem .6rem; font-size:.71rem; font-weight:700; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.pagos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>Reporte de Deudores
    </h1>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.pagos.deudores.recordatorio') }}"
              onsubmit="return confirm('¿Enviar recordatorio de pago a todos los representantes con cuotas vencidas?')">
            @csrf
            @if(request('grupo_id'))<input type="hidden" name="grupo_id" value="{{ request('grupo_id') }}">@endif
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-bell-fill me-1"></i>Enviar Recordatorio
            </button>
        </form>
        <a href="{{ route('admin.pagos.index', ['estado'=>'vencido']) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-list-ul me-1"></i>Ver vencidos
        </a>
        <a href="{{ route('admin.pagos.deudores.pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.pagos.deudores.excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

@if($matriculas->isEmpty())
<div class="text-center py-5">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
    <p class="mt-3 text-muted">¡Sin deudores! Todos los pagos están al día.</p>
</div>
@else

<div class="alerta-bar">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <div class="title">{{ $matriculas->count() }} estudiante(s) con pagos vencidos</div>
        <div class="sub">Deuda total: <strong>RD$ {{ number_format($totalDeuda, 2) }}</strong></div>
    </div>
</div>

{{-- Filtro por grupo --}}
<form method="GET" action="{{ route('admin.pagos.deudores') }}" class="mb-3 d-flex gap-2 align-items-center">
    <select name="grupo_id" class="form-select form-select-sm" style="max-width:220px;">
        <option value="">Todos los grupos</option>
        @foreach($grupos as $g)
        <option value="{{ $g->id }}" {{ request('grupo_id')==$g->id ? 'selected':'' }}>
            {{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}
        </option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
    <a href="{{ route('admin.pagos.deudores') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
</form>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Estudiante</th>
                <th>Grupo</th>
                <th>Cuotas vencidas</th>
                <th>Total deuda</th>
                <th>Primera mora</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $i => $mat)
            <tr>
                <td class="text-muted" style="font-size:.78rem;">{{ $i + 1 }}</td>
                <td>
                    <div class="fw-semibold" style="color:#1e293b;">{{ $mat->estudiante->apellido }}, {{ $mat->estudiante->nombre }}</div>
                    <div style="font-size:.72rem;color:#6b7280;">Matr. {{ $mat->estudiante->matricula ?? '—' }}</div>
                </td>
                <td style="font-size:.82rem;">{{ $mat->grupo->grado->nombre ?? '—' }} {{ $mat->grupo->seccion->nombre ?? '' }}</td>
                <td>
                    <span class="badge-vencido">{{ $mat->cuotas_vencidas }} cuota(s)</span>
                </td>
                <td class="fw-bold" style="color:#991b1b;">RD$ {{ number_format($mat->total_vencido, 2) }}</td>
                <td style="font-size:.8rem;color:#6b7280;">
                    {{ $mat->primera_mora ? \Carbon\Carbon::parse($mat->primera_mora)->format('d/m/Y') : '—' }}
                </td>
                <td>
                    <a href="{{ route('admin.pagos.por-estudiante', $mat) }}"
                       class="btn btn-sm btn-outline-danger py-1" style="font-size:.75rem;">
                        <i class="bi bi-cash-coin me-1"></i>Ver cuenta
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#fef2f2;">
                <td colspan="4" class="fw-bold text-end" style="font-size:.83rem;color:#991b1b;">Total deuda acumulada:</td>
                <td class="fw-bold" style="color:#991b1b;font-size:.95rem;">RD$ {{ number_format($totalDeuda, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif
@endsection
