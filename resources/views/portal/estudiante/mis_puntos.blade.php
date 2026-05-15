@extends('layouts.portal-estudiante')
@section('title', 'Mis Puntos')

@section('activeKey', 'mis-puntos')

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#6366F1,#8B5CF6);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-controller me-2"></i>Mis Puntos</h4>
        <small class="text-white opacity-75">Tu progreso, insignias y posición en el ranking del grupo</small>
    </div>
</div>

@if(!$matricula)
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No tienes una matrícula activa en el año escolar actual.</div>
@else

{{-- Tarjetas resumen --}}
@php $puntosHoy = $historial->where('fecha', today()->toDateString())->sum('puntos'); @endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#6366F1,#818CF8);color:#fff;">
            <i class="bi bi-star-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ number_format($totalPuntos) }}</div>
            <small style="opacity:.85;">Puntos totales</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#F59E0B,#FBBF24);color:#fff;">
            <i class="bi bi-trophy-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $miPosicion ? '#'.$miPosicion : 'N/A' }}</div>
            <small style="opacity:.85;">Posición grupo</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#D97706,#F59E0B);color:#fff;">
            <i class="bi bi-award-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $insigniasObtenidas->count() }}</div>
            <small style="opacity:.85;">Insignias</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;background:linear-gradient(135deg,#10B981,#34D399);color:#fff;">
            <i class="bi bi-lightning-charge-fill" style="font-size:1.8rem;opacity:.9;"></i>
            <div class="fw-bold mt-1" style="font-size:1.6rem;">{{ $puntosHoy }}</div>
            <small style="opacity:.85;">Puntos hoy</small>
        </div>
    </div>
</div>

{{-- Insignias --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-award-fill me-2" style="color:#F59E0B;"></i>Mis Insignias</h6>
        <div class="row g-2">
            @foreach(\App\Models\InsigniaEstudiante::TIPOS as $tipo => $info)
            @php $obtenida = isset($insigniasObtenidas[$tipo]); @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="text-center p-3 rounded-3" style="background:{{ $obtenida ? '#FEF9C3' : '#F3F4F6' }};border:2px solid {{ $obtenida ? '#FDE68A' : '#E5E7EB' }};opacity:{{ $obtenida ? '1' : '0.5' }};">
                    <div style="width:46px;height:46px;background:{{ $obtenida ? ($info['bg'] ?? '#FCD34D') : '#E5E7EB' }};border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                        <i class="bi {{ $info['icono'] ?? 'bi-star' }}" style="font-size:1.2rem;color:{{ $obtenida ? ($info['color'] ?? '#D97706') : '#9CA3AF' }};"></i>
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
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:#6366F1;"></i>Puntos por Categoría</h6>
                @php $maxCat = max(array_values($puntosCategoria) ?: [1]); @endphp
                @foreach(\App\Models\PuntoEstudiante::CATEGORIAS as $cat => $info)
                @php
                    $pts = $puntosCategoria[$cat] ?? 0;
                    $pct = $maxCat > 0 ? round($pts/$maxCat*100) : 0;
                    $barColors = ['blue'=>'#3B82F6','green'=>'#10B981','purple'=>'#8B5CF6','orange'=>'#F59E0B'];
                    $barColor = $barColors[$info['color'] ?? 'blue'] ?? '#6B7280';
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

    {{-- Top 10 --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill me-2" style="color:#F59E0B;"></i>Top 10 del Grupo</h6>
                @if($ranking->isEmpty())
                <p class="text-muted text-center py-3 small">Sin datos de ranking aún.</p>
                @else
                @foreach($ranking as $pos => $item)
                @php
                    $esMio = $matricula && $item['matricula_id'] === $matricula->id;
                    $medalla = $pos === 0 ? '🥇' : ($pos === 1 ? '🥈' : ($pos === 2 ? '🥉' : ($pos+1).'.'));
                @endphp
                <div class="d-flex align-items-center gap-2 px-2 py-2 rounded-3 mb-1"
                     style="{{ $esMio ? 'background:#EEF2FF;border:1.5px solid #A5B4FC;' : '' }}">
                    <span style="width:26px;text-align:center;font-size:.9rem;flex-shrink:0;">{{ $medalla }}</span>
                    <span class="flex-grow-1 text-truncate small {{ $esMio ? 'fw-bold' : '' }}" style="color:{{ $esMio ? '#4338CA' : '#374151' }};">
                        {{ $item['nombre'] }}
                        @if($esMio)<small class="opacity-75 ms-1">(tú)</small>@endif
                    </span>
                    <span class="fw-bold small" style="color:{{ $esMio ? '#4338CA' : '#6B7280' }};">{{ number_format($item['total']) }}<small class="fw-normal"> pts</small></span>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Historial --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2" style="color:#6366F1;"></i>Historial de Puntos <small class="text-muted fw-normal">(últimos 50)</small></h6>
    </div>
    @if($historial->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
        <small>Aún no tienes puntos registrados.</small><br>
        <small class="text-muted">Los puntos se generan automáticamente según tu desempeño académico.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#F8FAFC;">
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
                $catInfo = \App\Models\PuntoEstudiante::CATEGORIAS[$punto->categoria] ?? ['label'=>ucfirst($punto->categoria),'icon'=>'bi-star','color'=>'blue'];
                $bgBadge = ['blue'=>'#DBEAFE','green'=>'#D1FAE5','purple'=>'#EDE9FE','orange'=>'#FEF3C7'][$catInfo['color']] ?? '#F3F4F6';
                $textBadge = ['blue'=>'#1D4ED8','green'=>'#065F46','purple'=>'#5B21B6','orange'=>'#92400E'][$catInfo['color']] ?? '#374151';
            @endphp
            <tr>
                <td class="px-4 py-3 small">{{ $punto->concepto }}</td>
                <td class="py-3">
                    <span class="badge rounded-pill" style="background:{{ $bgBadge }};color:{{ $textBadge }};font-size:.7rem;">
                        <i class="bi {{ $catInfo['icon'] }} me-1"></i>{{ $catInfo['label'] }}
                    </span>
                </td>
                <td class="py-3 text-center fw-bold" style="color:#6366F1;">+{{ $punto->puntos }}</td>
                <td class="py-3 text-end pe-4 text-muted small">{{ \Carbon\Carbon::parse($punto->fecha)->format('d/m/Y') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endif
@endsection
