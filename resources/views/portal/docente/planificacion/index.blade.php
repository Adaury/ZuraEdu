@extends('layouts.portal')
@section('page-title', 'Planificaciones — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planificacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
.plan-card {
    border-radius:10px; border:1px solid #e2e8f0;
    padding:.85rem 1rem; margin-bottom:.75rem;
    background:#fff; transition:box-shadow .15s;
}
.plan-card:hover { box-shadow:0 2px 12px rgba(37,99,235,.10); }
.badge-ra       { background:#dbeafe; color:#1d4ed8; font-size:.72rem; }
.badge-actividad{ background:#dcfce7; color:#15803d; font-size:.72rem; }
[data-theme="dark"] .plan-card { background:#1e293b; border-color:#334155; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-text" style="color:#5b21b6;"></i>
            Planificaciones — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.docente.planificacion.create-ra', $asignacion) }}"
           style="background:#1d4ed8;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-circle"></i>Por RA
        </a>
        <a href="{{ route('portal.docente.planificacion.create-actividad', $asignacion) }}"
           style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-circle"></i>Por Actividad
        </a>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;color:#15803d;border-radius:8px;padding:.6rem 1rem;margin-bottom:.75rem;font-size:.82rem;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-journal-text" style="color:#5b21b6;font-size:1rem;"></i>
        <h3>Mis Planificaciones</h3>
    </div>

    @if($planificaciones->isEmpty())
    <div style="padding:2.5rem;text-align:center;color:#64748b;font-size:.85rem;">
        <i class="bi bi-journal-x" style="font-size:2.5rem;display:block;margin-bottom:.5rem;color:#94a3b8;"></i>
        <div>Aún no tienes planificaciones para este módulo.</div>
        <div style="margin-top:.85rem;display:flex;gap:.5rem;justify-content:center;">
            <a href="{{ route('portal.docente.planificacion.create-ra', $asignacion) }}"
               style="background:#1d4ed8;color:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;">
                Nueva por RA
            </a>
            <a href="{{ route('portal.docente.planificacion.create-actividad', $asignacion) }}"
               style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;">
                Nueva por Actividad
            </a>
        </div>
    </div>
    @else
    <div style="padding:.75rem;">
        @foreach($planificaciones as $plan)
        <div class="plan-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
                        @if($plan->tipo === 'ra')
                        <span class="badge-ra" style="border-radius:5px;padding:.15rem .45rem;font-weight:700;">
                            <i class="bi bi-bookmark-check me-1"></i>Por RA
                        </span>
                        @else
                        <span class="badge-actividad" style="border-radius:5px;padding:.15rem .45rem;font-weight:700;">
                            <i class="bi bi-activity me-1"></i>Por Actividad
                        </span>
                        @endif
                        @if($plan->publicado)
                        <span style="background:#dcfce7;color:#15803d;border-radius:5px;padding:.15rem .45rem;font-size:.7rem;font-weight:700;">
                            <i class="bi bi-eye me-1"></i>Publicado
                        </span>
                        @else
                        <span style="background:#f1f5f9;color:#64748b;border-radius:5px;padding:.15rem .45rem;font-size:.7rem;">
                            Borrador
                        </span>
                        @endif
                    </div>
                    <div style="font-weight:700;font-size:.88rem;color:#1e293b;" class="dm-text-primary">
                        {{ $plan->modulo_nombre ?? $asignacion->asignatura?->nombre }}
                        @if($plan->mf_codigo)
                        <span style="font-size:.75rem;font-family:monospace;font-weight:400;color:#64748b;">· {{ $plan->mf_codigo }}</span>
                        @endif
                    </div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
                        @if($plan->fecha_inicio && $plan->fecha_fin)
                        <i class="bi bi-calendar3 me-1"></i>{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}
                        @endif
                        @if($plan->tipo === 'ra')
                        · {{ $plan->raItems->count() }} RA(s)
                        @else
                        · Actividad #{{ $plan->actividades->first()?->actividad_numero ?? '?' }}
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                    <a href="{{ route('portal.docente.planificacion.show', [$asignacion, $plan]) }}"
                       style="background:#5b21b6;color:#fff;border-radius:7px;padding:.3rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-eye-fill"></i>Ver
                    </a>
                    <a href="{{ route('portal.docente.planificacion.edit', [$asignacion, $plan]) }}"
                       style="background:#1d4ed8;color:#fff;border-radius:7px;padding:.3rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-pencil-fill"></i>Editar
                    </a>
                    <form method="POST" action="{{ route('portal.docente.planificacion.toggle-publicado', [$asignacion, $plan]) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button style="background:{{ $plan->publicado ? '#dcfce7' : '#f1f5f9' }};color:{{ $plan->publicado ? '#15803d' : '#374151' }};border:none;border-radius:7px;padding:.3rem .6rem;font-size:.75rem;cursor:pointer;" title="{{ $plan->publicado ? 'Despublicar' : 'Publicar' }}">
                            <i class="bi bi-{{ $plan->publicado ? 'eye-fill' : 'eye-slash' }}"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('portal.docente.planificacion.destroy', [$asignacion, $plan]) }}"
                          onsubmit="return confirm('¿Eliminar esta planificación?')">
                        @csrf @method('DELETE')
                        <button style="background:#fee2e2;color:#dc2626;border:none;border-radius:7px;padding:.3rem .6rem;font-size:.75rem;cursor:pointer;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
