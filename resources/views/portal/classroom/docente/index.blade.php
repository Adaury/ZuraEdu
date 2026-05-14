@extends('layouts.admin')
@section('page-title', 'Mi Classroom')
@section('content')

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-easel2-fill me-2" style="color:#3B82F6;"></i>Mi Classroom</h4>
    <p class="text-muted small mb-0">Tus aulas virtuales activas</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if($clases->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-easel2" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="fw-semibold mb-1">No tienes aulas virtuales</p>
    <small>Las aulas se crean desde el panel administrativo por asignación</small>
</div>
@else
<div class="row g-3">
@foreach($clases as $clase)
<div class="col-md-6 col-xl-4">
    <a href="{{ route('portal.docente.classroom.show', $clase) }}" class="text-decoration-none">
    <div class="card h-100 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;transition:transform .15s;">
        <div style="background:{{ $clase->portada_color ?? '#3B82F6' }};height:10px;"></div>
        <div class="card-body">
            <h6 class="fw-bold mb-1" style="color:#111827;">{{ $clase->nombre }}</h6>
            <small class="text-muted d-block mb-2">{{ $clase->asignacion->asignatura?->nombre }} &bull; {{ $clase->asignacion->grupo?->nombre }}</small>
            @if($clase->descripcion)<p class="text-muted small mb-3" style="font-size:.8rem;line-height:1.4;">{{ Str::limit($clase->descripcion, 70) }}</p>@endif
            <div class="d-flex gap-3 small text-muted">
                <span><i class="bi bi-files me-1"></i>{{ $clase->materiales->count() }} materiales</span>
                <span><i class="bi bi-pencil me-1"></i>{{ $clase->materiales->whereIn('tipo',['tarea','evaluacion'])->count() }} tareas</span>
            </div>
        </div>
    </div>
    </a>
</div>
@endforeach
</div>
@endif

@push('realtime-data')
<script>
window._SGE_CLASE_IDS = {!! $clases->pluck('id')->values()->toJson() !!};
</script>
@endpush

@endsection
