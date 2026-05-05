@extends('layouts.admin')
@section('page-title', isset($disciplina) ? 'Editar Falta Disciplinaria' : 'Registrar Falta Disciplinaria')

@section('content')
<div class="container-fluid py-3">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.disciplina.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-shield-exclamation text-danger me-2"></i>
            {{ isset($disciplina) ? 'Editar Falta Disciplinaria' : 'Registrar Falta Disciplinaria' }}
        </h4>
        <small class="text-muted">
            {{ isset($disciplina) ? 'Modifica los datos de la falta registrada' : 'Complete los datos para registrar la falta' }}
        </small>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-exclamation-circle me-1"></i>
    <strong>Corrija los siguientes errores:</strong>
    <ul class="mb-0 mt-1 ps-3" style="font-size:.875rem;">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <form method="POST"
                      action="{{ isset($disciplina)
                          ? route('admin.disciplina.update', $disciplina)
                          : route('admin.disciplina.store') }}">
                    @csrf
                    @if(isset($disciplina)) @method('PUT') @endif

                    {{-- Estudiante --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Estudiante <span class="text-danger">*</span>
                        </label>
                        <select name="estudiante_id"
                                class="form-select @error('estudiante_id') is-invalid @enderror"
                                required>
                            <option value="">— Seleccione un estudiante —</option>
                            @foreach($estudiantes as $est)
                            <option value="{{ $est->id }}"
                                {{ old('estudiante_id', $disciplina->estudiante_id ?? '') == $est->id ? 'selected' : '' }}>
                                {{ $est->apellidos }}, {{ $est->nombres }}
                            </option>
                            @endforeach
                        </select>
                        @error('estudiante_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tipo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Tipo de Falta <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            @foreach($tipos as $key => $t)
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="tipo"
                                       id="tipo_{{ $key }}" value="{{ $key }}"
                                       {{ old('tipo', $disciplina->tipo ?? '') === $key ? 'checked' : '' }}
                                       required>
                                <label class="btn btn-outline-secondary w-100 text-start"
                                       for="tipo_{{ $key }}"
                                       style="border-color:{{ $t['color'] }}20;">
                                    <i class="bi {{ $t['icon'] }} me-1"
                                       style="color:{{ $t['color'] }};"></i>
                                    <span style="color:{{ $t['color'] }};font-weight:600;">{{ $t['label'] }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('tipo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fecha --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Fecha <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="fecha"
                               class="form-control @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', isset($disciplina) ? $disciplina->fecha->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               max="{{ now()->format('Y-m-d') }}"
                               required>
                        @error('fecha')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea name="descripcion" rows="3"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  placeholder="Describa la situación disciplinaria..."
                                  maxlength="1000" required>{{ old('descripcion', $disciplina->descripcion ?? '') }}</textarea>
                        @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Docente (opcional) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Docente que reporta
                            <small class="text-muted fw-normal">(opcional)</small>
                        </label>
                        <select name="docente_id"
                                class="form-select @error('docente_id') is-invalid @enderror">
                            <option value="">— Sin docente asignado —</option>
                            @foreach($docentes as $doc)
                            <option value="{{ $doc->id }}"
                                {{ old('docente_id', $disciplina->docente_id ?? '') == $doc->id ? 'selected' : '' }}>
                                {{ $doc->apellidos }}, {{ $doc->nombres }}
                            </option>
                            @endforeach
                        </select>
                        @error('docente_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notas de resolución --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Notas de Resolución
                            <small class="text-muted fw-normal">(opcional)</small>
                        </label>
                        <textarea name="notas_resolucion" rows="2"
                                  class="form-control @error('notas_resolucion') is-invalid @enderror"
                                  placeholder="Acciones tomadas, sanciones, acuerdos..."
                                  maxlength="1000">{{ old('notas_resolucion', $disciplina->notas_resolucion ?? '') }}</textarea>
                        @error('notas_resolucion')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Resuelto (solo en edición) --}}
                    @isset($disciplina)
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="resuelto" id="resuelto" value="1"
                                   {{ old('resuelto', $disciplina->resuelto) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="resuelto">
                                Marcar como resuelta
                            </label>
                        </div>
                    </div>
                    @endisset

                    {{-- Botones --}}
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('admin.disciplina.index') }}"
                           class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-save me-1"></i>
                            {{ isset($disciplina) ? 'Guardar Cambios' : 'Registrar Falta' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</div>
@endsection
