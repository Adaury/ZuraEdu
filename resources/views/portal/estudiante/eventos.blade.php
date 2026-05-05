@extends('layouts.portal')
@section('page-title', 'Eventos')
@section('portal-name', 'Portal Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'eventos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.eventos') }}" class="prt-nav-item active">
        <i class="bi bi-calendar-event-fill"></i>Eventos
    </a>
    <a href="{{ route('portal.estudiante.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@section('content')

{{-- Mensajes flash --}}
@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2" style="font-size:.85rem;border-radius:.6rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2" style="font-size:.85rem;border-radius:.6rem;">
    <i class="bi bi-x-circle-fill"></i>{{ session('error') }}
</div>
@endif
@if(session('info'))
<div class="alert alert-info d-flex align-items-center gap-2 mb-3 py-2" style="font-size:.85rem;border-radius:.6rem;">
    <i class="bi bi-info-circle-fill"></i>{{ session('info') }}
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-calendar-event-fill me-2" style="color:#8b5cf6;"></i>Eventos del Centro
    </h2>
    <span style="font-size:.78rem;color:var(--prt-muted);">{{ $eventos->count() }} evento(s) activo(s)</span>
</div>

@forelse($eventos as $evento)
@php
    $colorMap = [
        'blue'   => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
        'green'  => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'],
        'purple' => ['bg' => '#faf5ff', 'text' => '#7c3aed', 'border' => '#e9d5ff'],
        'yellow' => ['bg' => '#fefce8', 'text' => '#ca8a04', 'border' => '#fde68a'],
        'gray'   => ['bg' => '#f9fafb', 'text' => '#6b7280', 'border' => '#e5e7eb'],
    ];
    $color = $colorMap[$evento->tipo_color] ?? $colorMap['gray'];
    $pasado = $evento->fecha_inicio && $evento->fecha_inicio->isPast() && (is_null($evento->fecha_fin) || $evento->fecha_fin->isPast());
@endphp

<div class="prt-card" style="margin-bottom:.85rem;border-left:3px solid {{ $color['border'] }};">
    <div class="prt-card-body" style="padding:.9rem 1.1rem;">

        {{-- Cabecera: tipo badge + título --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;">
            <div>
                <span style="background:{{ $color['bg'] }};color:{{ $color['text'] }};border-radius:20px;padding:.18rem .65rem;font-size:.68rem;font-weight:700;letter-spacing:.02em;">
                    {{ $evento->tipo_label }}
                </span>
                <div style="font-size:.92rem;font-weight:700;color:#1e293b;margin-top:.3rem;">{{ $evento->nombre }}</div>
            </div>
            @if($evento->_inscrito)
            <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:.2rem .7rem;font-size:.7rem;font-weight:700;white-space:nowrap;">
                <i class="bi bi-check-circle-fill me-1"></i>Inscrito
            </span>
            @endif
        </div>

        {{-- Descripción --}}
        @if($evento->descripcion)
        <p style="font-size:.8rem;color:#374151;line-height:1.6;margin-bottom:.55rem;">{{ Str::limit($evento->descripcion, 180) }}</p>
        @endif

        {{-- Meta: fecha, lugar, cupo --}}
        <div style="display:flex;flex-wrap:wrap;gap:.6rem 1.1rem;font-size:.75rem;color:#6b7280;margin-bottom:.7rem;">
            <span><i class="bi bi-calendar3 me-1"></i>
                {{ $evento->fecha_inicio?->format('d/m/Y') }}
                @if($evento->fecha_fin && $evento->fecha_fin->ne($evento->fecha_inicio))
                    – {{ $evento->fecha_fin->format('d/m/Y') }}
                @endif
            </span>
            @if($evento->lugar)
            <span><i class="bi bi-geo-alt-fill me-1"></i>{{ $evento->lugar }}</span>
            @endif
            @if(! is_null($evento->cupo_maximo))
            <span>
                <i class="bi bi-people-fill me-1"></i>
                @if($evento->_lleno)
                    <span style="color:#dc2626;font-weight:600;">Sin cupo disponible</span>
                @else
                    {{ $evento->_cupos_disponibles }} cupo(s) disponible(s) de {{ $evento->cupo_maximo }}
                @endif
            </span>
            @else
            <span><i class="bi bi-people-fill me-1"></i>Cupo ilimitado</span>
            @endif
        </div>

        {{-- Acción --}}
        @if(! $pasado)
            @if($evento->_inscrito)
            <button disabled class="btn btn-sm" style="background:#d1fae5;color:#065f46;border:none;font-size:.78rem;cursor:default;">
                <i class="bi bi-check2-circle me-1"></i>Ya estás inscrito
            </button>
            @elseif($evento->_lleno)
            <button disabled class="btn btn-sm btn-secondary" style="font-size:.78rem;opacity:.65;cursor:not-allowed;">
                <i class="bi bi-x-circle me-1"></i>Sin cupo
            </button>
            @else
            <form method="POST" action="{{ route('portal.estudiante.eventos.inscribirse', $evento) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary" style="font-size:.78rem;"
                    onclick="return confirm('¿Confirmas tu inscripción en «{{ $evento->nombre }}»?')">
                    <i class="bi bi-calendar-plus me-1"></i>Inscribirme
                </button>
            </form>
            @endif
        @else
        <span style="font-size:.75rem;color:#9ca3af;font-style:italic;">
            <i class="bi bi-clock-history me-1"></i>Evento finalizado
        </span>
        @endif

    </div>
</div>
@empty
<div class="prt-card">
    <div class="prt-card-body" style="text-align:center;padding:2.5rem;color:var(--prt-muted);">
        <i class="bi bi-calendar-x" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
        No hay eventos activos en este momento.
    </div>
</div>
@endforelse

@endsection
