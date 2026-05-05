@extends('layouts.portal')
@section('page-title', 'Mis Notificaciones')
@section('portal-name', 'Notificaciones')

@section('sidebar')
    <div class="prt-sidebar-section">Navegación</div>
    @php $dash = auth()->user()->hasRole('Docente') ? 'portal.docente.dashboard' : (auth()->user()->hasRole('Representante') ? 'portal.padre.dashboard' : 'portal.estudiante.dashboard'); @endphp
    <a href="{{ route($dash) }}" class="prt-sidebar-link"><i class="bi bi-house-fill"></i>Inicio</a>
    <a href="#" class="prt-sidebar-link active"><i class="bi bi-bell-fill"></i>Notificaciones</a>
@endsection

@section('bottom-nav')
    <a href="{{ route($dash ?? 'portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="#" class="prt-nav-item active">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@push('styles')
<style>
.notif-item { display:flex; align-items:flex-start; gap:.85rem; padding:.85rem 1.1rem; border-bottom:1px solid var(--prt-border); }
.notif-item:last-child { border-bottom:none; }
.notif-item.unread { background:rgba(37,99,235,.04); }
.notif-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.notif-titulo { font-size:.85rem; font-weight:700; color:var(--prt-text); }
.notif-mensaje { font-size:.78rem; color:var(--prt-muted); margin-top:.15rem; }
.notif-fecha   { font-size:.7rem; color:var(--prt-muted); margin-top:.25rem; }
.tipo-chip { display:inline-block; border-radius:4px; padding:.1rem .45rem; font-size:.66rem; font-weight:700; }
</style>
@endpush

@section('content')
@php
    $tipoColor = [
        'comunicado'   => '#2563eb', 'planificacion' => '#7c3aed',
        'recursos'     => '#0891b2', 'pago'          => '#dc2626',
        'suplencia'    => '#d97706', 'ausencia'      => '#f59e0b',
        'asistencia'   => '#16a34a', 'observacion'   => '#ec4899',
        'boletin'      => '#0f766e', 'general'       => '#6b7280',
    ];
    $tipoIcon = [
        'comunicado'   => 'bi-megaphone-fill',     'planificacion' => 'bi-journal-text',
        'recursos'     => 'bi-folder2-open',        'pago'          => 'bi-cash-coin',
        'suplencia'    => 'bi-person-fill-exclamation', 'ausencia'  => 'bi-calendar-x-fill',
        'asistencia'   => 'bi-calendar-check-fill', 'observacion'  => 'bi-chat-quote-fill',
        'boletin'      => 'bi-file-earmark-text',   'general'      => 'bi-bell-fill',
    ];
    $noLeidas = $notificaciones->filter(fn($n) => !$n->leida)->count();
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-bell-fill me-2" style="color:var(--primary);"></i>Todas las Notificaciones
        @if($noLeidas > 0)
        <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.62rem;padding:.15rem .5rem;font-weight:700;vertical-align:middle;">{{ $noLeidas }} sin leer</span>
        @endif
    </h2>
    <div style="display:flex;align-items:center;gap:.5rem;">
        <span style="font-size:.78rem;color:var(--prt-muted);">{{ $notificaciones->total() }} en total</span>
        @if($noLeidas > 0)
        <form method="POST" action="{{ auth()->user()->hasRole('Docente') ? route('portal.docente.notif.leer-todas') : (auth()->user()->hasRole('Representante') ? route('portal.padre.notif.leer-todas') : route('portal.estudiante.notif.leer-todas')) }}">
            @csrf
            <button type="submit" style="background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.3rem .8rem;font-size:.75rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-check2-all me-1"></i>Marcar todas leídas
            </button>
        </form>
        @endif
    </div>
</div>

<div class="prt-card">
    @forelse($notificaciones as $notif)
    @php
        $color = $tipoColor[$notif->tipo] ?? '#6b7280';
        $icon  = $tipoIcon[$notif->tipo]  ?? 'bi-bell-fill';
    @endphp
    <div class="notif-item {{ $notif->leida ? '' : 'unread' }}">
        <div class="notif-icon" style="background:{{ $color }}18;">
            <i class="bi {{ $icon }}" style="color:{{ $color }};"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="notif-titulo">{{ $notif->titulo }}</div>
            <div class="notif-mensaje">{{ $notif->mensaje }}</div>
            <div class="notif-fecha">
                {{ $notif->created_at->diffForHumans() }}
                &nbsp;·&nbsp;
                <span class="tipo-chip" style="background:{{ $color }}18;color:{{ $color }};">
                    {{ ucfirst($notif->tipo) }}
                </span>
                @if($notif->leida)
                    &nbsp;<i class="bi bi-check2-all" style="color:#10b981;font-size:.72rem;" title="Leída"></i>
                @else
                    &nbsp;<span style="width:7px;height:7px;background:#2563eb;border-radius:50%;display:inline-block;"></span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:2.5rem;color:var(--prt-muted);">
        <i class="bi bi-bell-slash" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
        Sin notificaciones aún.
    </div>
    @endforelse
</div>

<div class="mt-3 d-flex justify-content-center">
    {{ $notificaciones->links() }}
</div>
@endsection
