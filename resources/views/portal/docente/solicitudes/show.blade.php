@extends('layouts.portal')
@section('page-title', 'Detalle de Solicitud')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'solicitudes'])
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.docente.solicitudes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h6 class="fw-bold mb-0 ms-1">Solicitud #{{ $solicitud->id }}</h6>
</div>

@php $cfg = $solicitud->estado_config; @endphp

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Encabezado --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body px-4 py-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $solicitud->asunto }}</h6>
                        <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.75rem;">
                            {{ $solicitud->tipo_label }}
                        </span>
                    </div>
                    <span class="badge rounded-pill px-3 py-2"
                          style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};border:1px solid {{ $cfg['color'] }}40;font-size:.78rem;">
                        {{ $cfg['label'] }}
                    </span>
                </div>

                @if($solicitud->fecha_inicio)
                <div class="mt-2" style="font-size:.82rem;color:#64748b;">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ $solicitud->fecha_inicio->format('d/m/Y') }}
                    @if($solicitud->fecha_fin && $solicitud->fecha_fin != $solicitud->fecha_inicio)
                    — {{ $solicitud->fecha_fin->format('d/m/Y') }}
                    @endif
                </div>
                @endif
                <div class="mt-1" style="font-size:.78rem;color:#94a3b8;">
                    Enviada {{ $solicitud->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 px-4">
                <small class="fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Descripción</small>
            </div>
            <div class="card-body px-4 py-3">
                <div style="white-space:pre-wrap;font-size:.88rem;line-height:1.7;color:#374151;">{{ $solicitud->descripcion }}</div>
            </div>
        </div>

        @if($solicitud->adjunto)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body px-4 py-2">
                <a href="{{ asset('storage/' . $solicitud->adjunto) }}" target="_blank"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-paperclip me-1"></i>Ver documento adjunto
                </a>
            </div>
        </div>
        @endif

        {{-- Respuesta --}}
        @if($solicitud->respuesta)
        <div class="card border-0 shadow-sm" style="border-left:4px solid {{ $cfg['color'] }} !important;">
            <div class="card-header py-2 px-4" style="background:{{ $cfg['bg'] }};border-bottom:1px solid {{ $cfg['color'] }}20;">
                <small class="fw-semibold" style="color:{{ $cfg['color'] }};font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;">
                    <i class="bi bi-reply-fill me-1"></i>Respuesta de la Institución
                </small>
            </div>
            <div class="card-body px-4 py-3">
                <div style="white-space:pre-wrap;font-size:.88rem;line-height:1.7;color:#374151;">{{ $solicitud->respuesta }}</div>
                @if($solicitud->respondidoPor)
                <div class="mt-2 text-muted" style="font-size:.75rem;">
                    — {{ $solicitud->respondidoPor->name }}
                    · {{ $solicitud->respondido_en?->format('d/m/Y H:i') }}
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="alert alert-light border py-2 px-3 mb-0" style="font-size:.82rem;">
            <i class="bi bi-hourglass-split me-1 text-warning"></i>
            Tu solicitud está siendo revisada. Recibirás una notificación cuando haya respuesta.
        </div>
        @endif
    </div>
</div>
@endsection
