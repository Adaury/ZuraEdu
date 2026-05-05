@extends('layouts.portal')
@section('title', 'Classroom de '.$estudiante->nombre_completo)

@section('content')
<div class="container-fluid px-0">

{{-- Header --}}
<div class="mb-4 p-4 rounded-2xl" style="background:#3B82F6;">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-easel2-fill text-white fs-4"></i>
        </div>
        <div>
            <h4 class="text-white fw-bold mb-0">Classroom — {{ $estudiante->nombres }} {{ $estudiante->apellidos }}</h4>
            <small class="text-white opacity-75">Aulas virtuales del año escolar activo</small>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-light ms-auto">← Volver</a>
    </div>
</div>

@if($clases->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-easel2 fs-1 d-block mb-2"></i>
        <p>No hay clases virtuales disponibles aún.</p>
    </div>
@else
<div class="row g-3">
@foreach($clases as $clase)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('portal.padre.hijo.classroom.show', [$estudiante, $clase]) }}" class="text-decoration-none">
        <div class="card h-100 border-0 shadow-sm hover-shadow" style="border-radius:16px;overflow:hidden;">
            {{-- Portada de color --}}
            <div style="background:{{ $clase->portada_color ?? '#3B82F6' }};height:8px;"></div>
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <h6 class="fw-bold mb-1" style="color:#111827;">{{ $clase->nombre }}</h6>
                        <small class="text-muted">
                            <i class="bi bi-person-fill me-1"></i>{{ $clase->asignacion->docente?->user?->name ?? 'Docente' }}
                        </small>
                    </div>
                    @if($clase->_pendientes > 0)
                    <span class="badge bg-danger rounded-pill">{{ $clase->_pendientes }} pendiente{{ $clase->_pendientes > 1 ? 's' : '' }}</span>
                    @else
                    <span class="badge bg-success rounded-pill"><i class="bi bi-check2"></i> Al día</span>
                    @endif
                </div>
                @if($clase->descripcion)
                <p class="text-muted small mb-3" style="line-height:1.4;">{{ Str::limit($clase->descripcion, 80) }}</p>
                @endif
                <div class="d-flex gap-3 small text-muted">
                    <span><i class="bi bi-book me-1"></i>{{ $clase->_tareas_total }} tarea{{ $clase->_tareas_total != 1 ? 's' : '' }}</span>
                    <span><i class="bi bi-check-circle me-1 text-success"></i>{{ $clase->_tareas_entregadas }} entregada{{ $clase->_tareas_entregadas != 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>
        </a>
    </div>
@endforeach
</div>
@endif

</div>
@endsection
