@extends('layouts.portal')
@section('page-title', 'Tareas — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'tareas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-check2-square"></i>Tareas
    </a>
@endsection

@push('styles')
<style>
.badge-tipo {
    display: inline-block;
    padding: .18rem .55rem;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .02em;
    color: #fff;
}
.card-tarea {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.1rem;
    margin-bottom: .75rem;
    transition: box-shadow .15s, border-color .15s;
}
.card-tarea:hover {
    box-shadow: 0 4px 16px rgba(59,130,246,.10);
    border-color: #93c5fd;
}
.progress-mini {
    height: 6px;
    border-radius: 99px;
    background: #e2e8f0;
    overflow: hidden;
    margin-top: .35rem;
}
.progress-mini-bar {
    height: 100%;
    border-radius: 99px;
    background: #3b82f6;
    transition: width .4s;
}
.stat-pill {
    font-size: .7rem;
    font-weight: 600;
    padding: .12rem .42rem;
    border-radius: 99px;
    color: #fff;
}
.vencida-label {
    font-size: .68rem;
    font-weight: 700;
    color: #ef4444;
    margin-left: .25rem;
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
    <div>
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-check2-square me-2" style="color:#3b82f6;"></i>Agenda / Tareas
        </h2>
        <p style="font-size:.78rem;color:var(--prt-muted);margin:.15rem 0 0;">
            {{ $asignacion->asignatura?->nombre }} &mdash;
            {{ $asignacion->grupo?->nombre_corto ?? $asignacion->grupo?->nombre ?? '' }}
        </p>
    </div>
    <a href="{{ route('portal.docente.tareas.create', $asignacion) }}"
       class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i>Nueva Tarea
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 py-2" style="font-size:.83rem;" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros rápidos --}}
<div class="d-flex gap-2 flex-wrap mb-3" x-data="{ filtro: 'todas' }">
    @foreach(['todas' => 'Todas', 'tarea' => 'Tarea', 'actividad' => 'Actividad', 'proyecto' => 'Proyecto', 'evaluacion' => 'Evaluación'] as $k => $label)
    <button
        @click="filtro = '{{ $k }}'"
        :class="filtro === '{{ $k }}' ? 'btn-primary' : 'btn-outline-secondary'"
        class="btn btn-sm"
        style="font-size:.74rem;"
        x-on:click="document.querySelectorAll('[data-tipo]').forEach(el => {
            if ('{{ $k }}' === 'todas' || el.dataset.tipo === '{{ $k }}') {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        })">
        {{ $label }}
    </button>
    @endforeach
</div>

@if($tareas->isEmpty())
<div style="text-align:center;padding:3rem 1rem;color:var(--prt-muted);">
    <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.35;"></i>
    <p style="margin-top:.75rem;font-size:.9rem;">Aún no hay tareas para esta asignación.</p>
    <a href="{{ route('portal.docente.tareas.create', $asignacion) }}" class="btn btn-primary btn-sm mt-1">
        <i class="bi bi-plus-lg me-1"></i>Crear primera tarea
    </a>
</div>
@else

<div id="lista-tareas">
@foreach($tareas as $tarea)
@php
    $counts   = $entregasCounts[$tarea->id] ?? collect();
    $nEntregadas = $counts->where('estado', 'entregada')->sum('total')
                 + $counts->where('estado', 'revisada')->sum('total');
    $nRevisadas  = $counts->where('estado', 'revisada')->sum('total');
    $porcentaje  = $totalEstudiantes > 0 ? round($nEntregadas / $totalEstudiantes * 100) : 0;
    $vencida     = $tarea->fecha_limite->isPast() && !$tarea->activo === false;
@endphp
<div class="card-tarea" data-tipo="{{ $tarea->tipo }}">
    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
        <div style="flex:1;min-width:0;">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge-tipo" style="background:{{ $tarea->tipo_color }};">
                    {{ $tarea->tipo_label }}
                </span>
                @if(! $tarea->activo)
                <span class="badge bg-secondary" style="font-size:.67rem;">Inactiva</span>
                @endif
                @if($tarea->fecha_limite->isPast())
                <span class="vencida-label"><i class="bi bi-clock-history"></i> Vencida</span>
                @endif
            </div>
            <h3 style="font-size:.92rem;font-weight:700;margin:0 0 .15rem;color:#1e293b;">
                {{ $tarea->titulo }}
            </h3>
            @if($tarea->descripcion)
            <p style="font-size:.78rem;color:var(--prt-muted);margin:0 0 .4rem;line-clamp:2;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;">
                {{ $tarea->descripcion }}
            </p>
            @endif
            <div class="d-flex gap-3 flex-wrap" style="font-size:.74rem;color:var(--prt-muted);">
                <span><i class="bi bi-calendar3 me-1"></i>{{ $tarea->fecha_limite->format('d/m/Y') }}</span>
                @if($tarea->puntos_valor)
                <span><i class="bi bi-star-fill me-1" style="color:#f59e0b;"></i>{{ $tarea->puntos_valor }} pts</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-1">
            <a href="{{ route('portal.docente.tareas.entregas', [$asignacion, $tarea]) }}"
               class="btn btn-outline-primary btn-sm" style="font-size:.74rem;">
                <i class="bi bi-people-fill me-1"></i>Ver entregas
            </a>
            <a href="{{ route('portal.docente.tareas.edit', [$asignacion, $tarea]) }}"
               class="btn btn-outline-secondary btn-sm" style="font-size:.74rem;">
                <i class="bi bi-pencil-fill me-1"></i>Editar
            </a>
            <form method="POST"
                  action="{{ route('portal.docente.tareas.destroy', [$asignacion, $tarea]) }}"
                  onsubmit="return confirm('¿Eliminar esta tarea? Esta acción no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:.74rem;">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div class="mt-2" style="font-size:.72rem;color:var(--prt-muted);">
        <div class="d-flex justify-content-between mb-1">
            <span>
                <span class="stat-pill" style="background:#3b82f6;">{{ $nEntregadas }}</span> entregadas
                &nbsp;
                <span class="stat-pill" style="background:#10b981;">{{ $nRevisadas }}</span> revisadas
                &nbsp; de
                <strong>{{ $totalEstudiantes }}</strong> estudiantes
            </span>
            <span>{{ $porcentaje }}%</span>
        </div>
        <div class="progress-mini">
            <div class="progress-mini-bar" style="width:{{ $porcentaje }}%;"></div>
        </div>
    </div>
</div>
@endforeach
</div>

@endif

@endsection
