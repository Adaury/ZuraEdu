@extends('layouts.admin')

@section('page-title', 'Nuevo Grupo')

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
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--secondary);
    }
    .invalid-feedback { font-size: .75rem; }
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    [data-theme="dark"] .form-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.grupos.index') }}" class="text-decoration-none">Grupos</a></li>
        <li class="breadcrumb-item active">Nuevo Grupo</li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-plus-circle me-2"></i>Nuevo Grupo
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">Registra un nuevo grupo o sección</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="form-card p-4">

            @if($errors->any())
                <div class="alert alert-danger border-0 mb-4" style="border-radius:10px;font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Por favor corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.grupos.store') }}" method="POST">
                @csrf

                {{-- Section: Año y Clasificación --}}
                <div class="form-section-title">
                    <i class="bi bi-calendar3 me-1"></i>Año y Clasificación
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="school_year_id">
                            Año Escolar <span class="text-danger">*</span>
                        </label>
                        <select name="school_year_id" id="school_year_id"
                                class="form-select @error('school_year_id') is-invalid @enderror" required>
                            <option value="">— Seleccionar año escolar —</option>
                            @foreach($schoolYears as $sy)
                                <option value="{{ $sy->id }}"
                                    {{ (old('school_year_id', $schoolYears->firstWhere('activo', true)?->id) == $sy->id) ? 'selected' : '' }}>
                                    {{ $sy->nombre }}
                                    @if($sy->activo) (Actual) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('school_year_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="grado_id">
                            Grado <span class="text-danger">*</span>
                        </label>
                        <select name="grado_id" id="grado_id"
                                class="form-select @error('grado_id') is-invalid @enderror" required>
                            <option value="">— Seleccionar grado —</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" {{ old('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('grado_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="seccion_id">
                            Sección <span class="text-danger">*</span>
                        </label>
                        <select name="seccion_id" id="seccion_id"
                                class="form-select @error('seccion_id') is-invalid @enderror" required>
                            <option value="">— Seleccionar sección —</option>
                            @foreach($secciones as $seccion)
                                <option value="{{ $seccion->id }}" {{ old('seccion_id') == $seccion->id ? 'selected' : '' }}>
                                    {{ $seccion->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('seccion_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Section: Tutor y Aula --}}
                <div class="form-section-title">
                    <i class="bi bi-person-badge me-1"></i>Tutor y Aula
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="tutor_id">Docente Tutor</label>
                        <select name="tutor_id" id="tutor_id"
                                class="form-select @error('tutor_id') is-invalid @enderror">
                            <option value="">— Sin tutor asignado —</option>
                            @foreach($docentes as $docente)
                                <option value="{{ $docente->id }}"
                                    {{ old('tutor_id') == $docente->id ? 'selected' : '' }}>
                                    {{ $docente->nombre_completo }}
                                    @if($docente->especialidad) — {{ $docente->especialidad }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('tutor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="aula">Aula / Salón</label>
                        <input type="text" name="aula" id="aula"
                               class="form-control @error('aula') is-invalid @enderror"
                               value="{{ old('aula') }}"
                               placeholder="Ej: A-101, Laboratorio 2"
                               maxlength="20">
                        @error('aula')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="capacidad">Capacidad máxima</label>
                        <input type="number" name="capacidad" id="capacidad"
                               class="form-control @error('capacidad') is-invalid @enderror"
                               value="{{ old('capacidad', 30) }}"
                               min="1" max="60"
                               placeholder="Ej: 30">
                        @error('capacidad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Section: Estado --}}
                <div class="form-section-title">
                    <i class="bi bi-toggle-on me-1"></i>Estado
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="activo" id="activo" value="1"
                               {{ old('activo', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="activo" style="font-size:.85rem;">
                            Grupo activo
                        </label>
                        <div class="text-muted mt-1" style="font-size:.75rem;">
                            Los grupos activos aparecen en matrículas y asignaciones.
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.5rem 1.4rem;">
                        <i class="bi bi-check-lg me-1"></i>Crear Grupo
                    </button>
                    <a href="{{ route('admin.grupos.index') }}"
                       class="btn fw-semibold"
                       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem 1.2rem;">
                        Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection
