@extends('layouts.admin')
@section('page-title', 'Registrar Préstamo')

@section('content')
<div class="container-fluid py-3" style="max-width:760px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-plus-circle text-primary me-2"></i>Registrar Préstamo
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

    @if($libros->isEmpty())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        No hay libros con ejemplares disponibles en este momento.
        <a href="{{ route('admin.biblioteca.index') }}" class="alert-link">Ver catálogo</a>
    </div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.biblioteca.prestamos.store') }}">
                @csrf

                <div class="row g-3">
                    {{-- Libro --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Libro <span class="text-danger">*</span></label>
                        <select name="libro_id"
                                class="form-select @error('libro_id') is-invalid @enderror"
                                required
                                x-data
                                x-init
                                id="selectLibro">
                            <option value="">Seleccionar libro disponible...</option>
                            @foreach($libros as $libro)
                            <option value="{{ $libro->id }}"
                                    data-disponibles="{{ $libro->cantidad_disponible }}"
                                    {{ old('libro_id') == $libro->id ? 'selected' : '' }}>
                                {{ $libro->titulo }} — {{ $libro->autor }}
                                ({{ $libro->cantidad_disponible }} disponible{{ $libro->cantidad_disponible !== 1 ? 's' : '' }})
                            </option>
                            @endforeach
                        </select>
                        @error('libro_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Estudiante --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Estudiante <span class="text-danger">*</span></label>
                        <select name="estudiante_id"
                                class="form-select @error('estudiante_id') is-invalid @enderror"
                                required>
                            <option value="">Seleccionar estudiante...</option>
                            @foreach($estudiantes as $est)
                            <option value="{{ $est->id }}"
                                    {{ old('estudiante_id') == $est->id ? 'selected' : '' }}>
                                {{ $est->apellidos }}, {{ $est->nombres }}
                            </option>
                            @endforeach
                        </select>
                        @error('estudiante_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Fecha préstamo --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha de Préstamo <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_prestamo"
                               class="form-control @error('fecha_prestamo') is-invalid @enderror"
                               value="{{ old('fecha_prestamo', now()->toDateString()) }}" required>
                        @error('fecha_prestamo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Fecha vencimiento --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha de Vencimiento <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_vencimiento"
                               class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                               value="{{ old('fecha_vencimiento', now()->addDays(14)->toDateString()) }}" required>
                        @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Por defecto: 14 días</small>
                    </div>

                    {{-- Notas --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notas <span class="text-muted small">(opcional)</span></label>
                        <textarea name="notas"
                                  class="form-control @error('notas') is-invalid @enderror"
                                  rows="2" maxlength="500"
                                  placeholder="Observaciones sobre el estado del libro, etc.">{{ old('notas') }}</textarea>
                        @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Registrar Préstamo
                    </button>
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
