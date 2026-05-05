@extends('layouts.admin')
@section('page-title', isset($evento) ? 'Editar Evento' : 'Nuevo Evento')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.eventos.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-calendar-event me-2"></i>
        {{ isset($evento) ? 'Editar Evento' : 'Nuevo Evento' }}
    </h4>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body" style="max-width:760px;">

        <form method="POST"
              action="{{ isset($evento) ? route('admin.eventos.update', $evento) : route('admin.eventos.store') }}">
            @csrf
            @if(isset($evento)) @method('PUT') @endif

            <div class="row g-3">

                {{-- Nombre --}}
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Nombre del Evento <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $evento->nombre ?? '') }}"
                           placeholder="Ej: Feria Científica 2026" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Descripción --}}
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Descripción breve del evento...">{{ old('descripcion', $evento->descripcion ?? '') }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tipo --}}
                <div class="col-sm-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Tipo <span class="text-danger">*</span>
                    </label>
                    <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="academico"  @selected(old('tipo', $evento->tipo ?? '') === 'academico')>Académico</option>
                        <option value="deportivo"  @selected(old('tipo', $evento->tipo ?? '') === 'deportivo')>Deportivo</option>
                        <option value="cultural"   @selected(old('tipo', $evento->tipo ?? '') === 'cultural')>Cultural</option>
                        <option value="social"     @selected(old('tipo', $evento->tipo ?? '') === 'social')>Social</option>
                        <option value="otro"       @selected(old('tipo', $evento->tipo ?? '') === 'otro')>Otro</option>
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Lugar --}}
                <div class="col-sm-8">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Lugar</label>
                    <input type="text" name="lugar" class="form-control @error('lugar') is-invalid @enderror"
                           value="{{ old('lugar', $evento->lugar ?? '') }}"
                           placeholder="Ej: Patio principal, Auditorio...">
                    @error('lugar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fecha inicio --}}
                <div class="col-sm-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Fecha de Inicio <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                           value="{{ old('fecha_inicio', isset($evento) ? $evento->fecha_inicio->format('Y-m-d') : '') }}"
                           required>
                    @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fecha fin --}}
                <div class="col-sm-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Fecha de Finalización</label>
                    <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                           value="{{ old('fecha_fin', isset($evento) && $evento->fecha_fin ? $evento->fecha_fin->format('Y-m-d') : '') }}">
                    @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Cupo máximo --}}
                <div class="col-sm-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Cupo Máximo</label>
                    <input type="number" name="cupo_maximo" min="1"
                           class="form-control @error('cupo_maximo') is-invalid @enderror"
                           value="{{ old('cupo_maximo', $evento->cupo_maximo ?? '') }}"
                           placeholder="Sin límite si se deja vacío">
                    @error('cupo_maximo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Activo --}}
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="chkActivo"
                               @checked(old('activo', $evento->activo ?? true))>
                        <label class="form-check-label fw-semibold" for="chkActivo" style="font-size:.85rem;">
                            Evento activo (visible y con inscripciones habilitadas)
                        </label>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="col-12 d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:8px;min-width:140px;">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($evento) ? 'Guardar Cambios' : 'Crear Evento' }}
                    </button>
                    <a href="{{ route('admin.eventos.index') }}" class="btn btn-outline-secondary" style="border-radius:8px;">
                        Cancelar
                    </a>
                </div>

            </div>
        </form>

    </div>
</div>

@endsection
