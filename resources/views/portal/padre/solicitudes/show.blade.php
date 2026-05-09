@extends('layouts.portal')
@section('page-title', $solicitud->asunto)
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'solicitudes'])
@endsection

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="{{ route('portal.padre.solicitudes.index') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Mis Solicitudes
    </a>
</div>

@php $ec = $solicitud->estado_config; @endphp

{{-- Header --}}
<div class="prt-card" style="padding:1.25rem 1.4rem;margin-bottom:1rem;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <div style="font-size:1rem;font-weight:800;color:#1e293b;margin-bottom:.35rem;">{{ $solicitud->asunto }}</div>
            <div style="font-size:.78rem;color:#64748b;display:flex;gap:.75rem;flex-wrap:wrap;">
                <span><i class="bi bi-tag me-1"></i>{{ $solicitud->tipo_label }}</span>
                @if($solicitud->estudiante)
                <span><i class="bi bi-person me-1"></i>{{ $solicitud->estudiante->nombre_completo }}</span>
                @endif
                <span><i class="bi bi-calendar3 me-1"></i>{{ $solicitud->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        <span style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};border:1px solid {{ $ec['color'] }}44;border-radius:99px;font-size:.7rem;font-weight:700;padding:.3rem .85rem;white-space:nowrap;">
            {{ $ec['label'] }}
        </span>
    </div>
</div>

{{-- Descripción --}}
<div class="prt-card" style="padding:1.25rem 1.4rem;margin-bottom:1rem;">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.65rem;">
        <i class="bi bi-chat-square-text me-1"></i>Tu solicitud
    </div>
    @if($solicitud->fecha_evento)
    <div style="background:#fafafa;border-radius:8px;padding:.55rem .85rem;font-size:.8rem;color:#374151;margin-bottom:.8rem;display:inline-flex;align-items:center;gap:.5rem;">
        <i class="bi bi-calendar-event text-primary"></i>
        Fecha del evento: <strong>{{ $solicitud->fecha_evento->format('d/m/Y') }}</strong>
    </div>
    @endif
    <p style="font-size:.88rem;color:#374151;white-space:pre-wrap;margin:0;">{{ $solicitud->descripcion }}</p>

    @if($solicitud->adjunto)
    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
        <a href="{{ Storage::url($solicitud->adjunto) }}" target="_blank"
           style="font-size:.8rem;color:#1d4ed8;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;background:#eff6ff;border-radius:8px;padding:.4rem .85rem;">
            <i class="bi bi-paperclip"></i>Ver documento adjunto
        </a>
    </div>
    @endif
</div>

{{-- Respuesta del centro --}}
@if($solicitud->respuesta)
<div class="prt-card" style="padding:1.25rem 1.4rem;border-left:4px solid {{ $ec['color'] }};margin-bottom:1rem;">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.65rem;">
        <i class="bi bi-reply-fill me-1"></i>Respuesta del centro
    </div>
    <p style="font-size:.88rem;color:#374151;white-space:pre-wrap;margin-bottom:.65rem;">{{ $solicitud->respuesta }}</p>
    <div style="font-size:.74rem;color:#9ca3af;display:flex;gap:.75rem;flex-wrap:wrap;">
        @if($solicitud->respondidoPor)
        <span><i class="bi bi-person me-1"></i>{{ $solicitud->respondidoPor->name }}</span>
        @endif
        @if($solicitud->respondido_en)
        <span><i class="bi bi-clock me-1"></i>{{ $solicitud->respondido_en->format('d/m/Y H:i') }}</span>
        @endif
    </div>
</div>
@else
<div class="prt-card" style="padding:1.1rem 1.4rem;text-align:center;margin-bottom:1rem;">
    <i class="bi bi-hourglass-split" style="font-size:1.8rem;color:#d97706;display:block;margin-bottom:.5rem;"></i>
    <div style="font-size:.85rem;font-weight:600;color:#374151;">Esperando respuesta</div>
    <div style="font-size:.77rem;color:#9ca3af;margin-top:.2rem;">El equipo del centro revisará tu solicitud pronto.</div>
</div>
@endif

@endsection
