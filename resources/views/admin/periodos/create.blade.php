@extends('layouts.admin')
@section('page-title', 'Nuevo Período')

@section('content')

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-calendar3 me-2"></i>Nuevo Período
    </h4>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('admin.periodos.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Año Escolar <span class="text-danger">*</span></label>
                <select name="school_year_id" class="form-select @error('school_year_id') is-invalid @enderror" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach($schoolYears as $sy)
                    <option value="{{ $sy->id }}"
                        {{ old('school_year_id', $schoolYear?->id ?? request('year')) == $sy->id ? 'selected' : '' }}>
                        {{ $sy->nombre }} {{ $sy->activo ? '(Activo)' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('school_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Número <span class="text-danger">*</span></label>
                    <input type="number" name="numero" class="form-control @error('numero') is-invalid @enderror"
                           value="{{ old('numero', 1) }}" min="1" max="12" required>
                    @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-8">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" placeholder="Ej: Primer Trimestre" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                           value="{{ old('fecha_inicio') }}">
                    @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                           value="{{ old('fecha_fin') }}">
                    @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                               {{ old('activo', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="activo">Activo</label>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="cerrado" id="cerrado" value="1"
                               {{ old('cerrado') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="cerrado">Cerrado</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-2"></i>Crear Período
                </button>
            </div>
        </form>

    </div>
</div>
</div>
</div>

@endsection
