@extends('layouts.admin')

@section('page-title', 'Zonas de Acceso')

@section('content')
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Carnet+', 'url' => route('admin.carnet.index')],
    ['label' => 'Zonas'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold" style="color: var(--primary);">
        <i class="bi bi-geo-alt me-2"></i>Zonas de Acceso
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaZona">
        <i class="bi bi-plus-lg me-1"></i>Nueva Zona
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Zona</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($zonas as $zona)
                        <tr>
                            <td class="ps-4 fw-semibold">
                                <i class="bi {{ \App\Models\CarnetZona::ICONOS[$zona->tipo] ?? 'bi-geo-alt' }} me-2 text-muted"></i>
                                {{ $zona->nombre }}
                            </td>
                            <td>{{ \App\Models\CarnetZona::TIPOS[$zona->tipo] ?? ucfirst($zona->tipo) }}</td>
                            <td>
                                @if($zona->activo)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <form action="{{ route('admin.carnet.zonas.toggle', $zona) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="{{ $zona->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="bi {{ $zona->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.carnet.zonas.destroy', $zona) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar la zona «{{ addslashes($zona->nombre) }}»? Esta acción no se puede deshacer.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                No hay zonas registradas. Crea la primera con el botón "Nueva Zona".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===================== MODAL: Nueva Zona ===================== --}}
<div class="modal fade" id="modalNuevaZona" tabindex="-1" aria-labelledby="modalNuevaZonaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.carnet.zonas.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalNuevaZonaLabel">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Zona
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="nombre"
                            class="form-control @error('nombre') is-invalid @enderror"
                            value="{{ old('nombre') }}"
                            placeholder="Ej: Portería Principal"
                            required
                        >
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                            <option value="">Seleccionar tipo...</option>
                            @foreach(\App\Models\CarnetZona::TIPOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ old('tipo') == $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i>Guardar Zona
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('modalNuevaZona')).show();
    });
</script>
@endif
@endsection
