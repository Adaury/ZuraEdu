@extends('layouts.admin')
@section('page-title', isset($reunion) ? 'Editar Reunión' : 'Nueva Reunión')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-{{ isset($reunion) ? 'pencil-square' : 'calendar-plus' }} me-2"></i>
            {{ isset($reunion) ? 'Editar Reunión' : 'Nueva Reunión' }}
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ isset($reunion) ? 'Modifica los datos de la reunión.' : 'Registra una nueva reunión institucional.' }}
        </p>
    </div>
    <a href="{{ route('admin.reuniones.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($reunion)
                  ? route('admin.reuniones.update', $reunion)
                  : route('admin.reuniones.store') }}">
            @csrf
            @if(isset($reunion)) @method('PUT') @endif

            <div class="row g-3">
                {{-- Título --}}
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Título <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $reunion->titulo ?? '') }}"
                           placeholder="Ej. Reunión mensual de docentes – Abril 2026" required>
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tipo --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Tipo <span class="text-danger">*</span>
                    </label>
                    <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                        @foreach($tipos as $val => $lbl)
                            <option value="{{ $val }}"
                                @selected(old('tipo', $reunion->tipo ?? 'otra') === $val)>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Estado --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Estado <span class="text-danger">*</span>
                    </label>
                    <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                        @foreach($estados as $val => $lbl)
                            <option value="{{ $val }}"
                                @selected(old('estado', $reunion->estado ?? 'programada') === $val)>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Convocante --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Convocante</label>
                    <select name="convocante_id" class="form-select form-select @error('convocante_id') is-invalid @enderror">
                        <option value="">— Sin especificar —</option>
                        @foreach($convocantes as $u)
                            <option value="{{ $u->id }}"
                                @selected(old('convocante_id', $reunion->convocante_id ?? '') == $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('convocante_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fecha --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Fecha y hora <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" name="fecha"
                           class="form-control @error('fecha') is-invalid @enderror"
                           value="{{ old('fecha', isset($reunion) ? $reunion->fecha->format('Y-m-d\TH:i') : '') }}"
                           required>
                    @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Lugar --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Lugar</label>
                    <input type="text" name="lugar"
                           class="form-control @error('lugar') is-invalid @enderror"
                           value="{{ old('lugar', $reunion->lugar ?? '') }}"
                           placeholder="Ej. Sala de reuniones, Aula 5…">
                    @error('lugar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Agenda --}}
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Agenda</label>
                    <textarea name="agenda" rows="5"
                              class="form-control @error('agenda') is-invalid @enderror"
                              placeholder="Puntos a tratar, uno por línea…">{{ old('agenda', $reunion->agenda ?? '') }}</textarea>
                    @error('agenda')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Participantes --}}
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Participantes
                        <small class="text-muted fw-normal">(listado libre)</small>
                    </label>
                    <textarea name="participantes" rows="4"
                              class="form-control @error('participantes') is-invalid @enderror"
                              placeholder="Ej. Directora, Coord. Académica, Docentes del primer ciclo…">{{ old('participantes', $reunion->participantes ?? '') }}</textarea>
                    @error('participantes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Botones --}}
                <div class="col-12 d-flex gap-2 justify-content-end border-top pt-3 mt-1">
                    <a href="{{ route('admin.reuniones.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($reunion) ? 'Guardar cambios' : 'Crear Reunión' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
