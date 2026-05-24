@extends('layouts.portal')
@section('title', 'Mi Carnet+')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'mi-carnet'])
@endsection

@section('content')
@php
    $TIPO_ICON = [
        'entrada'     => 'bi-box-arrow-in-right',
        'salida'      => 'bi-box-arrow-right',
        'biblioteca'  => 'bi-book',
        'comedor'     => 'bi-cup-hot',
        'laboratorio' => 'bi-flask',
        'evento'      => 'bi-calendar-event',
        'prestamo'    => 'bi-journal-bookmark',
    ];
    $ESTADO_COLOR = [
        'presente'   => ['bg' => '#dcfce7', 'text' => '#15803d', 'label' => 'Presente'],
        'tardanza'   => ['bg' => '#fef9c3', 'text' => '#b45309', 'label' => 'Tardanza'],
        'no_autorizado' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'label' => 'No autorizado'],
    ];
@endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <div style="width:38px;height:38px;background:#0ea5e918;border-radius:11px;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-credit-card-2-front-fill" style="color:#0ea5e9;font-size:1.1rem;"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">Mi Carnet+</h5>
        <small class="text-muted">Identidad digital y control de acceso</small>
    </div>
</div>

@if(!$carnet)
{{-- Sin carnet generado --}}
<div class="text-center py-5">
    <div style="width:80px;height:80px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <i class="bi bi-credit-card-2-front" style="font-size:2.5rem;color:#94a3b8;"></i>
    </div>
    <h6 class="fw-bold text-muted">Carnet no generado</h6>
    <p class="text-muted small">Tu carnet digital aún no ha sido emitido. Contacta con la administración.</p>
</div>
@else

<div class="row g-4">

    {{-- ── Tarjeta del carnet ──────────────────────────────────────────────── --}}
    <div class="col-lg-5">

        {{-- Carnet visual --}}
        <div class="card border-0 shadow" style="border-radius:20px;overflow:hidden;background:linear-gradient(135deg,#0ea5e9,#6366f1);">
            <div class="card-body p-4">
                {{-- Header de la tarjeta --}}
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-white opacity-75" style="font-size:.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Carnet Estudiantil</div>
                        @if($schoolYear)
                        <div class="text-white opacity-60" style="font-size:.7rem;">{{ $schoolYear->nombre ?? $schoolYear }}</div>
                        @endif
                    </div>
                    <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:.65rem;padding:.3rem .6rem;border-radius:99px;">
                        <i class="bi bi-circle-fill me-1" style="font-size:.4rem;{{ $carnet->estado === 'activo' ? 'color:#4ade80;' : 'color:#f87171;' }}"></i>
                        {{ ucfirst($carnet->estado) }}
                    </span>
                </div>

                {{-- Avatar + nombre --}}
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;color:#fff;flex-shrink:0;">
                        {{ strtoupper(substr($estudiante->user?->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-white fw-bold" style="font-size:1rem;line-height:1.2;">{{ $estudiante->user?->name }}</div>
                        @if($matricula)
                        <div class="text-white opacity-75" style="font-size:.75rem;">
                            {{ $matricula->grupo?->grado?->nombre }} {{ $matricula->grupo?->seccion?->nombre }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Número de carnet --}}
                <div class="text-white" style="font-family:monospace;font-size:1rem;font-weight:700;letter-spacing:2px;opacity:.9;">
                    {{ $carnet->numero_carnet }}
                </div>
                @if($carnet->vigencia_hasta)
                <div class="text-white opacity-60" style="font-size:.68rem;margin-top:.2rem;">
                    Válido hasta {{ $carnet->vigencia_hasta->format('m/Y') }}
                </div>
                @endif
            </div>
        </div>

        {{-- QR code --}}
        @if($carnet->estado === 'activo')
        <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
            <div class="card-body text-center p-4">
                <div class="text-muted small fw-semibold mb-3">
                    <i class="bi bi-qr-code me-1"></i>Código QR de acceso
                </div>
                <img
                    src="https://quickchart.io/qr?text={{ urlencode($qrUrl) }}&size=200&ecLevel=M&margin=2"
                    alt="QR Carnet"
                    style="width:180px;height:180px;border-radius:12px;"
                    onerror="this.parentElement.innerHTML='<p class=\'text-muted small\'>QR no disponible</p>'"
                >
                <div class="mt-3 text-muted" style="font-size:.72rem;">
                    <i class="bi bi-shield-lock me-1"></i>Muestra este código en la entrada
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-warning border-0 shadow-sm mt-3" style="border-radius:14px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Tu carnet está <strong>{{ $carnet->estado }}</strong>. Contacta con administración.
        </div>
        @endif
    </div>

    {{-- ── Panel derecho ──────────────────────────────────────────────────── --}}
    <div class="col-lg-7 d-flex flex-column gap-4">

        {{-- Risk Score --}}
        @if($risk)
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-{{ $risk['nivel']['color'] }}"></i>Estado de Asistencia (30 días)</h6>
                    <span class="badge bg-{{ $risk['nivel']['color'] }}" style="font-size:.75rem;">
                        <i class="bi {{ $risk['nivel']['icon'] }} me-1"></i>{{ $risk['nivel']['label'] }}
                    </span>
                </div>

                {{-- Barra de asistencia --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Porcentaje de asistencia</span>
                        <strong style="color:{{ $risk['porcentaje_asistencia'] >= 85 ? '#16a34a' : ($risk['porcentaje_asistencia'] >= 70 ? '#ca8a04' : '#dc2626') }};">
                            {{ $risk['porcentaje_asistencia'] }}%
                        </strong>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:{{ $risk['porcentaje_asistencia'] }}%;background:{{ $risk['porcentaje_asistencia'] >= 85 ? '#16a34a' : ($risk['porcentaje_asistencia'] >= 70 ? '#ca8a04' : '#dc2626') }};border-radius:99px;transition:width .6s;"></div>
                    </div>
                </div>

                {{-- KPIs --}}
                <div class="row g-2">
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:#dcfce7;">
                            <div class="fw-bold" style="font-size:1.4rem;color:#15803d;">{{ $risk['presencias'] }}</div>
                            <div class="small text-muted">Presencias</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:#fef9c3;">
                            <div class="fw-bold" style="font-size:1.4rem;color:#b45309;">{{ $risk['tardanzas'] }}</div>
                            <div class="small text-muted">Tardanzas</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:#fee2e2;">
                            <div class="fw-bold" style="font-size:1.4rem;color:#b91c1c;">{{ $risk['ausencias'] }}</div>
                            <div class="small text-muted">Ausencias</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Historial de accesos --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-header border-0 p-4 pb-0 bg-transparent">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Historial de accesos</h6>
            </div>
            <div class="card-body p-3">
                @if($accesos->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox" style="font-size:2rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
                    <small>Sin accesos registrados</small>
                </div>
                @else
                <div style="max-height:340px;overflow-y:auto;" class="pe-1">
                @foreach($accesos as $acceso)
                @php
                    $ic  = $TIPO_ICON[$acceso->tipo_evento] ?? 'bi-arrow-left-right';
                    $est = $ESTADO_COLOR[$acceso->estado] ?? ['bg'=>'#f1f5f9','text'=>'#64748b','label'=>ucfirst($acceso->estado)];
                @endphp
                <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div style="width:36px;height:36px;background:{{ $est['bg'] }};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi {{ $ic }}" style="color:{{ $est['text'] }};font-size:.95rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:.85rem;">{{ ucfirst($acceso->tipo_evento) }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            {{ $acceso->created_at->format('d/m/Y H:i') }}
                            @if($acceso->zona_id) · {{ $acceso->zona?->nombre }} @endif
                        </div>
                    </div>
                    <span class="badge" style="background:{{ $est['bg'] }};color:{{ $est['text'] }};font-size:.7rem;">{{ $est['label'] }}</span>
                </div>
                @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endif

@endsection
