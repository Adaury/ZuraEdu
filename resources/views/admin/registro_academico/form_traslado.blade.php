@extends('layouts.admin')
@section('page-title', 'Registrar Traslado')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-arrow-left-right me-2" style="color:#f59e0b;"></i>Registrar Traslado
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">Transferencia a otra institución educativa</p>
    </div>
    <a href="{{ route('admin.registro-academico.traslados') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Info del estudiante --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.75rem;">Estudiante</div>
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#2a4f96,var(--primary));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
            {{ mb_strtoupper(mb_substr($estudiante->nombres,0,1) . mb_substr($estudiante->apellidos,0,1)) }}
        </div>
        <div>
            <div style="font-weight:700;font-size:1rem;color:#1e293b;">{{ $estudiante->nombre_completo }}</div>
            <div style="font-size:.8rem;color:#6b7280;">
                {{ $estudiante->numero_matricula }}
                @if($estudiante->cedula) · {{ $estudiante->cedula }} @endif
            </div>
            <div style="font-size:.8rem;color:#6b7280;">
                {{ $matricula->grupo?->grado?->nombre }} {{ $matricula->grupo?->seccion?->nombre }}
                · {{ $matricula->schoolYear?->nombre }}
            </div>
        </div>
    </div>
</div>

<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.registro-academico.traslado.registrar', $estudiante) }}">
        @csrf

        @if($errors->any())
        <div class="alert alert-danger mb-4" style="border-radius:10px;font-size:.84rem;">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-bold" style="font-size:.85rem;">Institución Destino <span class="text-danger">*</span></label>
                <input type="text" name="institucion_traslado" value="{{ old('institucion_traslado') }}"
                       placeholder="Nombre completo del centro educativo"
                       class="form-control @error('institucion_traslado') is-invalid @enderror" style="border-radius:8px;">
                @error('institucion_traslado')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;">Fecha del Traslado <span class="text-danger">*</span></label>
                <input type="date" name="fecha_baja" value="{{ old('fecha_baja', today()->format('Y-m-d')) }}"
                       class="form-control @error('fecha_baja') is-invalid @enderror" style="border-radius:8px;">
                @error('fecha_baja')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">Motivo del Traslado</label>
                <textarea name="motivo_baja" rows="3"
                          placeholder="Motivo del traslado (opcional)"
                          class="form-control" style="border-radius:8px;">{{ old('motivo_baja') }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 justify-content-end">
            <a href="{{ route('admin.registro-academico.traslados') }}" class="btn btn-outline-secondary" style="border-radius:8px;">
                Cancelar
            </a>
            <button type="submit" class="btn" style="background:#f59e0b;color:#fff;border-radius:8px;font-weight:600;"
                    onclick="return confirm('¿Confirmar traslado de {{ $estudiante->nombre_completo }}?')">
                <i class="bi bi-arrow-left-right me-1"></i>Registrar Traslado
            </button>
        </div>
    </form>
</div>
@endsection
