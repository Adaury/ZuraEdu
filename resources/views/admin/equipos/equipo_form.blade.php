@extends('layouts.admin')
@section('page-title', isset($equipo) ? 'Editar Equipo' : 'Nuevo Equipo')

@section('content')
<div class="container-fluid py-3" style="max-width:640px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.equipos.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-{{ isset($equipo) ? 'pencil-square' : 'plus-circle' }} text-primary me-2"></i>
            {{ isset($equipo) ? 'Editar Equipo' : 'Nuevo Equipo' }}
        </h4>
    </div>

    @if($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST"
                  action="{{ isset($equipo) ? route('admin.equipos.update', $equipo) : route('admin.equipos.store') }}">
                @csrf
                @if(isset($equipo)) @method('PUT') @endif

                <div class="row g-3">
                    {{-- Nombre --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre"
                               class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $equipo->nombre ?? '') }}"
                               maxlength="200" required
                               placeholder="Ej: Laptop Dell Inspiron 15">
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tipo --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                            <option value="">Seleccionar tipo...</option>
                            @foreach($tipos as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('tipo', $equipo->tipo ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Código --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Código / Serie <span class="text-muted small">(opcional)</span>
                        </label>
                        <input type="text" name="codigo"
                               class="form-control @error('codigo') is-invalid @enderror"
                               value="{{ old('codigo', $equipo->codigo ?? '') }}"
                               maxlength="60"
                               placeholder="Ej: SN-2024-001">
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                            @foreach($estados as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('estado', $equipo->estado ?? 'disponible') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Descripción <span class="text-muted small">(opcional)</span>
                        </label>
                        <textarea name="descripcion"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  rows="3" maxlength="1000"
                                  placeholder="Características, modelo, observaciones...">{{ old('descripcion', $equipo->descripcion ?? '') }}</textarea>
                        @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($equipo) ? 'Guardar cambios' : 'Registrar equipo' }}
                    </button>
                    <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
