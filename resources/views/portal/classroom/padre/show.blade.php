@extends('layouts.portal')
@section('title', $claseVirtual->nombre)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'classroom'])
@endsection

@section('content')
<div class="container-fluid px-0">

{{-- Header --}}
<div class="mb-4 p-4 rounded-2xl" style="background:{{ $claseVirtual->portada_color ?? '#3B82F6' }};">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('portal.padre.hijo.classroom.index', $estudiante) }}"
           class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;">← Volver</a>
        <div>
            <h4 class="text-white fw-bold mb-0">{{ $claseVirtual->nombre }}</h4>
            <small class="text-white opacity-75">
                {{ $claseVirtual->asignacion->asignatura?->nombre }} •
                Prof. {{ $claseVirtual->asignacion->docente?->user?->name }}
            </small>
        </div>
    </div>
</div>

{{-- Resumen tareas --}}
@php
    $tareas = $materiales->whereIn('tipo', ['tarea','evaluacion']);
    $entregadas = $tareas->filter(fn($m) => isset($entregasMap[$m->id]));
    $calificadas = $entregadas->filter(fn($m) => $entregasMap[$m->id]?->estado === 'calificado');
@endphp
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px;">
            <div class="fs-3 fw-bold text-primary">{{ $tareas->count() }}</div>
            <small class="text-muted">Tareas/Evaluaciones</small>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px;">
            <div class="fs-3 fw-bold text-success">{{ $entregadas->count() }}</div>
            <small class="text-muted">Entregadas</small>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px;">
            <div class="fs-3 fw-bold text-warning">{{ $calificadas->count() }}</div>
            <small class="text-muted">Calificadas</small>
        </div>
    </div>
</div>

{{-- Stream de materiales --}}
@forelse($materiales as $material)
@php
    $entrega = $entregasMap[$material->id] ?? null;
    $esTarea = in_array($material->tipo, ['tarea','evaluacion']);
    $colorTipo = ['anuncio'=>'#6366F1','material'=>'#10B981','tarea'=>'#F59E0B','evaluacion'=>'#EF4444'][$material->tipo] ?? '#6B7280';
    $iconTipo  = ['anuncio'=>'bi-megaphone-fill','material'=>'bi-book-fill','tarea'=>'bi-pencil-fill','evaluacion'=>'bi-clipboard-check-fill'][$material->tipo] ?? 'bi-file-text-fill';
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:16px;border-left:4px solid {{ $colorTipo }} !important;">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="d-flex gap-3 flex-1">
                <div style="width:40px;height:40px;background:{{ $colorTipo }}22;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $iconTipo }}" style="color:{{ $colorTipo }};font-size:1.1rem;"></i>
                </div>
                <div class="flex-1">
                    <div class="fw-semibold" style="color:#111827;">{{ $material->titulo }}</div>
                    <div class="text-muted small mt-1">{{ Str::limit($material->contenido, 150) }}</div>
                    @if($material->fecha_limite)
                    <small class="text-muted mt-1 d-block">
                        <i class="bi bi-calendar me-1"></i>Fecha límite: {{ \Carbon\Carbon::parse($material->fecha_limite)->format('d/m/Y H:i') }}
                    </small>
                    @endif
                    @if($material->puntos)
                    <small class="text-muted"><i class="bi bi-star me-1"></i>{{ $material->puntos }} puntos</small>
                    @endif
                </div>
            </div>
            {{-- Estado de entrega --}}
            @if($esTarea)
            <div class="text-end flex-shrink-0">
                @if($entrega && $entrega->estado === 'calificado')
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Calificado
                    </span>
                    @if($entrega->calificacion !== null)
                    <div class="fw-bold mt-1" style="color:#059669;font-size:1.1rem;">{{ $entrega->calificacion }}/{{ $material->puntos ?? 100 }}</div>
                    @endif
                    @if($entrega->comentario_docente)
                    <div class="small text-muted mt-1" style="max-width:160px;">
                        <i class="bi bi-chat-left-text me-1"></i>{{ Str::limit($entrega->comentario_docente, 60) }}
                    </div>
                    @endif
                @elseif($entrega && $entrega->estado === 'entregado')
                    <span class="badge bg-info"><i class="bi bi-clock me-1"></i>Entregado</span>
                    <div class="text-muted small mt-1">{{ \Carbon\Carbon::parse($entrega->fecha_entrega)->format('d/m H:i') }}</div>
                @else
                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation me-1"></i>Pendiente</span>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@empty
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    <p>No hay materiales publicados aún.</p>
</div>
@endforelse

</div>
@endsection
