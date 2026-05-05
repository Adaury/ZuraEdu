@extends('layouts.portal')
@section('page-title', 'Mis Planificaciones')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'mis-planificaciones'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.mis-planificaciones') }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
@endsection

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-text" style="color:#7c3aed;"></i>
            Mis Planificaciones
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            @if($schoolYear) {{ $schoolYear->nombre }} · @endif
            {{ $asignaciones->count() }} asignación(es)
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.docente.mis-planificaciones.pdf') }}" target="_blank"
           style="background:#991b1b;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <a href="{{ route('portal.docente.mis-planificaciones.excel') }}"
           style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>
</div>

@if($asignaciones->isEmpty())
<div class="prt-card" style="text-align:center;padding:3rem 1rem;">
    <i class="bi bi-journal-x" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
    <div style="font-weight:700;color:#64748b;">Sin asignaciones activas</div>
</div>
@else

@foreach($asignaciones as $asig)
@php $planes = $planificaciones->get($asig->id, collect()); @endphp
<div class="prt-card" style="margin-bottom:.85rem;">
    <div class="prt-card-header" style="background:linear-gradient(90deg,rgba(124,58,237,.08) 0%,transparent 100%);border-bottom:2px solid rgba(124,58,237,.12);">
        <i class="bi bi-book-fill" style="color:#7c3aed;font-size:1rem;"></i>
        <div style="flex:1;">
            <div style="font-weight:800;font-size:.88rem;color:var(--prt-text);">{{ $asig->asignatura?->nombre ?? '—' }}</div>
            <div style="font-size:.72rem;color:#64748b;">{{ $asig->grupo?->nombre_completo ?? '—' }}</div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
            <span style="background:#7c3aed;color:#fff;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                {{ $planes->count() }} planif.
            </span>
            <a href="{{ route('portal.docente.planificacion.index', $asig) }}"
               style="background:#7c3aed;color:#fff;border-radius:7px;padding:.3rem .7rem;font-size:.72rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                <i class="bi bi-list-ul"></i>Ver
            </a>
            <a href="{{ route('portal.docente.planificacion.create-ra', $asig) }}"
               style="background:#f0fdf4;color:#15803d;border-radius:7px;padding:.3rem .7rem;font-size:.72rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                <i class="bi bi-plus-lg"></i>Nueva
            </a>
        </div>
    </div>

    @if($planes->isEmpty())
    <div style="padding:.85rem 1rem;font-size:.8rem;color:#94a3b8;text-align:center;">
        Sin planificaciones aún — <a href="{{ route('portal.docente.planificacion.create-ra', $asig) }}" style="color:#7c3aed;">Crear primera</a>
    </div>
    @else
    <div style="padding:.5rem 1rem;">
        @foreach($planes->take(3) as $plan)
        <div style="display:flex;align-items:center;gap:.6rem;padding:.45rem 0;border-bottom:1px solid var(--prt-border);">
            @if($plan->tipo === 'ra')
            <span style="background:#dbeafe;color:#1d4ed8;border-radius:6px;padding:.12rem .45rem;font-size:.68rem;font-weight:700;flex-shrink:0;">RA</span>
            @else
            <span style="background:#dcfce7;color:#15803d;border-radius:6px;padding:.12rem .45rem;font-size:.68rem;font-weight:700;flex-shrink:0;">Act.</span>
            @endif
            <div style="flex:1;min-width:0;">
                <div style="font-size:.8rem;font-weight:700;color:var(--prt-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $plan->modulo_nombre ?? $asig->asignatura?->nombre }}
                </div>
                @if($plan->fecha_inicio && $plan->fecha_fin)
                <div style="font-size:.68rem;color:#64748b;">{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}</div>
                @endif
            </div>
            @if($plan->publicado)
            <span style="background:#dcfce7;color:#15803d;border-radius:20px;padding:.1rem .45rem;font-size:.65rem;font-weight:700;flex-shrink:0;">Pub.</span>
            @else
            <span style="background:#fef9c3;color:#92400e;border-radius:20px;padding:.1rem .45rem;font-size:.65rem;font-weight:700;flex-shrink:0;">Draft</span>
            @endif
        </div>
        @endforeach
        @if($planes->count() > 3)
        <div style="padding:.45rem 0;font-size:.75rem;color:#64748b;text-align:center;">
            + {{ $planes->count() - 3 }} más —
            <a href="{{ route('portal.docente.planificacion.index', $asig) }}" style="color:#7c3aed;">ver todas</a>
        </div>
        @endif
    </div>
    @endif
</div>
@endforeach

@endif

@endsection
