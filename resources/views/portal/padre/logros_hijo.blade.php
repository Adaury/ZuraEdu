@extends('layouts.portal')
@section('title', 'Logros — ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'logros', 'estudiante' => $estudiante])
@endsection

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-controller me-2"></i>Logros y Puntos</h4>
        <small class="text-white opacity-75">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</small>
    </div>
</div>

{{-- ── GAMIFICACIÓN ─────────────────────────────────────────────────── --}}
@if($gamificacionActiva && $matricula)

{{-- Stats --}}
@php $puntosHoy = $historial->where('fecha', today()->toDateString())->sum('puntos'); @endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;">
            <i class="bi bi-star-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ number_format($totalPuntos) }}</div>
            <small style="opacity:.85;">Puntos totales</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;">
            <i class="bi bi-trophy-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $miPosicion ? '#'.$miPosicion : 'N/A' }}</div>
            <small style="opacity:.85;">Posición grupo</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;">
            <i class="bi bi-award-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $insigniasObtenidas->count() }}</div>
            <small style="opacity:.85;">Insignias</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#10b981,#34d399);color:#fff;">
            <i class="bi bi-lightning-charge-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $puntosHoy }}</div>
            <small style="opacity:.85;">Puntos hoy</small>
        </div>
    </div>
</div>

{{-- Insignias --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-award-fill me-2" style="color:#f59e0b;"></i>Insignias</h6>
        <div class="row g-2">
            @foreach(\App\Models\InsigniaEstudiante::TIPOS as $tipo => $info)
            @php $obtenida = isset($insigniasObtenidas[$tipo]); @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="text-center p-3 rounded-3"
                     style="background:{{ $obtenida ? '#fef9c3' : '#f3f4f6' }};border:2px solid {{ $obtenida ? '#fde68a' : '#e5e7eb' }};opacity:{{ $obtenida ? '1' : '0.5' }};">
                    <div style="width:46px;height:46px;background:{{ $obtenida ? ($info['bg'] ?? '#fcd34d') : '#e5e7eb' }};border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                        <i class="bi {{ $info['icono'] ?? 'bi-star' }}" style="font-size:1.2rem;color:{{ $obtenida ? ($info['color'] ?? '#d97706') : '#9ca3af' }};"></i>
                    </div>
                    <div class="fw-semibold" style="font-size:.73rem;color:#374151;line-height:1.2;">{{ $info['label'] ?? $tipo }}</div>
                    @if($obtenida)
                    <small class="text-muted" style="font-size:.62rem;">{{ $insigniasObtenidas[$tipo]->fecha_obtencion?->format('d/m/Y') }}</small>
                    @else
                    <small class="text-muted" style="font-size:.62rem;">Bloqueada</small>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Puntos por categoría --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:#6366f1;"></i>Puntos por Categoría</h6>
                @php $maxCat = max(array_values($puntosCategoria) ?: [1]); @endphp
                @foreach(\App\Models\PuntoEstudiante::CATEGORIAS as $cat => $info)
                @php
                    $pts = $puntosCategoria[$cat] ?? 0;
                    $pct = $maxCat > 0 ? round($pts / $maxCat * 100) : 0;
                    $barColors = ['blue'=>'#3b82f6','green'=>'#10b981','purple'=>'#8b5cf6','orange'=>'#f59e0b'];
                    $barColor  = $barColors[$info['color'] ?? 'blue'] ?? '#6b7280';
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-semibold"><i class="bi {{ $info['icon'] ?? 'bi-star' }} me-1"></i>{{ $info['label'] }}</small>
                        <small class="fw-bold">{{ $pts }} pts</small>
                    </div>
                    <div class="progress" style="height:8px;border-radius:99px;">
                        <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $barColor }};border-radius:99px;" role="progressbar"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top 10 del grupo --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill me-2" style="color:#f59e0b;"></i>Top 10 del Grupo</h6>
                @if($ranking->isEmpty())
                <p class="text-muted text-center py-3 small">Sin datos de ranking aún.</p>
                @else
                @foreach($ranking as $pos => $item)
                @php
                    $esHijo = $item['es_hijo'];
                    $medalla = $pos === 0 ? '🥇' : ($pos === 1 ? '🥈' : ($pos === 2 ? '🥉' : ($pos+1).'.'));
                @endphp
                <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3 mb-1"
                     style="{{ $esHijo ? 'background:#eef2ff;border:1.5px solid #a5b4fc;' : '' }}">
                    <span style="width:26px;text-align:center;font-size:.9rem;flex-shrink:0;">{{ $medalla }}</span>
                    <span class="flex-grow-1 text-truncate small {{ $esHijo ? 'fw-bold' : '' }}" style="color:{{ $esHijo ? '#4338ca' : '#374151' }};">
                        {{ $item['nombre'] }}
                        @if($esHijo)<small class="opacity-75 ms-1">(tu hijo/a)</small>@endif
                    </span>
                    <span class="fw-bold small" style="color:{{ $esHijo ? '#4338ca' : '#6b7280' }};">{{ number_format($item['total']) }}<small class="fw-normal"> pts</small></span>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Historial de puntos --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2" style="color:#6366f1;"></i>Historial de Puntos <small class="text-muted fw-normal">(últimos 50)</small></h6>
    </div>
    @if($historial->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
        <small>Aún no tiene puntos registrados.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="px-4 py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Concepto</th>
                    <th class="py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Categoría</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Puntos</th>
                    <th class="py-3 text-muted fw-semibold text-end pe-4" style="font-size:.72rem;text-transform:uppercase;">Fecha</th>
                </tr>
            </thead>
            <tbody>
            @foreach($historial as $punto)
            @php
                $catInfo  = \App\Models\PuntoEstudiante::CATEGORIAS[$punto->categoria] ?? ['label'=>ucfirst($punto->categoria),'icon'=>'bi-star','color'=>'blue'];
                $bgBadge  = ['blue'=>'#dbeafe','green'=>'#d1fae5','purple'=>'#ede9fe','orange'=>'#fef3c7'][$catInfo['color']] ?? '#f3f4f6';
                $txtBadge = ['blue'=>'#1d4ed8','green'=>'#065f46','purple'=>'#5b21b6','orange'=>'#92400e'][$catInfo['color']] ?? '#374151';
            @endphp
            <tr>
                <td class="px-4 py-3 small">{{ $punto->concepto }}</td>
                <td class="py-3">
                    <span class="badge rounded-pill" style="background:{{ $bgBadge }};color:{{ $txtBadge }};font-size:.7rem;">
                        <i class="bi {{ $catInfo['icon'] }} me-1"></i>{{ $catInfo['label'] }}
                    </span>
                </td>
                <td class="py-3 text-center fw-bold" style="color:#6366f1;">+{{ $punto->puntos }}</td>
                <td class="py-3 text-end pe-4 text-muted small">{{ \Carbon\Carbon::parse($punto->fecha)->format('d/m/Y') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@elseif(!$gamificacionActiva)
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>El módulo de gamificación no está activo en este plan.
</div>
@endif

{{-- ── RECONOCIMIENTOS ──────────────────────────────────────────────── --}}
@if($reconocimientos->isNotEmpty())
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0"><i class="bi bi-award-fill me-2" style="color:#b45309;"></i>Reconocimientos</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($reconocimientos as $rec)
            <div class="col-md-6">
                <div class="d-flex gap-3 align-items-start p-3 rounded-3" style="background:#fefce8;border:1.5px solid #fde68a;">
                    <div style="width:44px;height:44px;border-radius:10px;background:#fef3c7;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-award-fill" style="color:#b45309;font-size:1.3rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.85rem;font-weight:800;color:#1e293b;">{{ $rec->titulo }}</div>
                        @if($rec->tipo)
                        <span style="background:#fef9c3;color:#92400e;border-radius:99px;padding:.1rem .5rem;font-size:.7rem;font-weight:700;">{{ $rec->tipo->nombre }}</span>
                        @endif
                        @if($rec->descripcion)
                        <p style="font-size:.78rem;color:#64748b;margin-top:.3rem;margin-bottom:.2rem;">{{ $rec->descripcion }}</p>
                        @endif
                        <span style="font-size:.72rem;color:#94a3b8;"><i class="bi bi-calendar3 me-1"></i>{{ $rec->fecha->format('d/m/Y') }}</span>
                    </div>
                    @if($rec->entregado)
                    <span style="background:#d1fae5;color:#065f46;border-radius:99px;padding:.1rem .5rem;font-size:.68rem;font-weight:700;white-space:nowrap;">
                        <i class="bi bi-check-circle-fill me-1"></i>Entregado
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body text-center py-4">
        <i class="bi bi-award" style="font-size:2.5rem;color:#d1d5db;"></i>
        <p class="text-muted mt-2 mb-0 small">Aún no hay reconocimientos registrados.</p>
    </div>
</div>
@endif

@endsection
