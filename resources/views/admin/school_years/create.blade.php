@extends('layouts.admin')
@section('page-title', 'Nuevo Año Escolar')

@section('content')

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-mortarboard me-2"></i>Nuevo Año Escolar
    </h4>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('admin.school-years.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre') }}" placeholder="Ej: 2024-2025" required>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Fecha Inicio <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                           value="{{ old('fecha_inicio') }}" required>
                    @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Fecha Fin <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                           value="{{ old('fecha_fin') }}" required>
                    @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                           {{ old('activo', true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="activo">Marcar como Año Activo</label>
                </div>
                <div class="text-muted mt-1" style="font-size:.8rem;">
                    <i class="bi bi-info-circle me-1"></i>Solo puede haber un año activo. Al activar este se desactivará el anterior.
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-2"></i>Crear Año Escolar
                </button>
            </div>
        </form>

    </div>
</div>
</div>
</div>

@endsection
