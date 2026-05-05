@extends('layouts.admin')
@section('page-title', 'Inventario Escolar')

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

.badge-cat { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-est { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }

.qty-bar-wrap { display:flex; align-items:center; gap:.5rem; min-width:120px; }
.qty-bar { flex:1; height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; }
.qty-bar-fill { height:100%; border-radius:3px; transition:width .3s; }

.chip-categoria { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:20px; font-size:.75rem; font-weight:700; cursor:pointer; border:2px solid transparent; transition:all .15s; }
.chip-categoria:hover { filter:brightness(.95); }
.chip-categoria.active { border-color:var(--primary); }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="bi bi-archive-fill me-2" style="color:var(--primary)"></i>Inventario Escolar</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.inventario.pdf', request()->query()) }}"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.inventario.excel', request()->query()) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.inventario.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuevo Artículo
        </a>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tarjetas resumen --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;"><i class="bi bi-archive text-primary"></i></div>
            <div>
                <div class="stat-label">Total Artículos</div>
                <div class="stat-value">{{ $totalArticulos }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe;"><i class="bi bi-grid text-purple" style="color:#7c3aed;"></i></div>
            <div>
                <div class="stat-label">Categorías</div>
                <div class="stat-value">{{ $totalCategorias }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;"><i class="bi bi-check2-circle text-success"></i></div>
            <div>
                <div class="stat-label">Disponibles</div>
                <div class="stat-value" style="color:#065f46;">{{ $totalDisponibles }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;"><i class="bi bi-exclamation-triangle text-danger"></i></div>
            <div>
                <div class="stat-label">En Mal Estado</div>
                <div class="stat-value" style="color:#991b1b;">{{ $enMalEstado }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Chips por categoría --}}
@if($porCategoria->isNotEmpty())
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.inventario.index', array_merge(request()->except('categoria'), [])) }}"
       class="chip-categoria {{ !request('categoria') ? 'active' : '' }}"
       style="background:#f3f4f6; color:#374151;">
        <i class="bi bi-grid-3x3-gap"></i> Todas
    </a>
    @foreach($categorias as $key => $cat)
    @if(isset($porCategoria[$key]))
    <a href="{{ route('admin.inventario.index', array_merge(request()->except('categoria'), ['categoria' => $key])) }}"
       class="chip-categoria {{ request('categoria') === $key ? 'active' : '' }}"
       style="background:{{ $cat['color'] }}; color:{{ $cat['text'] }};">
        <i class="{{ $cat['icon'] }}"></i>
        {{ $cat['label'] }}
        <span style="background:rgba(0,0,0,.12); border-radius:10px; padding:0 6px; font-size:.68rem;">{{ $porCategoria[$key] }}</span>
    </a>
    @endif
    @endforeach
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.inventario.index') }}">
<div class="filter-bar">
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Categoría</label>
        <select name="categoria" onchange="this.form.submit()">
            <option value="">Todas</option>
            @foreach($categorias as $key => $cat)
            <option value="{{ $key }}" {{ request('categoria') == $key ? 'selected' : '' }}>{{ $cat['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Estado</label>
        <select name="estado" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($estados as $key => $est)
            <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $est['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div style="flex:1; min-width:200px;">
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Buscar</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, ubicación…" style="width:100%;">
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">
        <i class="bi bi-search"></i> Buscar
    </button>
    @if(request()->hasAny(['categoria', 'estado', 'q']))
    <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary btn-sm" style="height:36px;">
        <i class="bi bi-x-lg"></i> Limpiar
    </a>
    @endif
</div>
</form>

{{-- Tabla --}}
<div class="table-card">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Artículo</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Disponible / Total</th>
                <th>Ubicación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articulos as $i => $art)
            @php
                $catInfo = $art->categoria_info;
                $estInfo = $art->estado_info;
                $pct     = $art->cantidad_total > 0
                    ? round(($art->cantidad_disponible / $art->cantidad_total) * 100)
                    : 0;
                $barColor = $pct > 60 ? '#10b981' : ($pct > 30 ? '#f59e0b' : '#ef4444');
            @endphp
            <tr>
                <td style="color:#9ca3af; font-size:.78rem;">{{ $articulos->firstItem() + $i }}</td>
                <td>
                    <div style="font-weight:700; color:#111827;">{{ $art->nombre }}</div>
                    @if($art->descripcion)
                    <div style="font-size:.75rem; color:#6b7280; margin-top:2px;">{{ Str::limit($art->descripcion, 60) }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge-cat" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                        <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
                    </span>
                </td>
                <td>
                    <span class="badge-est" style="background:{{ $estInfo['color'] }}; color:{{ $estInfo['text'] }};">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $estInfo['dot'] }};display:inline-block;"></span>
                        {{ $estInfo['label'] }}
                    </span>
                </td>
                <td>
                    <div class="qty-bar-wrap">
                        <span style="font-weight:700; color:#111827; min-width:28px;">{{ $art->cantidad_disponible }}</span>
                        <div class="qty-bar">
                            <div class="qty-bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                        </div>
                        <span style="color:#9ca3af; font-size:.75rem;">/ {{ $art->cantidad_total }}</span>
                    </div>
                </td>
                <td style="color:#6b7280; font-size:.82rem;">
                    {{ $art->ubicacion ?? '—' }}
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('admin.inventario.movimientos', $art) }}"
                           class="btn btn-outline-primary btn-sm" style="font-size:.75rem;"
                           title="Movimientos">
                            <i class="bi bi-arrow-left-right"></i>
                        </a>
                        <a href="{{ route('admin.inventario.edit', $art) }}"
                           class="btn btn-outline-secondary btn-sm" style="font-size:.75rem;"
                           title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.inventario.destroy', $art) }}"
                              onsubmit="return confirm('¿Eliminar este artículo y todos sus movimientos?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:.75rem;" title="Eliminar">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4" style="color:#9ca3af;">
                    <i class="bi bi-archive" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                    No hay artículos registrados.
                    <a href="{{ route('admin.inventario.create') }}" class="d-block mt-2 text-primary">Agregar primer artículo</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($articulos->hasPages())
    <div style="padding:1rem 1.25rem; border-top:1px solid #f3f4f6;">
        {{ $articulos->links() }}
    </div>
    @endif
</div>
@endsection
