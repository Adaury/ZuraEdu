@extends('layouts.admin')

@section('page-title', 'Nuevo Caso de Seguimiento')

@push('styles')
<style>
    .ss-section-title {
        font-size:.68rem; font-weight:700; letter-spacing:.1em;
        text-transform:uppercase; color:var(--primary);
        margin-bottom:1rem; padding-bottom:.4rem;
        border-bottom:1px solid #e5e7eb;
    }
</style>
@endpush

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.seguimiento-social.index') }}" class="text-decoration-none">Seguimiento Social</a></li>
        <li class="breadcrumb-item active">Nuevo Caso</li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="{{ route('admin.seguimiento-social.index') }}"
       class="btn btn-sm"
       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-person-plus me-2"></i>Nuevo Caso de Seguimiento
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">Registra un nuevo caso para un estudiante</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">

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

                <form method="POST" action="{{ route('admin.seguimiento-social.store') }}">
                    @csrf

                    {{-- Sección: Estudiante --}}
                    <div class="ss-section-title"><i class="bi bi-person me-1"></i>Estudiante</div>

                    <div class="mb-4">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">
                            Estudiante <span class="text-danger">*</span>
                        </label>
                        <select name="estudiante_id" required
                                class="form-select @error('estudiante_id') is-invalid @enderror"
                                style="border-radius:8px;font-size:.875rem;">
                            <option value="">— Selecciona un estudiante —</option>
                            @foreach($estudiantes as $est)
                                <option value="{{ $est->id }}" @selected(old('estudiante_id') == $est->id)>
                                    {{ $est->nombre_completo }} ({{ $est->numero_matricula }})
                                </option>
                            @endforeach
                        </select>
                        @error('estudiante_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Sección: Datos del Caso --}}
                    <div class="ss-section-title"><i class="bi bi-info-circle me-1"></i>Datos del Caso</div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Tipo de Caso <span class="text-danger">*</span>
                            </label>
                            <select name="tipo" required
                                    class="form-select @error('tipo') is-invalid @enderror"
                                    style="border-radius:8px;font-size:.875rem;">
                                <option value="">— Seleccionar —</option>
                                @foreach(\App\Models\CasoSeguimiento::TIPOS as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('tipo') === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Nivel de Riesgo <span class="text-danger">*</span>
                            </label>
                            <select name="nivel_riesgo" required
                                    class="form-select @error('nivel_riesgo') is-invalid @enderror"
                                    style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\CasoSeguimiento::NIVELES_RIESGO as $val => $info)
                                    <option value="{{ $val }}" @selected(old('nivel_riesgo', 'bajo') === $val)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                            @error('nivel_riesgo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Estado <span class="text-danger">*</span>
                            </label>
                            <select name="estado" required
                                    class="form-select"
                                    style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\CasoSeguimiento::ESTADOS as $val => $info)
                                    <option value="{{ $val }}" @selected(old('estado', 'abierto') === $val)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Fecha de Apertura <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="fecha_apertura" required
                                   value="{{ old('fecha_apertura', now()->format('Y-m-d')) }}"
                                   class="form-control @error('fecha_apertura') is-invalid @enderror"
                                   style="border-radius:8px;font-size:.875rem;">
                            @error('fecha_apertura')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Responsable</label>
                        <select name="responsable_id"
                                class="form-select"
                                style="border-radius:8px;font-size:.875rem;">
                            <option value="">— Sin asignar —</option>
                            @foreach($responsables as $resp)
                                <option value="{{ $resp->id }}" @selected(old('responsable_id') == $resp->id)>
                                    {{ $resp->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sección: Descripción --}}
                    <div class="ss-section-title"><i class="bi bi-chat-text me-1"></i>Descripción del Caso</div>

                    <div class="mb-4">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">
                            Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea name="descripcion" rows="5" required
                                  placeholder="Describe la situación, antecedentes relevantes y motivo de apertura del caso…"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  style="border-radius:8px;font-size:.875rem;resize:vertical;">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:8px;padding:.5rem 1.4rem;">
                            <i class="bi bi-check-lg me-1"></i>Crear Caso
                        </button>
                        <a href="{{ route('admin.seguimiento-social.index') }}"
                           class="btn fw-semibold"
                           style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem 1.2rem;">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
