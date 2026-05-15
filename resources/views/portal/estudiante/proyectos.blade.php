@extends('layouts.portal-estudiante')
@section('title', 'Proyectos Escolares')

@section('activeKey', 'proyectos')

@section('content')

@php
$areaColors = [
    'ciencias'    => ['bg'=>'#f0fdf4','text'=>'#15803d','border'=>'#86efac'],
    'matematica'  => ['bg'=>'#eff6ff','text'=>'#1d4ed8','border'=>'#93c5fd'],
    'humanidades' => ['bg'=>'#faf5ff','text'=>'#7e22ce','border'=>'#d8b4fe'],
    'tecnologia'  => ['bg'=>'#eef2ff','text'=>'#4338ca','border'=>'#a5b4fc'],
    'arte'        => ['bg'=>'#fdf2f8','text'=>'#be185d','border'=>'#f9a8d4'],
    'otro'        => ['bg'=>'#f9fafb','text'=>'#4b5563','border'=>'#d1d5db'],
];
$estadoColors = [
    'planificacion' => ['bg'=>'#fefce8','text'=>'#854d0e','badge'=>'#fde047'],
    'desarrollo'    => ['bg'=>'#eff6ff','text'=>'#1e40af','badge'=>'#93c5fd'],
    'finalizado'    => ['bg'=>'#f0fdf4','text'=>'#166534','badge'=>'#86efac'],
    'presentado'    => ['bg'=>'#eef2ff','text'=>'#3730a3','badge'=>'#a5b4fc'],
];
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-kanban-fill me-2" style="color:#6366f1;"></i>Proyectos Escolares
    </h2>
    @if($schoolYear)
    <span style="font-size:.75rem;color:var(--prt-muted);">{{ $schoolYear->nombre }}</span>
    @endif
</div>

{{-- ── MIS PROYECTOS ──────────────────────────────────────────────────────── --}}
<div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6366f1;margin-bottom:.65rem;">
    <i class="bi bi-person-fill me-1"></i>Mis Proyectos
    <span style="font-weight:400;text-transform:none;color:var(--prt-muted);">({{ $misProyectos->count() }})</span>
</div>

@forelse($misProyectos as $proyecto)
@php
    $ac = $areaColors[$proyecto->area] ?? $areaColors['otro'];
    $ec = $estadoColors[$proyecto->estado] ?? $estadoColors['planificacion'];
    $progreso = $proyecto->progreso;
    $totalFases = $proyecto->fases->count();
    $fasesOk    = $proyecto->fases->where('completada', true)->count();
@endphp

<div class="prt-card" style="margin-bottom:.85rem;border-left:3px solid {{ $ac['border'] }};">
    <div class="prt-card-body" style="padding:.9rem 1.1rem;">

        {{-- Cabecera --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.45rem;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.3rem;">
                    {{-- Badge área --}}
                    <span style="background:{{ $ac['bg'] }};color:{{ $ac['text'] }};border-radius:20px;padding:.15rem .6rem;font-size:.67rem;font-weight:700;">
                        {{ $proyecto->area_label }}
                    </span>
                    {{-- Badge estado --}}
                    <span style="background:{{ $ec['bg'] }};color:{{ $ec['text'] }};border-radius:20px;padding:.15rem .6rem;font-size:.67rem;font-weight:700;">
                        {{ $proyecto->estado_label }}
                    </span>
                    {{-- Rol del estudiante --}}
                    @if($proyecto->_rol === 'lider')
                    <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:.15rem .6rem;font-size:.67rem;font-weight:700;">
                        <i class="bi bi-star-fill me-1" style="font-size:.6rem;"></i>Líder
                    </span>
                    @else
                    <span style="background:#f3f4f6;color:#374151;border-radius:20px;padding:.15rem .6rem;font-size:.67rem;font-weight:600;">
                        Integrante
                    </span>
                    @endif
                </div>
                <div style="font-size:.92rem;font-weight:700;color:#1e293b;">{{ $proyecto->titulo }}</div>
            </div>
        </div>

        {{-- Descripción --}}
        @if($proyecto->descripcion)
        <p style="font-size:.8rem;color:#374151;line-height:1.6;margin-bottom:.55rem;">{{ Str::limit($proyecto->descripcion, 160) }}</p>
        @endif

        {{-- Meta --}}
        <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;font-size:.75rem;color:#6b7280;margin-bottom:.7rem;">
            @if($proyecto->tutor && $proyecto->tutor->name !== '—')
            <span><i class="bi bi-person-badge me-1"></i>{{ $proyecto->tutor->name }}</span>
            @endif
            @if($proyecto->fecha_inicio)
            <span><i class="bi bi-calendar3 me-1"></i>{{ $proyecto->fecha_inicio->format('d/m/Y') }}
                @if($proyecto->fecha_fin) – {{ $proyecto->fecha_fin->format('d/m/Y') }} @endif
            </span>
            @endif
            <span><i class="bi bi-people me-1"></i>{{ $proyecto->integrantes->count() }} integrante(s)</span>
        </div>

        {{-- Barra de progreso de fases --}}
        @if($totalFases > 0)
        <div style="margin-bottom:.2rem;">
            <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#6b7280;margin-bottom:.25rem;">
                <span><i class="bi bi-list-check me-1"></i>Fases: {{ $fasesOk }}/{{ $totalFases }} completadas</span>
                <span style="font-weight:700;color:{{ $progreso >= 100 ? '#16a34a' : '#3b82f6' }};">{{ $progreso }}%</span>
            </div>
            <div style="background:#e5e7eb;border-radius:99px;height:6px;overflow:hidden;">
                <div style="background:{{ $progreso >= 100 ? '#16a34a' : '#3b82f6' }};width:{{ $progreso }}%;height:100%;border-radius:99px;transition:width .4s;"></div>
            </div>
        </div>

        {{-- Lista de fases --}}
        <div x-data="{ open: false }" style="margin-top:.5rem;">
            <button @click="open=!open" style="background:none;border:none;padding:0;font-size:.72rem;color:#6366f1;cursor:pointer;font-weight:600;">
                <span x-text="open ? 'Ocultar fases ▲' : 'Ver fases ▼'"></span>
            </button>
            <div x-show="open" x-transition style="margin-top:.5rem;display:flex;flex-direction:column;gap:.3rem;">
                @foreach($proyecto->fases as $fase)
                <div style="display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:{{ $fase->completada ? '#16a34a' : '#374151' }};">
                    <i class="bi bi-{{ $fase->completada ? 'check-circle-fill' : 'circle' }}" style="flex-shrink:0;"></i>
                    <span>{{ $fase->titulo ?? $fase->nombre ?? 'Fase' }}</span>
                    @if($fase->fecha_limite)
                    <span style="color:#9ca3af;margin-left:auto;">{{ \Carbon\Carbon::parse($fase->fecha_limite)->format('d/m') }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @else
        <p style="font-size:.74rem;color:#9ca3af;font-style:italic;margin:0;">Sin fases registradas aún.</p>
        @endif

    </div>
</div>
@empty
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-body" style="text-align:center;padding:1.8rem;color:var(--prt-muted);">
        <i class="bi bi-kanban" style="font-size:2rem;display:block;margin-bottom:.6rem;opacity:.4;"></i>
        No estás asignado a ningún proyecto este año.
    </div>
</div>
@endforelse

{{-- ── OTROS PROYECTOS DEL AÑO ──────────────────────────────────────────── --}}
@if($todosProyectos->isNotEmpty())
<div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin:1.25rem 0 .65rem;">
    <i class="bi bi-grid me-1"></i>Otros Proyectos del Año
    <span style="font-weight:400;text-transform:none;">({{ $todosProyectos->count() }})</span>
</div>

@foreach($todosProyectos as $proyecto)
@php
    $ac = $areaColors[$proyecto->area] ?? $areaColors['otro'];
    $ec = $estadoColors[$proyecto->estado] ?? $estadoColors['planificacion'];
    $totalFases = $proyecto->fases->count();
    $fasesOk    = $proyecto->fases->where('completada', true)->count();
    $progreso   = $proyecto->progreso;
@endphp

<div class="prt-card" style="margin-bottom:.75rem;opacity:.88;border-left:3px solid {{ $ac['border'] }};">
    <div class="prt-card-body" style="padding:.8rem 1rem;">
        <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-bottom:.3rem;">
            <span style="background:{{ $ac['bg'] }};color:{{ $ac['text'] }};border-radius:20px;padding:.12rem .55rem;font-size:.65rem;font-weight:700;">
                {{ $proyecto->area_label }}
            </span>
            <span style="background:{{ $ec['bg'] }};color:{{ $ec['text'] }};border-radius:20px;padding:.12rem .55rem;font-size:.65rem;font-weight:700;">
                {{ $proyecto->estado_label }}
            </span>
        </div>
        <div style="font-size:.88rem;font-weight:700;color:#374151;">{{ $proyecto->titulo }}</div>
        @if($proyecto->descripcion)
        <p style="font-size:.77rem;color:#6b7280;margin:.3rem 0 .5rem;line-height:1.55;">{{ Str::limit($proyecto->descripcion, 120) }}</p>
        @endif
        <div style="display:flex;flex-wrap:wrap;gap:.4rem .9rem;font-size:.73rem;color:#9ca3af;">
            @if($proyecto->tutor && $proyecto->tutor->name !== '—')
            <span><i class="bi bi-person-badge me-1"></i>{{ $proyecto->tutor->name }}</span>
            @endif
            <span><i class="bi bi-people me-1"></i>{{ $proyecto->integrantes->count() }} integrante(s)</span>
            @if($totalFases > 0)
            <span><i class="bi bi-list-check me-1"></i>{{ $fasesOk }}/{{ $totalFases }} fases · {{ $progreso }}%</span>
            @endif
        </div>
    </div>
</div>
@endforeach
@endif

@endsection
