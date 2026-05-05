@extends('layouts.admin')
@section('page-title', 'Nuevo Ticket')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary,#1e3a6e);">
            <i class="bi bi-plus-circle me-2"></i>Abrir Nuevo Ticket
        </h4>
        <p class="text-muted small mb-0 mt-1">Describe tu solicitud o incidencia con el mayor detalle posible</p>
    </div>
    <a href="{{ route('admin.soporte.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <form action="{{ route('admin.soporte.store') }}" method="POST">
                    @csrf

                    {{-- Título --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="titulo">
                            Título del ticket <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="titulo"
                               name="titulo"
                               value="{{ old('titulo') }}"
                               maxlength="200"
                               placeholder="Ej: No puedo acceder al módulo de calificaciones"
                               class="form-control @error('titulo') is-invalid @enderror">
                        @error('titulo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Categoría y Prioridad --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="categoria">
                                Categoría <span class="text-danger">*</span>
                            </label>
                            <select id="categoria" name="categoria"
                                    class="form-select @error('categoria') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                @foreach($categorias as $val => $label)
                                <option value="{{ $val }}" {{ old('categoria') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="prioridad">
                                Prioridad <span class="text-danger">*</span>
                            </label>
                            <select id="prioridad" name="prioridad"
                                    class="form-select @error('prioridad') is-invalid @enderror">
                                @foreach($prioridades as $val => $label)
                                <option value="{{ $val }}" {{ (old('prioridad', 'media') === $val) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @error('prioridad')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Usa <strong>Urgente</strong> solo si el sistema está completamente inoperativo.
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="descripcion">
                            Descripción detallada <span class="text-danger">*</span>
                        </label>
                        <textarea id="descripcion"
                                  name="descripcion"
                                  rows="7"
                                  maxlength="5000"
                                  placeholder="Describe paso a paso lo que ocurre, mensajes de error si los hay, y cualquier información que ayude a resolverlo..."
                                  class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-end mt-1">
                            <span class="text-muted small" x-data x-text="$refs.desc.value.length + '/5000'" x-init="
                                let el = document.getElementById('descripcion');
                                el.addEventListener('input', () => $el.textContent = el.value.length + '/5000');
                            ">0/5000</span>
                        </div>
                    </div>

                    {{-- Aviso --}}
                    <div class="alert alert-info py-2 mb-4 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Tu ticket será recibido por el equipo de soporte y recibirás una notificación cuando sea atendido.
                    </div>

                    {{-- Botones --}}
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.soporte.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Enviar Ticket
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection
