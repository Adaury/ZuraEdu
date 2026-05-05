@extends('layouts.admin')
@section('page-title', 'Registrar Incidente Médico')

@section('content')
<div class="container-fluid py-3">

{{-- Encabezado ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-clipboard2-plus text-warning me-2"></i>Registrar Incidente Médico
        </h4>
        <small class="text-muted">Complete los datos del incidente ocurrido</small>
    </div>
    <a href="{{ route('admin.salud.incidentes') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a incidentes
    </a>
</div>

{{-- Formulario ──────────────────────────────────────────────────────────── --}}
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <form action="{{ route('admin.salud.incidentes.guardar') }}" method="POST">
            @csrf

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Datos del Incidente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Estudiante --}}
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold small">
                                Estudiante <span class="text-danger">*</span>
                            </label>
                            <select name="estudiante_id"
                                    class="form-select form-select-sm @error('estudiante_id') is-invalid @enderror"
                                    required>
                                <option value="">— Seleccione un estudiante —</option>
                                @foreach($estudiantes as $est)
                                <option value="{{ $est->id }}"
                                    {{ old('estudiante_id', $estudiante?->id) == $est->id ? 'selected' : '' }}>
                                    {{ $est->nombre_completo }}
                                    @if($est->numero_matricula) ({{ $est->numero_matricula }}) @endif
                                </option>
                                @endforeach
                            </select>
                            @error('estudiante_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Fecha --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="fecha"
                                   class="form-control form-control-sm @error('fecha') is-invalid @enderror"
                                   value="{{ old('fecha', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}"
                                   required>
                            @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tipo de incidente --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                Tipo de Incidente <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2" x-data="{ tipo: '{{ old('tipo') }}' }">
                                @foreach($tipos as $key => $ti)
                                <div class="col-6 col-sm-3">
                                    <label class="d-block cursor-pointer">
                                        <input type="radio" name="tipo" value="{{ $key }}"
                                               class="d-none"
                                               x-model="tipo"
                                               {{ old('tipo') === $key ? 'checked' : '' }}>
                                        <div class="card border-2 text-center py-2 px-1"
                                             :class="tipo === '{{ $key }}' ? 'border-warning shadow-sm' : 'border-light'"
                                             style="cursor:pointer;transition:all .15s;">
                                            <i class="bi {{ $ti['icon'] }} fs-5 mb-1"
                                               style="color:{{ $ti['color'] }};"></i>
                                            <div class="small fw-semibold" style="color:{{ $ti['color'] }};">
                                                {{ $ti['label'] }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @error('tipo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Descripción --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                Descripción del Incidente <span class="text-danger">*</span>
                            </label>
                            <textarea name="descripcion" rows="4"
                                      class="form-control form-control-sm @error('descripcion') is-invalid @enderror"
                                      placeholder="Describa con detalle qué ocurrió, cómo y cuándo…"
                                      required>{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Acción tomada --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                Acción Tomada <span class="text-danger">*</span>
                            </label>
                            <textarea name="accion_tomada" rows="3"
                                      class="form-control form-control-sm @error('accion_tomada') is-invalid @enderror"
                                      placeholder="Ej: Se aplicaron primeros auxilios, se llamó al representante, se administró medicamento…"
                                      required>{{ old('accion_tomada') }}</textarea>
                            @error('accion_tomada')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Remitido a --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Remitido a (opcional)</label>
                            <input type="text" name="remitido_a"
                                   class="form-control form-control-sm @error('remitido_a') is-invalid @enderror"
                                   value="{{ old('remitido_a') }}"
                                   placeholder="Ej: Hospital, Clínica, Médico de guardia…">
                            @error('remitido_a')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.salud.incidentes') }}" class="btn btn-outline-secondary btn-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning btn-sm px-4">
                        <i class="bi bi-floppy me-1"></i>Guardar Incidente
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
