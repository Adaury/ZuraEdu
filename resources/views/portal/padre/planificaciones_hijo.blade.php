@extends('layouts.portal')
@section('page-title', 'Planificaciones — ' . ($estudiante->nombre_completo ?? ''))
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'planificaciones', 'estudiante' => $estudiante])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-person-fill"></i>Mi Hijo
    </a>
    <a href="{{ route('portal.padre.hijo.planificaciones', $estudiante) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
@endsection

@push('styles')
<style>
.plan-mat-hd {
    background: linear-gradient(90deg,rgba(124,58,237,.08) 0%,transparent 100%);
    border-bottom: 2px solid rgba(124,58,237,.12);
    padding: .65rem 1rem;
    display: flex; align-items: center; gap: .6rem;
    font-weight: 800; font-size: .88rem; color: var(--prt-text);
}
.plan-item {
    padding: .75rem 1rem;
    border-bottom: 1px solid var(--prt-border);
    display: flex; align-items: flex-start; gap: .75rem; flex-wrap: wrap;
}
.plan-item:last-child { border-bottom: none; }
.plan-badge { border-radius: 6px; padding: .15rem .5rem; font-size: .7rem; font-weight: 700; }
.badge-ra   { background: #dbeafe; color: #1d4ed8; }
.badge-act  { background: #dcfce7; color: #15803d; }
[data-theme="dark"] .badge-ra  { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .badge-act { background: #052e16; color: #4ade80; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-text" style="color:#7c3aed;"></i>
            Planificaciones — {{ $estudiante->nombre_completo }}
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $matricula?->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.padre.hijo.planificaciones.pdf', $estudiante) }}" target="_blank"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-pdf-fill"></i>PDF
        </a>
        <a href="{{ route('portal.padre.hijo.planificaciones.excel', $estudiante) }}"
           style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-excel-fill"></i>Excel
        </a>
    </div>
</div>

@if($planificaciones->isEmpty())
<div class="prt-card" style="text-align:center;padding:3rem 1rem;">
    <i class="bi bi-journal-x" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
    <div style="font-weight:700;color:#64748b;margin-bottom:.3rem;">Sin planificaciones disponibles</div>
    <div style="font-size:.8rem;color:#94a3b8;">Aún no hay planificaciones publicadas para la sección de tu representado.</div>
</div>
@else

@foreach($planificaciones as $asignacionId => $planes)
@php $primera = $planes->first(); $asignatura = $primera?->asignacion?->asignatura; $docente = $primera?->asignacion?->docente; @endphp
<div class="prt-card" style="margin-bottom:.85rem;">
    <div class="plan-mat-hd">
        <i class="bi bi-book-fill" style="color:#7c3aed;font-size:.95rem;"></i>
        <div style="flex:1;">
            <div>{{ $asignatura?->nombre ?? '—' }}</div>
            @if($docente)
            <div style="font-size:.7rem;font-weight:400;color:var(--prt-muted);">
                <i class="bi bi-person me-1"></i>{{ $docente->nombre_completo }}
            </div>
            @endif
        </div>
        <span style="background:#7c3aed;color:#fff;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
            {{ $planes->count() }} planif.
        </span>
    </div>

    @foreach($planes as $plan)
    <div class="plan-item">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem;flex-wrap:wrap;">
                @if($plan->tipo === 'ra')
                    <span class="plan-badge badge-ra"><i class="bi bi-bookmark-check me-1"></i>Por RA</span>
                @else
                    <span class="plan-badge badge-act"><i class="bi bi-activity me-1"></i>Por Actividad</span>
                @endif
            </div>
            <div style="font-weight:700;font-size:.88rem;color:var(--prt-text);">
                {{ $plan->modulo_nombre ?? $asignatura?->nombre }}
                @if($plan->mf_codigo)
                <span style="font-size:.73rem;font-family:monospace;font-weight:400;color:#64748b;">· {{ $plan->mf_codigo }}</span>
                @endif
            </div>
            <div style="font-size:.74rem;color:#64748b;margin-top:.2rem;">
                @if($plan->sesion)<i class="bi bi-people me-1"></i>{{ $plan->sesion }} @endif
                @if($plan->fecha_inicio && $plan->fecha_fin)
                &nbsp;·&nbsp;<i class="bi bi-calendar3 me-1"></i>{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}
                @endif
                @if($plan->horas)&nbsp;·&nbsp;{{ $plan->horas }}h@endif
            </div>
            @if($plan->tipo === 'ra' && $plan->raItems->isNotEmpty())
            <div style="margin-top:.45rem;display:flex;flex-direction:column;gap:.25rem;">
                @foreach($plan->raItems as $ra)
                <div style="font-size:.76rem;color:var(--prt-text);background:var(--prt-bg);border-left:3px solid #7c3aed;padding:.25rem .5rem;border-radius:0 5px 5px 0;">
                    @if($ra->ra_codigo)<strong style="color:#7c3aed;">{{ $ra->ra_codigo }}:</strong> @endif
                    {{ Str::limit($ra->ra_descripcion, 120) }}
                </div>
                @endforeach
            </div>
            @elseif($plan->tipo === 'actividad')
            @php $act = $plan->actividades->first(); @endphp
            @if($act?->objetivo)
            <div style="margin-top:.35rem;font-size:.76rem;color:var(--prt-text);background:var(--prt-bg);border-left:3px solid #15803d;padding:.25rem .5rem;border-radius:0 5px 5px 0;">
                <strong style="color:#15803d;">Objetivo:</strong> {{ Str::limit($act->objetivo, 120) }}
            </div>
            @endif
            @endif
        </div>
    </div>
    @endforeach
</div>
@endforeach

@endif

@endsection
