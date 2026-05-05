@extends('layouts.admin')

@section('page-title', 'Aulas')

@section('content')
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios', 'url' => route('admin.horarios.index')],
    ['label' => 'Aulas'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold" style="color: var(--primary);">
        <i class="bi bi-door-open me-2"></i>Aulas
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaAula">
        <i class="bi bi-plus-lg me-1"></i>Nueva Aula
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre</th>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Piso</th>
                        <th>Disponible</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aulas as $aula)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $aula->nombre }}</td>
                            <td>
                                <span class="font-monospace text-muted">{{ $aula->codigo }}</span>
                            </td>
                            <td>
                                @php
                                    $tipoClasses = [
                                        'aula'       => 'bg-primary',
                                        'laboratorio'=> 'bg-info text-dark',
                                        'taller'     => 'bg-warning text-dark',
                                        'gimnasio'   => 'bg-success',
                                        'biblioteca' => 'bg-secondary',
                                    ];
                                    $tipoLabels = [
                                        'aula'       => 'Aula',
                                        'laboratorio'=> 'Laboratorio',
                                        'taller'     => 'Taller',
                                        'gimnasio'   => 'Gimnasio',
                                        'biblioteca' => 'Biblioteca',
                                    ];
                                    $cls = $tipoClasses[$aula->tipo] ?? 'bg-secondary';
                                    $lbl = $tipoLabels[$aula->tipo] ?? ucfirst($aula->tipo);
                                @endphp
                                <span class="badge {{ $cls }}">{{ $lbl }}</span>
                            </td>
                            <td>
                                <i class="bi bi-people me-1 text-muted"></i>{{ $aula->capacidad }}
                            </td>
                            <td>{{ $aula->piso ?? '—' }}</td>
                            <td>
                                @if($aula->disponible)
                                    <span class="badge bg-success">Disponible</span>
                                @else
                                    <span class="badge bg-danger">No disponible</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary me-1"
                                    onclick="abrirEditar(
                                        {{ $aula->id }},
                                        '{{ addslashes($aula->nombre) }}',
                                        '{{ addslashes($aula->codigo) }}',
                                        {{ $aula->capacidad }},
                                        '{{ $aula->tipo }}',
                                        '{{ $aula->piso }}',
                                        {{ $aula->disponible ? 'true' : 'false' }}
                                    )"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarAula"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form
                                    action="{{ route('admin.horarios.aulas.destroy', $aula) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Eliminar el aula «{{ addslashes($aula->nombre) }}»? Esta acción no se puede deshacer.')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                No hay aulas registradas. Crea la primera con el botón "Nueva Aula".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($aulas->hasPages())
        <div class="card-footer bg-transparent d-flex justify-content-end">
            {{ $aulas->links() }}
        </div>
    @endif
</div>

{{-- ===================== MODAL: Nueva Aula ===================== --}}
<div class="modal fade" id="modalNuevaAula" tabindex="-1" aria-labelledby="modalNuevaAulaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.horarios.aulas.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalNuevaAulaLabel">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Aula
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="nombre"
                                class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}"
                                placeholder="Ej: Aula 101"
                                required
                            >
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="codigo"
                                class="form-control @error('codigo') is-invalid @enderror"
                                value="{{ old('codigo') }}"
                                placeholder="Ej: A-101"
                                required
                            >
                            @error('codigo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Capacidad <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                name="capacidad"
                                class="form-control @error('capacidad') is-invalid @enderror"
                                value="{{ old('capacidad', 30) }}"
                                min="1"
                                max="999"
                                required
                            >
                            @error('capacidad')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="aula"        {{ old('tipo') == 'aula'        ? 'selected' : '' }}>Aula</option>
                                <option value="laboratorio" {{ old('tipo') == 'laboratorio' ? 'selected' : '' }}>Laboratorio</option>
                                <option value="taller"      {{ old('tipo') == 'taller'      ? 'selected' : '' }}>Taller</option>
                                <option value="gimnasio"    {{ old('tipo') == 'gimnasio'    ? 'selected' : '' }}>Gimnasio</option>
                                <option value="biblioteca"  {{ old('tipo') == 'biblioteca'  ? 'selected' : '' }}>Biblioteca</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Piso</label>
                            <input
                                type="text"
                                name="piso"
                                class="form-control @error('piso') is-invalid @enderror"
                                value="{{ old('piso') }}"
                                placeholder="Ej: Planta baja"
                            >
                            @error('piso')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="disponible"
                                    id="disponibleNueva"
                                    value="1"
                                    {{ old('disponible', true) ? 'checked' : '' }}
                                >
                                <label class="form-check-label fw-semibold" for="disponibleNueva">
                                    Disponible para asignación
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i>Guardar Aula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== MODAL: Editar Aula ===================== --}}
<div class="modal fade" id="modalEditarAula" tabindex="-1" aria-labelledby="modalEditarAulaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="formEditarAula" action="" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalEditarAulaLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Aula
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="nombre"
                                id="editNombre"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="codigo"
                                id="editCodigo"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Capacidad <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                name="capacidad"
                                id="editCapacidad"
                                class="form-control"
                                min="1"
                                max="999"
                                required
                            >
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" id="editTipo" class="form-select" required>
                                <option value="aula">Aula</option>
                                <option value="laboratorio">Laboratorio</option>
                                <option value="taller">Taller</option>
                                <option value="gimnasio">Gimnasio</option>
                                <option value="biblioteca">Biblioteca</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Piso</label>
                            <input
                                type="text"
                                name="piso"
                                id="editPiso"
                                class="form-control"
                            >
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="disponible"
                                    id="editDisponible"
                                    value="1"
                                >
                                <label class="form-check-label fw-semibold" for="editDisponible">
                                    Disponible para asignación
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i>Actualizar Aula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function abrirEditar(id, nombre, codigo, capacidad, tipo, piso, disponible) {
        const baseUrl = '{{ route('admin.horarios.aulas.update', ':id') }}'.replace(':id', id);
        document.getElementById('formEditarAula').action = baseUrl;

        document.getElementById('editNombre').value    = nombre;
        document.getElementById('editCodigo').value    = codigo;
        document.getElementById('editCapacidad').value = capacidad;
        document.getElementById('editPiso').value      = piso !== 'null' ? piso : '';

        const tipoSelect = document.getElementById('editTipo');
        for (let opt of tipoSelect.options) {
            opt.selected = (opt.value === tipo);
        }

        document.getElementById('editDisponible').checked = disponible;
    }

    @if($errors->any() && old('_method') === 'PUT')
        const editModal = new bootstrap.Modal(document.getElementById('modalEditarAula'));
        editModal.show();
    @elseif($errors->any())
        const createModal = new bootstrap.Modal(document.getElementById('modalNuevaAula'));
        createModal.show();
    @endif
</script>
@endpush
