@extends('layouts.admin')
@section('page-title', 'Movimientos — ' . $articulo->nombre)

@push('styles')
<style>
.art-header { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.25rem 1.5rem; margin-bottom:1.25rem; display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
.art-badge  { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .8rem; border-radius:20px; font-size:.75rem; font-weight:700; }
.art-qty    { font-size:1.6rem; font-weight:800; color:#111827; line-height:1; }
.art-qty-label { font-size:.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

.qty-bar-wrap { display:flex; align-items:center; gap:.5rem; min-width:130px; }
.qty-bar { flex:1; height:8px; border-radius:4px; background:#e5e7eb; overflow:hidden; }
.qty-bar-fill { height:100%; border-radius:4px; }

.mov-card  { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.mov-card .thead th { background:#f8fafc; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.7rem 1rem; }
.mov-card .tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.mov-card .tbody tr:last-child td { border-bottom:none; }
.mov-card .tbody tr:hover { background:#f9fafb; }

.tipo-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }

.form-sidebar { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; position:sticky; top:80px; }
.form-sidebar h2 { font-size:1rem; font-weight:800; color:var(--primary); margin-bottom:1.1rem; }
.form-label-sm { font-size:.78rem; font-weight:700; color:#374151; margin-bottom:.25rem; }
.tipo-radio { display:flex; flex-direction:column; gap:.4rem; }
.tipo-radio label { display:flex; align-items:center; gap:.55rem; padding:.45rem .8rem; border-radius:9px; border:2px solid #e5e7eb; font-size:.83rem; font-weight:600; cursor:pointer; transition:all .15s; }
.tipo-radio label:has(input:checked) { border-color:var(--primary); background:#f0f4ff; }
.tipo-radio input[type=radio] { accent-color:var(--primary); width:15px; height:15px; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <nav aria-label="breadcrumb" class="mb-0">
        <ol class="breadcrumb mb-0" style="font-size:.83rem;">
            <li class="breadcrumb-item"><a href="{{ route('admin.inventario.index') }}">Inventario</a></li>
            <li class="breadcrumb-item active">{{ $articulo->nombre }}</li>
        </ol>
    </nav>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Cabecera artículo --}}
@php
    $catInfo = $articulo->categoria_info;
    $estInfo = $articulo->estado_info;
    $pct     = $articulo->cantidad_total > 0
        ? round(($articulo->cantidad_disponible / $articulo->cantidad_total) * 100)
        : 0;
    $barColor = $pct > 60 ? '#10b981' : ($pct > 30 ? '#f59e0b' : '#ef4444');
@endphp

<div class="art-header">
    <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;"
         style="background:{{ $catInfo['color'] }}">
        <i class="{{ $catInfo['icon'] }}" style="color:{{ $catInfo['text'] }};"></i>
    </div>
    <div style="flex:1; min-width:160px;">
        <div style="font-size:1.15rem; font-weight:800; color:#111827;">{{ $articulo->nombre }}</div>
        <div class="d-flex flex-wrap gap-2 mt-1">
            <span class="art-badge" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
            </span>
            <span class="art-badge" style="background:{{ $estInfo['color'] }}; color:{{ $estInfo['text'] }};">
                <span style="width:7px;height:7px;border-radius:50%;background:{{ $estInfo['dot'] }};display:inline-block;"></span>
                {{ $estInfo['label'] }}
            </span>
            @if($articulo->ubicacion)
            <span class="art-badge" style="background:#f3f4f6; color:#374151;">
                <i class="bi bi-geo-alt"></i>{{ $articulo->ubicacion }}
            </span>
            @endif
        </div>
    </div>
    <div class="text-center px-3">
        <div class="art-qty">{{ $articulo->cantidad_disponible }}</div>
        <div class="art-qty-label">Disponibles</div>
    </div>
    <div>
        <div class="qty-bar-wrap" style="min-width:160px;">
            <div class="qty-bar" style="flex:1;">
                <div class="qty-bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
            </div>
            <span style="font-size:.78rem; color:#6b7280; white-space:nowrap;">{{ $pct }}% ({{ $articulo->cantidad_total }} total)</span>
        </div>
    </div>
    <a href="{{ route('admin.inventario.edit', $articulo) }}" class="btn btn-outline-secondary btn-sm ms-auto">
        <i class="bi bi-pencil me-1"></i>Editar
    </a>
</div>

<div class="row g-3">

    {{-- Historial de movimientos --}}
    <div class="col-lg-8">
        <div class="mov-card">
            <div style="padding:.9rem 1.25rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
                <h2 style="font-size:1rem; font-weight:800; color:var(--primary); margin:0;">
                    <i class="bi bi-clock-history me-1"></i>Historial de Movimientos
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:.78rem; color:#9ca3af;">{{ $movimientos->total() }} registros</span>
                    <a href="{{ route('admin.inventario.movimientos.pdf', $articulo) }}"
                       class="btn btn-outline-danger btn-sm" style="font-size:.75rem;" title="Exportar PDF">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <a href="{{ route('admin.inventario.movimientos.excel', $articulo) }}"
                       class="btn btn-outline-success btn-sm" style="font-size:.75rem;" title="Exportar Excel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                </div>
            </div>
            <table class="table mb-0">
                <thead class="thead">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody class="tbody">
                    @forelse($movimientos as $mov)
                    @php $tipoInfo = $mov->tipo_info; @endphp
                    <tr>
                        <td style="white-space:nowrap; color:#6b7280; font-size:.8rem;">
                            {{ $mov->created_at->format('d/m/Y H:i') }}
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
                        <td style="color:#374151;">{{ $mov->motivo }}</td>
                        <td style="font-size:.8rem; color:#6b7280;">
                            {{ $mov->usuario?->name ?? 'Sistema' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4" style="color:#9ca3af;">
                            <i class="bi bi-inbox" style="font-size:1.8rem; display:block; margin-bottom:.4rem;"></i>
                            Sin movimientos registrados aún.
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
    </div>

    {{-- Formulario rápido --}}
    <div class="col-lg-4">
        <div class="form-sidebar" x-data="{ tipo: 'entrada' }">
            <h2><i class="bi bi-plus-slash-minus me-1"></i>Registrar Movimiento</h2>

            <form method="POST" action="{{ route('admin.inventario.movimientos.store', $articulo) }}">
                @csrf

                {{-- Tipo --}}
                <div class="mb-3">
                    <label class="form-label-sm d-block mb-2">Tipo <span class="text-danger">*</span></label>
                    <input type="hidden" name="tipo" :value="tipo">
                    <div class="tipo-radio">
                        @foreach($tipos as $key => $t)
                        <label @click="tipo = '{{ $key }}'">
                            <input type="radio" name="_tipo_display" value="{{ $key }}"
                                   {{ old('tipo', 'entrada') === $key ? 'checked' : '' }}>
                            <span style="width:9px;height:9px;border-radius:50%;background:{{ $t['text'] }};display:inline-block;flex-shrink:0;"></span>
                            <i class="{{ $t['icon'] }}" style="color:{{ $t['text'] }};"></i>
                            {{ $t['label'] }}
                            @if($key === 'entrada')
                            <span style="font-size:.72rem;color:#9ca3af;margin-left:auto;">Aumenta disponible</span>
                            @elseif($key === 'salida')
                            <span style="font-size:.72rem;color:#9ca3af;margin-left:auto;">Reduce disponible</span>
                            @else
                            <span style="font-size:.72rem;color:#9ca3af;margin-left:auto;">Valor absoluto</span>
                            @endif
                        </label>
                        @endforeach
                    </div>
                    @error('tipo')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                </div>

                {{-- Cantidad --}}
                <div class="mb-3">
                    <label class="form-label-sm">Cantidad <span class="text-danger">*</span></label>
                    <input type="number" name="cantidad"
                           class="form-control form-control-sm"
                           style="border-radius:9px;"
                           value="{{ old('cantidad', 1) }}"
                           min="1" required>
                    @error('cantidad')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                </div>

                {{-- Motivo --}}
                <div class="mb-3">
                    <label class="form-label-sm">Motivo <span class="text-danger">*</span></label>
                    <textarea name="motivo"
                              class="form-control form-control-sm"
                              style="border-radius:9px;"
                              rows="3"
                              placeholder="Ej: Compra nueva, préstamo aula 2, inventario anual…"
                              required>{{ old('motivo') }}</textarea>
                    @error('motivo')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                </div>

                {{-- Resumen disponibilidad actual --}}
                <div style="background:#f8fafc; border-radius:9px; padding:.65rem 1rem; margin-bottom:1rem; font-size:.8rem; color:#374151;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Disponible actual: <strong>{{ $articulo->cantidad_disponible }}</strong>
                    de <strong>{{ $articulo->cantidad_total }}</strong> totales.
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i>Registrar Movimiento
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
