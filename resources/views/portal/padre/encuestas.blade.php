@extends('layouts.portal')
@section('page-title', 'Encuestas')
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'encuestas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.comunicados') }}" class="prt-nav-item">
        <i class="bi bi-megaphone-fill"></i>Noticias
    </a>
    <a href="{{ route('portal.padre.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="prt-alert prt-alert-success" style="margin-bottom:1rem;padding:.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#166534;font-size:.85rem;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-clipboard-check-fill me-2" style="color:#8b5cf6;"></i>Encuestas de Satisfacción
    </h2>
    <span style="font-size:.78rem;color:var(--prt-muted);">{{ $encuestas->count() }} disponible(s)</span>
</div>

@forelse($encuestas as $encuesta)
<div class="prt-card" style="margin-bottom:.85rem;border-left:4px solid {{ $encuesta->_ya_respondio ? '#22c55e' : '#8b5cf6' }};">
    <div class="prt-card-body" style="padding:.9rem 1.1rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:.9rem;font-weight:700;color:#1e293b;margin-bottom:.25rem;">
                    {{ $encuesta->titulo }}
                </div>
                @if($encuesta->descripcion)
                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:.4rem;">{{ $encuesta->descripcion }}</div>
                @endif
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;">
                    <span style="background:#f3e8ff;color:#7c3aed;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                        {{ $encuesta->preguntas_count }} pregunta(s)
                    </span>
                    @if($encuesta->fecha_cierre)
                        <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                            Cierra: {{ $encuesta->fecha_cierre->format('d/m/Y') }}
                        </span>
                    @endif
                    @if($encuesta->_ya_respondio)
                        <span style="background:#dcfce7;color:#166534;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                            <i class="bi bi-check-circle-fill me-1"></i>Ya respondida
                        </span>
                    @endif
                </div>
            </div>
            <div style="flex-shrink:0;">
                @if($encuesta->_ya_respondio)
                    <span style="display:inline-flex;align-items:center;gap:.35rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:8px;padding:.35rem .75rem;font-size:.78rem;font-weight:600;">
                        <i class="bi bi-check-circle-fill"></i>Respondida
                    </span>
                @else
                    <a href="{{ route('portal.padre.encuestas.responder', $encuesta) }}"
                       style="display:inline-flex;align-items:center;gap:.35rem;background:#8b5cf6;color:#fff;border-radius:8px;padding:.35rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;">
                        <i class="bi bi-pencil-fill"></i>Responder
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem 1rem;">
    <i class="bi bi-clipboard-x" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
    <p style="color:var(--prt-muted);font-size:.85rem;margin:0;">No hay encuestas disponibles en este momento.</p>
</div>
@endforelse

@endsection
