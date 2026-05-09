@extends('layouts.portal-estudiante')
@section('title', 'Detalle de Solicitud')
@section('activeKey', 'solicitudes')

@section('content')
@php $ec = $estados[$solicitud->estado] ?? $estados['pendiente']; @endphp

<div class="prt-page-header">
    <div>
        <h1 class="prt-page-title"><i class="bi bi-file-earmark-text me-2"></i>Detalle de Solicitud</h1>
        <p class="prt-page-sub">{{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
    </div>
    <a href="{{ route('portal.estudiante.solicitudes.index') }}" class="prt-btn prt-btn-outline">
        <i class="bi bi-arrow-left"></i> Mis Solicitudes
    </a>
</div>

<div style="display:grid;gap:1.25rem;">

    {{-- Estado banner --}}
    <div style="background:{{ $ec['bg'] }};border:1.5px solid {{ $ec['color'] }}30;border-radius:14px;padding:1rem 1.5rem;display:flex;align-items:center;gap:.75rem;">
        @if($solicitud->estado === 'pendiente')
            <i class="bi bi-clock-fill" style="color:{{ $ec['color'] }};font-size:1.3rem;"></i>
            <div><strong style="color:{{ $ec['color'] }};">Solicitud en espera de revisión.</strong><div style="font-size:.82rem;color:#78350f;margin-top:.15rem;">El personal administrativo la procesará pronto.</div></div>
        @elseif($solicitud->estado === 'en_proceso')
            <i class="bi bi-arrow-repeat" style="color:{{ $ec['color'] }};font-size:1.3rem;"></i>
            <div><strong style="color:{{ $ec['color'] }};">Tu solicitud está siendo procesada.</strong><div style="font-size:.82rem;color:#1e40af;margin-top:.15rem;">Recibirás una notificación con la respuesta.</div></div>
        @elseif($solicitud->estado === 'aprobada')
            <i class="bi bi-check-circle-fill" style="color:{{ $ec['color'] }};font-size:1.3rem;"></i>
            <div><strong style="color:{{ $ec['color'] }};">Solicitud aprobada.</strong><div style="font-size:.82rem;color:#166534;margin-top:.15rem;">Revisa la respuesta del centro a continuación.</div></div>
        @else
            <i class="bi bi-x-circle-fill" style="color:{{ $ec['color'] }};font-size:1.3rem;"></i>
            <div><strong style="color:{{ $ec['color'] }};">Solicitud no aprobada.</strong><div style="font-size:.82rem;color:#991b1b;margin-top:.15rem;">Revisa el motivo en la respuesta del centro.</div></div>
        @endif
        <span style="margin-left:auto;padding:.3rem .85rem;border-radius:99px;font-size:.78rem;font-weight:700;background:{{ $ec['color'] }};color:#fff;">
            {{ $ec['label'] }}
        </span>
    </div>

    {{-- Datos de la solicitud --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.07);overflow:hidden;">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem;">Tipo</div>
            <div style="font-size:.95rem;font-weight:700;color:#0f172a;">{{ $tipos[$solicitud->tipo] ?? $solicitud->tipo }}</div>
        </div>
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem;">Asunto</div>
            <div style="font-size:.95rem;font-weight:600;color:#0f172a;">{{ $solicitud->asunto }}</div>
        </div>
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.4rem;">Descripción</div>
            <div style="font-size:.88rem;color:#374151;line-height:1.6;white-space:pre-line;">{{ $solicitud->descripcion }}</div>
        </div>
        @if($solicitud->fecha_evento)
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.2rem;">Fecha del evento</div>
            <div style="font-size:.88rem;color:#374151;font-weight:500;">{{ $solicitud->fecha_evento->format('d/m/Y') }}</div>
        </div>
        @endif
        @if($solicitud->adjunto)
        <div style="padding:1.1rem 1.5rem;">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.4rem;">Adjunto</div>
            <a href="{{ asset('storage/' . $solicitud->adjunto) }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;border-radius:9px;background:#f1f5f9;border:1.5px solid #e2e8f0;color:#374151;font-size:.82rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-paperclip"></i> Ver adjunto
            </a>
        </div>
        @endif
    </div>

    {{-- Respuesta del centro --}}
    @if($solicitud->respuesta || in_array($solicitud->estado, ['aprobada','rechazada','en_proceso']))
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.07);overflow:hidden;">
        <div style="padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
            <div style="font-size:.78rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-chat-text-fill" style="color:#6366f1;"></i> Respuesta del centro
                @if($solicitud->respondidoPor)
                <span style="font-size:.7rem;font-weight:500;color:#94a3b8;margin-left:.25rem;">
                    por {{ $solicitud->respondidoPor->name }} · {{ $solicitud->respondido_en?->format('d/m/Y H:i') }}
                </span>
                @endif
            </div>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            @if($solicitud->respuesta)
            <p style="font-size:.88rem;color:#374151;line-height:1.6;white-space:pre-line;margin:0;">{{ $solicitud->respuesta }}</p>
            @else
            <p style="font-size:.85rem;color:#94a3b8;margin:0;font-style:italic;">Sin respuesta escrita aún.</p>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
