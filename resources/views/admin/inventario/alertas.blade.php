@extends('layouts.admin')
@section('page-title', 'Inventario — Alertas de Stock')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.35rem; font-weight:800; color:var(--primary); margin:0; }

.alert-section { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:1.5rem; }
.alert-section-header { padding:.9rem 1.25rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
.alert-section-title { font-size:.95rem; font-weight:800; margin:0; }

.art-row { padding:.8rem 1.25rem; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.art-row:last-child { border-bottom:none; }
.art-row:hover { background:#f9fafb; }

.qty-bar-wrap { display:flex; align-items:center; gap:.5rem; min-width:120px; }
.qty-bar { flex:1; height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; }
.qty-bar-fill { height:100%; border-radius:3px; }

.badge-cat { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.empty-state { padding:2.5rem; text-align:center; color:#9ca3af; }
.empty-state i { font-size:2rem; display:block; margin-bottom:.5rem; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-exclamation-triangle-fill me-2" style="color:#f59e0b;"></i>Alertas de Inventario</h1>
        <small style="color:#6b7280;">Artículos que requieren atención: sin stock, stock bajo o en mal estado</small>
    </div>
    <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al Inventario
    </a>
</div>

@php
    $totalAlertas = $sinStock->count() + $stockBajo->count() + $malEstado->count() + $reparacion->count();
@endphp

@if($totalAlertas === 0)
<div style="background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:4rem; text-align:center;">
    <div style="font-size:3rem; margin-bottom:1rem;">✅</div>
    <h5 style="font-weight:800; color:#065f46;">¡Todo en orden!</h5>
    <p style="color:#6b7280; margin-bottom:1.5rem;">No hay artículos con alertas de stock ni en mal estado.</p>
    <a href="{{ route('admin.inventario.index') }}" class="btn btn-primary btn-sm">Ver Inventario</a>
</div>
@else

{{-- Sin stock --}}
@if($sinStock->isNotEmpty())
<div class="alert-section" style="border-top:3px solid #dc2626;">
    <div class="alert-section-header">
        <h6 class="alert-section-title" style="color:#dc2626;">
            <i class="bi bi-x-circle-fill me-2"></i>Sin Stock
        </h6>
        <span class="badge bg-danger">{{ $sinStock->count() }}</span>
    </div>
    @foreach($sinStock as $art)
    @php $catInfo = $art->categoria_info; @endphp
    <div class="art-row">
        <div style="flex:1; min-width:160px;">
            <div style="font-weight:700; color:#111827;">{{ $art->nombre }}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap">
                <span class="badge-cat" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                    <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
                </span>
                @if($art->ubicacion)
                <span class="badge-cat" style="background:#f3f4f6; color:#374151;">
                    <i class="bi bi-geo-alt"></i>{{ $art->ubicacion }}
                </span>
                @endif
            </div>
        </div>
        <div class="text-center" style="min-width:80px;">
            <div style="font-size:1.4rem; font-weight:900; color:#dc2626;">0</div>
            <div style="font-size:.7rem; color:#9ca3af;">de {{ $art->cantidad_total }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventario.movimientos', $art) }}"
               class="btn btn-sm btn-outline-primary" title="Ver movimientos">
                <i class="bi bi-arrow-left-right"></i>
            </a>
            <a href="{{ route('admin.inventario.edit', $art) }}"
               class="btn btn-sm btn-outline-secondary" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Stock bajo --}}
@if($stockBajo->isNotEmpty())
<div class="alert-section" style="border-top:3px solid #f59e0b;">
    <div class="alert-section-header">
        <h6 class="alert-section-title" style="color:#92400e;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Stock Bajo (≤ 25% del total)
        </h6>
        <span class="badge" style="background:#fef3c7; color:#92400e;">{{ $stockBajo->count() }}</span>
    </div>
    @foreach($stockBajo as $art)
    @php
        $catInfo = $art->categoria_info;
        $pct = $art->cantidad_total > 0 ? round(($art->cantidad_disponible / $art->cantidad_total) * 100) : 0;
    @endphp
    <div class="art-row">
        <div style="flex:1; min-width:160px;">
            <div style="font-weight:700; color:#111827;">{{ $art->nombre }}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap">
                <span class="badge-cat" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                    <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
                </span>
                @if($art->ubicacion)
                <span class="badge-cat" style="background:#f3f4f6; color:#374151;">
                    <i class="bi bi-geo-alt"></i>{{ $art->ubicacion }}
                </span>
                @endif
            </div>
        </div>
        <div style="min-width:160px;">
            <div class="qty-bar-wrap">
                <span style="font-weight:700; color:#f59e0b; min-width:28px;">{{ $art->cantidad_disponible }}</span>
                <div class="qty-bar">
                    <div class="qty-bar-fill" style="width:{{ $pct }}%; background:#f59e0b;"></div>
                </div>
                <span style="color:#9ca3af; font-size:.75rem;">/ {{ $art->cantidad_total }} ({{ $pct }}%)</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventario.movimientos', $art) }}"
               class="btn btn-sm btn-outline-primary" title="Ver movimientos">
                <i class="bi bi-arrow-left-right"></i>
            </a>
            <a href="{{ route('admin.inventario.edit', $art) }}"
               class="btn btn-sm btn-outline-secondary" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Mal estado --}}
@if($malEstado->isNotEmpty())
<div class="alert-section" style="border-top:3px solid #6b7280;">
    <div class="alert-section-header">
        <h6 class="alert-section-title" style="color:#374151;">
            <i class="bi bi-tools me-2"></i>En Mal Estado
        </h6>
        <span class="badge bg-secondary">{{ $malEstado->count() }}</span>
    </div>
    @foreach($malEstado as $art)
    @php $catInfo = $art->categoria_info; @endphp
    <div class="art-row">
        <div style="flex:1; min-width:160px;">
            <div style="font-weight:700; color:#111827;">{{ $art->nombre }}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap">
                <span class="badge-cat" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                    <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
                </span>
                @if($art->descripcion)
                <span style="font-size:.75rem; color:#6b7280;">{{ Str::limit($art->descripcion, 60) }}</span>
                @endif
            </div>
        </div>
        <div class="text-center" style="min-width:80px;">
            <div style="font-size:1.1rem; font-weight:700; color:#374151;">{{ $art->cantidad_disponible }}</div>
            <div style="font-size:.7rem; color:#9ca3af;">disponibles</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventario.edit', $art) }}"
               class="btn btn-sm btn-outline-secondary" title="Editar estado">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- En reparación --}}
@if($reparacion->isNotEmpty())
<div class="alert-section" style="border-top:3px solid #8b5cf6;">
    <div class="alert-section-header">
        <h6 class="alert-section-title" style="color:#7c3aed;">
            <i class="bi bi-wrench-adjustable me-2"></i>En Reparación
        </h6>
        <span class="badge" style="background:#ede9fe; color:#7c3aed;">{{ $reparacion->count() }}</span>
    </div>
    @foreach($reparacion as $art)
    @php $catInfo = $art->categoria_info; @endphp
    <div class="art-row">
        <div style="flex:1; min-width:160px;">
            <div style="font-weight:700; color:#111827;">{{ $art->nombre }}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap">
                <span class="badge-cat" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
                    <i class="{{ $catInfo['icon'] }}"></i>{{ $catInfo['label'] }}
                </span>
                @if($art->ubicacion)
                <span class="badge-cat" style="background:#f3f4f6; color:#374151;">
                    <i class="bi bi-geo-alt"></i>{{ $art->ubicacion }}
                </span>
                @endif
            </div>
        </div>
        <div class="text-center" style="min-width:80px;">
            <div style="font-size:1.1rem; font-weight:700; color:#7c3aed;">{{ $art->cantidad_disponible }}</div>
            <div style="font-size:.7rem; color:#9ca3af;">disponibles</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventario.edit', $art) }}"
               class="btn btn-sm btn-outline-secondary" title="Editar estado">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif

@endif
@endsection
