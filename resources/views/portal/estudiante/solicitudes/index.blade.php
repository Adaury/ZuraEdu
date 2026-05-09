@extends('layouts.portal-estudiante')
@section('title', 'Mis Solicitudes')
@section('activeKey', 'solicitudes')

@section('content')
<div class="prt-page-header">
    <div>
        <h1 class="prt-page-title"><i class="bi bi-send-fill me-2"></i>Mis Solicitudes</h1>
        <p class="prt-page-sub">Gestiones y peticiones enviadas al centro educativo</p>
    </div>
    <a href="{{ route('portal.estudiante.solicitudes.create') }}" class="prt-btn prt-btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva Solicitud
    </a>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
    @php
    $statItems = [
        ['label'=>'Pendientes',   'val'=>$stats['pendientes'], 'color'=>'#d97706','bg'=>'#fffbeb','icon'=>'bi-clock-fill'],
        ['label'=>'En Proceso',   'val'=>$stats['en_proceso'], 'color'=>'#2563eb','bg'=>'#eff6ff','icon'=>'bi-arrow-repeat'],
        ['label'=>'Total enviadas','val'=>$stats['total'],      'color'=>'#6366f1','bg'=>'#eef2ff','icon'=>'bi-send-fill'],
    ];
    @endphp
    @foreach($statItems as $s)
    <div style="background:#fff;border-radius:14px;padding:1.1rem 1.25rem;box-shadow:0 2px 12px rgba(15,23,42,.07);display:flex;align-items:center;gap:.9rem;">
        <div style="width:42px;height:42px;border-radius:11px;background:{{ $s['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi {{ $s['icon'] }}" style="color:{{ $s['color'] }};font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1;">{{ $s['val'] }}</div>
            <div style="font-size:.72rem;color:#64748b;font-weight:500;">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

@if(session('success'))
<div class="prt-alert prt-alert-success mb-3"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

{{-- Lista --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.07);overflow:hidden;">
    @forelse($solicitudes as $sol)
    @php $ec = $estados[$sol->estado] ?? $estados['pendiente']; @endphp
    <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.25rem;">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;background:#f1f5f9;color:#64748b;padding:.2rem .55rem;border-radius:99px;">
                    {{ $tipos[$sol->tipo] ?? $sol->tipo }}
                </span>
                <span style="font-size:.7rem;font-weight:700;padding:.2rem .65rem;border-radius:99px;background:{{ $ec['bg'] }};color:{{ $ec['color'] }};">
                    {{ $ec['label'] }}
                </span>
            </div>
            <div style="font-size:.95rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ $sol->asunto }}
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">
                {{ $sol->created_at->format('d/m/Y H:i') }}
                @if($sol->fecha_evento) · Fecha evento: {{ $sol->fecha_evento->format('d/m/Y') }} @endif
            </div>
        </div>
        <a href="{{ route('portal.estudiante.solicitudes.show', $sol) }}"
           style="padding:.45rem .9rem;border-radius:9px;background:#f8fafc;border:1.5px solid #e2e8f0;color:#374151;font-size:.8rem;font-weight:600;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:.35rem;">
            <i class="bi bi-eye"></i> Ver detalle
        </a>
    </div>
    @empty
    <div style="text-align:center;padding:3rem 1rem;">
        <i class="bi bi-send" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
        <p style="color:#64748b;font-size:.9rem;">No has enviado ninguna solicitud aún.</p>
        <a href="{{ route('portal.estudiante.solicitudes.create') }}"
           style="display:inline-flex;align-items:center;gap:.4rem;margin-top:.75rem;padding:.5rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;font-size:.85rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-plus-circle"></i> Enviar primera solicitud
        </a>
    </div>
    @endforelse
</div>

{{ $solicitudes->links() }}
@endsection
