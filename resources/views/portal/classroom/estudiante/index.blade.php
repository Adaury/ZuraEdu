@extends('layouts.portal')
@section('title', 'Mi Classroom')
@section('content')

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-easel2-fill me-2" style="color:#3B82F6;"></i>Mi Classroom</h4>
    <p class="text-muted small mb-0">Tus aulas virtuales activas</p>
</div>

@if(!empty($sinMatricula))
<div class="card border-0 shadow-sm" style="border-radius:16px;max-width:480px;margin:2rem auto;">
    <div class="card-body text-center py-5 px-4">
        <div style="width:64px;height:64px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:1.6rem;color:#d97706;"></i>
        </div>
        <h6 class="fw-bold mb-2" style="color:#1e293b;">Sin matrícula activa</h6>
        <p class="text-muted mb-3" style="font-size:.85rem;line-height:1.5;">
            No tienes una matrícula activa para el año escolar actual. Comunícate con la secretaría para verificar tu inscripción.
        </p>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>
@elseif($clases->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-easel2" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="fw-semibold mb-1">No hay aulas disponibles</p>
</div>
@else
<div class="row g-3">
@foreach($clases as $clase)
<div class="col-md-6">
    <a href="{{ route('portal.estudiante.classroom.show', $clase) }}" class="text-decoration-none">
    <div class="card h-100 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div style="background:{{ $clase->portada_color ?? '#3B82F6' }};height:8px;"></div>
        <div class="card-body">
            <h6 class="fw-bold mb-1" style="color:#111827;">{{ $clase->nombre }}</h6>
            <small class="text-muted d-block mb-2">{{ $clase->asignacion->asignatura?->nombre }} &bull; Prof. {{ $clase->asignacion->docente?->user?->name }}</small>
            <div class="d-flex gap-3 small text-muted">
                <span><i class="bi bi-files me-1"></i>{{ $clase->materiales->count() }} materiales</span>
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
@if($matricula?->grupo_id)
window._SGE_GRUPO_IDS = [{{ $matricula->grupo_id }}];
@endif
</script>
@endpush

@endsection
