@extends('layouts.admin')
@section('page-title', isset($libro) ? 'Editar Libro' : 'Nuevo Libro')

@section('content')
<div class="container-fluid py-3" style="max-width:760px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.biblioteca.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-book text-primary me-2"></i>
            {{ isset($libro) ? 'Editar Libro' : 'Registrar Nuevo Libro' }}
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
                  action="{{ isset($libro) ? route('admin.biblioteca.libros.update', $libro) : route('admin.biblioteca.libros.store') }}">
                @csrf
                @if(isset($libro)) @method('PUT') @endif

                <div class="row g-3">
                    {{-- Título --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                               value="{{ old('titulo', $libro->titulo ?? '') }}" required maxlength="255">
                        @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Autor --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Autor <span class="text-danger">*</span></label>
                        <input type="text" name="autor" class="form-control @error('autor') is-invalid @enderror"
                               value="{{ old('autor', $libro->autor ?? '') }}" required maxlength="255">
                        @error('autor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- ISBN --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ISBN <span class="text-muted small">(opcional)</span></label>
                        <input type="text" name="isbn" class="form-control @error('isbn') is-invalid @enderror"
                               value="{{ old('isbn', $libro->isbn ?? '') }}" maxlength="30"
                               placeholder="978-XXXXXXXXXX">
                        @error('isbn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Categoría --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                            <option value="">Seleccionar categoría...</option>
                            @foreach($categorias as $key => $label)
                            <option value="{{ $key }}" {{ old('categoria', $libro->categoria ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Cantidad --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cantidad Total <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad_total"
                               class="form-control @error('cantidad_total') is-invalid @enderror"
                               value="{{ old('cantidad_total', $libro->cantidad_total ?? 1) }}"
                               min="1" required>
                        @error('cantidad_total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @isset($libro)
                        <small class="text-muted">Disponibles actualmente: <strong>{{ $libro->cantidad_disponible }}</strong></small>
                        @endisset
                    </div>

                    {{-- Descripción --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción <span class="text-muted small">(opcional)</span></label>
                        <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                                  rows="3" maxlength="1000">{{ old('descripcion', $libro->descripcion ?? '') }}</textarea>
                        @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($libro) ? 'Guardar Cambios' : 'Registrar Libro' }}
                    </button>
                    <a href="{{ route('admin.biblioteca.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
