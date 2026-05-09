@extends('layouts.admin')
@section('page-title', 'Inventario — Historial Global')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.35rem; font-weight:800; color:var(--primary); margin:0; }

.stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.1rem 1.4rem; display:flex; align-items:center; gap:1rem; }
.stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.stat-label { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }
.stat-value { font-size:1.4rem; font-weight:800; color:#111827; line-height:1.2; }

.filter-bar { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1rem 1.25rem; margin-bottom:1.25rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; }
.filter-bar select, .filter-bar input { font-size:.83rem; padding:.4rem .75rem; border-radius:8px; border:1px solid #d1d5db; background:#f9fafb; height:36px; }
.filter-bar select:focus, .filter-bar input:focus { outline:none; border-color:var(--primary); background:#fff; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover { background:#f9fafb; }

.tipo-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-clock-history me-2" style="color:var(--primary)"></i>Historial Global de Movimientos</h1>
        <small style="color:#6b7280;">Todos los movimientos de inventario registrados en el sistema</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inventario.historial.excel', request()->query()) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al Inventario
        </a>
    </div>
</div>

{{-- Tarjetas resumen --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;"><i class="bi bi-arrow-down-circle text-success"></i></div>
            <div>
                <div class="stat-label">Total Entradas</div>
                <div class="stat-value" style="color:#065f46;">+{{ number_format($totalEntradas) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;"><i class="bi bi-arrow-up-circle text-danger"></i></div>
            <div>
                <div class="stat-label">Total Salidas</div>
                <div class="stat-value" style="color:#991b1b;">-{{ number_format($totalSalidas) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;"><i class="bi bi-arrows-angle-contract" style="color:#92400e;"></i></div>
            <div>
                <div class="stat-label">Ajustes</div>
                <div class="stat-value" style="color:#92400e;">{{ number_format($totalAjustes) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.inventario.historial') }}">
<div class="filter-bar">
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Tipo</label>
        <select name="tipo" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($tipos as $key => $t)
            <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>{{ $t['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div style="min-width:200px;">
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Artículo</label>
        <select name="articulo_id" onchange="this.form.submit()" style="min-width:200px;">
            <option value="">Todos los artículos</option>
            @foreach($articulos as $id => $nombre)
            <option value="{{ $id }}" {{ request('articulo_id') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Desde</label>
        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}">
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Hasta</label>
        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">
        <i class="bi bi-search"></i> Filtrar
    </button>
    @if(request()->hasAny(['tipo', 'articulo_id', 'fecha_desde', 'fecha_hasta']))
    <a href="{{ route('admin.inventario.historial') }}" class="btn btn-outline-secondary btn-sm" style="height:36px;">
        <i class="bi bi-x-lg"></i> Limpiar
    </a>
    @endif
</div>
</form>

{{-- Tabla --}}
<div class="table-card">
    <div style="padding:.9rem 1.25rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-weight:700; font-size:.95rem; color:var(--primary);">
            <i class="bi bi-list-ul me-1"></i>Movimientos
        </span>
        <span style="font-size:.78rem; color:#9ca3af;">{{ $movimientos->total() }} registros</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Artículo</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Motivo</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
            @php $tipoInfo = $mov->tipo_info; @endphp
            <tr>
                <td style="white-space:nowrap; color:#6b7280; font-size:.8rem;">
                    {{ $mov->created_at->format('d/m/Y H:i') }}
                </td>
                <td>
                    <a href="{{ route('admin.inventario.movimientos', $mov->articulo_id) }}"
                       style="font-weight:600; color:#111827; text-decoration:none;"
                       class="link-primary">
                        {{ $mov->articulo?->nombre ?? '—' }}
                    </a>
                    @if($mov->articulo)
                    <div style="font-size:.73rem; color:#9ca3af;">
                        {{ $mov->articulo->categoria_info['label'] ?? '' }}
                    </div>
                    @endif
                </td>
                <td>
                    <span class="tipo-chip" style="background:{{ $tipoInfo['color'] }}; color:{{ $tipoInfo['text'] }};">
                        <i class="{{ $tipoInfo['icon'] }}"></i>{{ $tipoInfo['label'] }}
                    </span>
                </td>
                <td>
                    <span style="font-size:1rem; font-weight:800; color:{{ $tipoInfo['text'] }};">
                        {{ $tipoInfo['sign'] }}{{ $mov->cantidad }}
                    </span>
                </td>
                <td style="color:#374151; max-width:220px;">{{ $mov->motivo }}</td>
                <td style="font-size:.8rem; color:#6b7280;">
                    {{ $mov->usuario?->name ?? 'Sistema' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                    No hay movimientos registrados
                    @if(request()->hasAny(['tipo','articulo_id','fecha_desde','fecha_hasta']))
                    con los filtros aplicados.
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($movimientos->hasPages())
    <div style="padding:1rem 1.25rem; border-top:1px solid #f3f4f6;">
        {{ $movimientos->links() }}
    </div>
    @endif
</div>
@endsection
