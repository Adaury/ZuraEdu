@extends('layouts.portal')
@section('title', 'Proyectos — ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'proyectos', 'estudiante' => $estudiante])
@endsection

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-kanban-fill me-2"></i>Proyectos Escolares</h4>
        <small class="text-white opacity-75">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</small>
    </div>
</div>

@if($proyectos->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5">
        <i class="bi bi-kanban" style="font-size:3rem;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-0">Tu hijo/a no está inscrito/a en ningún proyecto actualmente.</p>
    </div>
</div>
@else
@foreach($proyectos as $proyecto)
@php
    $estados = \App\Models\ProyectoEscolar::ESTADOS;
    $areas   = \App\Models\ProyectoEscolar::AREAS;
    $estadoLabel = $estados[$proyecto->estado] ?? ucfirst($proyecto->estado);
    $areaLabel   = $areas[$proyecto->area]     ?? ucfirst($proyecto->area);
    $totalFases     = $proyecto->fases->count();
    $fasesCompletadas = $proyecto->fases->where('completada', true)->count();
    $pct = $totalFases > 0 ? round(($fasesCompletadas / $totalFases) * 100) : 0;
    $estadoColor = match($proyecto->estado) {
        'finalizado', 'presentado' => ['bg' => '#d1fae5', 'color' => '#065f46'],
        'desarrollo'               => ['bg' => '#dbeafe', 'color' => '#1e40af'],
        default                    => ['bg' => '#f3f4f6', 'color' => '#374151'],
    };
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;overflow:hidden;">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-2">
            <div>
                <div style="font-size:.92rem;font-weight:800;color:#1e293b;">{{ $proyecto->titulo }}</div>
                <div class="d-flex gap-2 mt-1 flex-wrap">
                    <span style="background:#e0f2fe;color:#0369a1;border-radius:99px;padding:.1rem .55rem;font-size:.7rem;font-weight:700;">{{ $areaLabel }}</span>
                    <span style="background:{{ $estadoColor['bg'] }};color:{{ $estadoColor['color'] }};border-radius:99px;padding:.1rem .55rem;font-size:.7rem;font-weight:700;">{{ $estadoLabel }}</span>
                </div>
            </div>
            <div style="font-size:.75rem;color:#94a3b8;text-align:right;white-space:nowrap;">
                @if($proyecto->fecha_inicio)
                    <i class="bi bi-calendar3 me-1"></i>{{ $proyecto->fecha_inicio->format('d/m/Y') }}
                    @if($proyecto->fecha_fin) – {{ $proyecto->fecha_fin->format('d/m/Y') }} @endif
                @endif
            </div>
        </div>
        @if($proyecto->descripcion)
            <p style="font-size:.8rem;color:#64748b;margin-bottom:.5rem;">{{ \Illuminate\Support\Str::limit($proyecto->descripcion, 120) }}</p>
        @endif
        @if($totalFases > 0)
        <div style="margin-top:.5rem;">
            <div class="d-flex justify-content-between" style="font-size:.72rem;color:#6b7280;margin-bottom:3px;">
                <span>Avance: {{ $fasesCompletadas }}/{{ $totalFases }} fases</span>
                <span style="font-weight:700;">{{ $pct }}%</span>
            </div>
            <div style="background:#e5e7eb;border-radius:99px;height:6px;overflow:hidden;">
                <div style="width:{{ $pct }}%;background:#0ea5e9;height:100%;border-radius:99px;"></div>
            </div>
        </div>
        @endif
        @if($proyecto->tutor)
        <div style="font-size:.73rem;color:#94a3b8;margin-top:.5rem;">
            <i class="bi bi-person-fill me-1"></i>Tutor: {{ $proyecto->tutor->name }}
        </div>
        @endif
    </div>
</div>
@endforeach
@endif

@endsection
