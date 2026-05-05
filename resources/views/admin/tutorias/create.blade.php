@extends('layouts.admin')

@section('page-title', 'Asignar Tutor')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(30,58,110,.06);
    }
    .form-section-title {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        margin-bottom: 1rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .875rem;
        padding: .5rem .75rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.1);
    }
    .form-control.is-invalid, .form-select.is-invalid { border-color: #dc2626; }
    .invalid-feedback { font-size: .75rem; }
    .grupo-taken { opacity: .45; pointer-events: none; }
    [data-theme="dark"] .form-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .form-section-title { border-color: #334155; }
    [data-theme="dark"] .form-label { color: #cbd5e1; }
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tutorias.index') }}" class="text-decoration-none">Tutorías</a></li>
        <li class="breadcrumb-item active">Asignar Tutor</li>
    </ol>
</nav>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-person-plus me-2"></i>Asignar Tutor
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            Vincula un docente como tutor responsable de un grupo.
        </p>
    </div>
    <a href="{{ route('admin.tutorias.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Error global --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="form-card p-4">
            <form action="{{ route('admin.tutorias.store') }}" method="POST" novalidate>
                @csrf

                {{-- Año escolar (oculto, se usa el actual) --}}
                <input type="hidden" name="school_year_id"
                       value="{{ $schoolYear?->id }}">

                {{-- Sección: Docente --}}
                <p class="form-section-title"><i class="bi bi-person-badge me-1"></i>Docente Tutor</p>

                <div class="mb-3">
                    <label class="form-label" for="docente_id">Docente <span class="text-danger">*</span></label>
                    <select name="docente_id" id="docente_id"
                            class="form-select @error('docente_id') is-invalid @enderror"
                            required>
                        <option value="">— Seleccionar docente —</option>
                        @foreach($docentes as $docente)
                            <option value="{{ $docente->id }}"
                                    @selected(old('docente_id') == $docente->id)>
                                {{ $docente->nombre_completo }}
                                @if($docente->especialidad) — {{ $docente->especialidad }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('docente_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Sección: Grupo --}}
                <p class="form-section-title mt-4"><i class="bi bi-people me-1"></i>Grupo</p>

                <div class="mb-3">
                    <label class="form-label" for="grupo_id">Grupo <span class="text-danger">*</span></label>
                    <select name="grupo_id" id="grupo_id"
                            class="form-select @error('grupo_id') is-invalid @enderror"
                            required>
                        <option value="">— Seleccionar grupo —</option>
                        @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}"
                                    @selected(old('grupo_id') == $grupo->id)
                                    @if($gruposConTutoria->contains($grupo->id)) disabled class="grupo-taken" @endif>
                                {{ $grupo->nombre_completo }}
                                @if($gruposConTutoria->contains($grupo->id)) (ya tiene tutor)@endif
                            </option>
                        @endforeach
                    </select>
                    @error('grupo_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text" style="font-size:.75rem;">
                        Los grupos marcados como "(ya tiene tutor)" ya tienen asignación activa para este año.
                    </div>
                </div>

                {{-- Descripción --}}
                <p class="form-section-title mt-4"><i class="bi bi-card-text me-1"></i>Observaciones (opcional)</p>

                <div class="mb-4">
                    <label class="form-label" for="descripcion">Descripción / Notas</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              maxlength="500"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Objetivos de la tutoría, notas generales...">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Año escolar (visible, solo lectura) --}}
                @if($schoolYear)
                <div class="alert alert-light border mb-4" style="border-radius:8px;font-size:.82rem;">
                    <i class="bi bi-calendar3 me-2 text-primary"></i>
                    Año escolar activo: <strong>{{ $schoolYear->nombre }}</strong>
                </div>
                @else
                <div class="alert alert-warning border mb-4" style="border-radius:8px;font-size:.82rem;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No hay un año escolar activo. Activa uno antes de continuar.
                </div>
                @endif

                {{-- Botones --}}
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.tutorias.index') }}"
                       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1.25rem;"
                            @unless($schoolYear) disabled @endunless>
                        <i class="bi bi-check-lg me-1"></i>Guardar Asignación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
