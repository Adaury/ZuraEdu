@extends('layouts.portal-estudiante')
@section('title', 'Mi Ruta de Transporte')

@section('activeKey', 'transporte')

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#0369a1,#38bdf8);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-bus-front-fill me-2"></i>Mi Ruta de Transporte</h4>
        <small class="text-white opacity-75">Información de la ruta asignada para el traslado escolar</small>
    </div>
</div>

@if(!$asignacion || !$ruta)
<div class="text-center py-5">
    <i class="bi bi-bus-front" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:1rem;"></i>
    <h6 class="text-muted">No tienes una ruta de transporte asignada</h6>
    <p class="text-muted small">Si necesitas el servicio de transporte escolar, comunícate con la administración.</p>
</div>
@else

{{-- Info de la ruta --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-signpost-2-fill" style="color:#1d4ed8;font-size:1.3rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;">Nombre de la Ruta</div>
                        <div class="fw-bold" style="font-size:1.05rem;color:#1e293b;">{{ $ruta->nombre }}</div>
                    </div>
                </div>
            </div>
            @if($ruta->conductor)
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-badge-fill" style="color:#065f46;font-size:1.3rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;">Conductor</div>
                        <div class="fw-bold" style="font-size:1rem;color:#1e293b;">{{ $ruta->conductor }}</div>
                        @if($ruta->telefono_conductor)
                        <a href="tel:{{ $ruta->telefono_conductor }}" style="font-size:.8rem;color:#0369a1;text-decoration:none;">
                            <i class="bi bi-telephone me-1"></i>{{ $ruta->telefono_conductor }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @if($ruta->placa)
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-card-text" style="color:#92400e;font-size:1.3rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;">Placa del Vehículo</div>
                        <div class="fw-bold" style="font-size:1rem;color:#1e293b;font-family:monospace;">{{ strtoupper($ruta->placa) }}</div>
                    </div>
                </div>
            </div>
            @endif
            @if($asignacion->parada)
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-geo-alt-fill" style="color:#7c3aed;font-size:1.3rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:600;">Mi Parada</div>
                        <div class="fw-bold" style="font-size:1rem;color:#1e293b;">{{ $asignacion->parada->nombre }}</div>
                        @if($asignacion->parada->hora_estimada)
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $asignacion->parada->hora_estimada }}</small>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
        @if($ruta->descripcion)
        <div class="mt-3 pt-3 border-top" style="font-size:.85rem;color:#6b7280;">
            <i class="bi bi-info-circle me-1"></i>{{ $ruta->descripcion }}
        </div>
        @endif
    </div>
</div>

{{-- Paradas de la ruta --}}
@if($ruta->paradas->isNotEmpty())
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-map me-2" style="color:#0369a1;"></i>
            Paradas de la Ruta
            <small class="text-muted fw-normal">({{ $ruta->paradas->count() }} paradas)</small>
        </h6>
    </div>
    <div class="card-body p-4">
        <div style="position:relative;">
            @foreach($ruta->paradas as $i => $parada)
            @php $esMiParada = $asignacion->parada_id === $parada->id; @endphp
            <div class="d-flex gap-3 mb-3">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:28px;">
                    <div style="width:28px;height:28px;border-radius:50%;background:{{ $esMiParada ? '#7c3aed' : '#e2e8f0' }};display:flex;align-items:center;justify-content:center;border:2px solid {{ $esMiParada ? '#7c3aed' : '#cbd5e1' }};z-index:1;">
                        @if($esMiParada)
                        <i class="bi bi-person-fill" style="color:#fff;font-size:.75rem;"></i>
                        @else
                        <span style="font-size:.68rem;font-weight:700;color:#94a3b8;">{{ $parada->orden }}</span>
                        @endif
                    </div>
                    @if(!$loop->last)
                    <div style="width:2px;flex:1;background:#e2e8f0;margin:2px 0;min-height:20px;"></div>
                    @endif
                </div>
                <div class="pb-1 {{ $esMiParada ? 'fw-semibold' : '' }}" style="font-size:.88rem;color:{{ $esMiParada ? '#1e293b' : '#64748b' }};">
                    <span>{{ $parada->nombre }}</span>
                    @if($esMiParada)
                    <span class="badge ms-2" style="background:#ede9fe;color:#7c3aed;font-size:.65rem;">Mi parada</span>
                    @endif
                    @if($parada->hora_estimada)
                    <small class="text-muted d-block"><i class="bi bi-clock me-1"></i>{{ $parada->hora_estimada }}</small>
                    @endif
                    @if($parada->referencia)
                    <small class="text-muted d-block"><i class="bi bi-geo me-1"></i>{{ $parada->referencia }}</small>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@endif

@endsection
