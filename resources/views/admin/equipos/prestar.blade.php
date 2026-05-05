@extends('layouts.admin')
@section('page-title', 'Registrar Préstamo de Equipo')

@section('content')
<div class="container-fluid py-3" style="max-width:760px;">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.equipos.prestamos.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-plus-circle text-primary me-2"></i>Registrar Préstamo de Equipo
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

    @if($equipos->isEmpty())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        No hay equipos disponibles en este momento.
        <a href="{{ route('admin.equipos.index') }}" class="alert-link">Ver inventario</a>
    </div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.equipos.prestamos.store') }}">
                @csrf

                <div class="row g-3">
                    {{-- Equipo --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Equipo <span class="text-danger">*</span></label>
                        <select name="equipo_id"
                                class="form-select @error('equipo_id') is-invalid @enderror"
                                required>
                            <option value="">Seleccionar equipo disponible...</option>
                            @foreach($equipos as $equipo)
                            <option value="{{ $equipo->id }}"
                                    {{ (old('equipo_id', request('equipo_id'))) == $equipo->id ? 'selected' : '' }}>
                                {{ $equipo->nombre }}
                                @if($equipo->codigo) [{{ $equipo->codigo }}] @endif
                                — {{ $equipo->etiqueta_tipo }}
                            </option>
                            @endforeach
                        </select>
                        @error('equipo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Usuario --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                        <select name="usuario_id"
                                class="form-select @error('usuario_id') is-invalid @enderror"
                                required>
                            <option value="">Seleccionar usuario...</option>
                            @foreach($usuarios as $user)
                            <option value="{{ $user->id }}"
                                    {{ old('usuario_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} — {{ $user->email }}
                            </option>
                            @endforeach
                        </select>
                        @error('usuario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                               value="{{ old('fecha_vencimiento', now()->addDays(7)->toDateString()) }}" required>
                        @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Por defecto: 7 días</small>
                    </div>

                    {{-- Motivo --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Motivo / Propósito <span class="text-muted small">(opcional)</span>
                        </label>
                        <textarea name="motivo"
                                  class="form-control @error('motivo') is-invalid @enderror"
                                  rows="2" maxlength="500"
                                  placeholder="Ej: Clase de informática, proyecto de ciencias...">{{ old('motivo') }}</textarea>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Registrar Préstamo
                    </button>
                    <a href="{{ route('admin.equipos.prestamos.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
