@extends('layouts.admin')

@section('page-title', 'Franjas Horarias')

@section('content')
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios', 'url' => route('admin.horarios.index')],
    ['label' => 'Franjas Horarias'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold" style="color: var(--primary);">
        <i class="bi bi-clock me-2"></i>Franjas Horarias
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaFranja">
        <i class="bi bi-plus-lg me-1"></i>Nueva Franja
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

<div class="alert alert-info d-flex align-items-start gap-2 border-0" role="alert">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong>Nota:</strong> Las franjas marcadas como <em>recreo</em> no se utilizan para asignar clases.
        Son visibles en el horario pero no generan asignaciones académicas.
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Nombre / Etiqueta</th>
                        <th>Recreo</th>
                        <th>Activa</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($franjas as $franja)
                        <tr class="{{ $franja->es_recreo ? 'table-warning' : '' }}">
                            <td class="ps-4 fw-bold text-muted">{{ $franja->numero }}</td>
                            <td>
                                <i class="bi bi-clock me-1 text-muted"></i>
                                {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                            </td>
                            <td>
                                <i class="bi bi-clock-history me-1 text-muted"></i>
                                {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                            </td>
                            <td>
                                @if($franja->nombre)
                                    <span class="fw-semibold">{{ $franja->nombre }}</span>
                                @else
                                    <span class="text-muted fst-italic">Sin etiqueta</span>
                                @endif
                            </td>
                            <td>
                                @if($franja->es_recreo)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-cup-hot me-1"></i>Recreo
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">Clase</span>
                                @endif
                            </td>
                            <td>
                                @if($franja->activa)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary me-1"
                                    title="Editar franja"
                                    onclick="abrirEditar({{ $franja->id }}, {{ $franja->numero }}, '{{ $franja->hora_inicio }}', '{{ $franja->hora_fin }}', '{{ addslashes($franja->nombre ?? '') }}', {{ $franja->es_recreo ? 'true' : 'false' }}, {{ $franja->activa ? 'true' : 'false' }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form
                                    action="{{ route('admin.horarios.franjas.destroy', $franja) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Eliminar la franja {{ $franja->numero }}{{ $franja->nombre ? ' («' . addslashes($franja->nombre) . '»)' : '' }}? Esta acción no se puede deshacer.')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar franja">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-clock display-6 d-block mb-2"></i>
                                No hay franjas horarias configuradas. Crea la primera con el botón "Nueva Franja".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===================== MODAL: Nueva Franja ===================== --}}
<div class="modal fade" id="modalNuevaFranja" tabindex="-1" aria-labelledby="modalNuevaFranjaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.horarios.franjas.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalNuevaFranjaLabel">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Franja Horaria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Número de franja <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                name="numero"
                                class="form-control @error('numero') is-invalid @enderror"
                                value="{{ old('numero') }}"
                                min="1"
                                max="20"
                                placeholder="Ej: 1"
                                required
                            >
                            <div class="form-text">Orden de la franja dentro del día escolar.</div>
                            @error('numero')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">
                                Hora inicio <span class="text-danger">*</span>
                            </label>
                            <input
                                type="time"
                                name="hora_inicio"
                                class="form-control @error('hora_inicio') is-invalid @enderror"
                                value="{{ old('hora_inicio') }}"
                                required
                            >
                            @error('hora_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">
                                Hora fin <span class="text-danger">*</span>
                            </label>
                            <input
                                type="time"
                                name="hora_fin"
                                class="form-control @error('hora_fin') is-invalid @enderror"
                                value="{{ old('hora_fin') }}"
                                required
                            >
                            @error('hora_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre / Etiqueta</label>
                            <input
                                type="text"
                                name="nombre"
                                class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}"
                                placeholder="Ej: Primera hora, Recreo grande... (opcional)"
                            >
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="es_recreo"
                                    id="esRecreoCheck"
                                    value="1"
                                    {{ old('es_recreo') ? 'checked' : '' }}
                                >
                                <label class="form-check-label fw-semibold" for="esRecreoCheck">
                                    Es recreo / descanso
                                </label>
                            </div>
                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-flex align-items-center gap-2 small" role="alert">
                                <i class="bi bi-exclamation-circle flex-shrink-0"></i>
                                Las franjas de recreo <strong>no se usan para asignar clases</strong>.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i>Guardar Franja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ===================== MODAL: Editar Franja ===================== --}}
<div class="modal fade" id="modalEditarFranja" tabindex="-1" aria-labelledby="modalEditarFranjaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formEditarFranja" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalEditarFranjaLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Franja Horaria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Número de franja <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="numero" id="editNumero"
                                class="form-control" min="1" max="20" required>
                            <div class="form-text">Orden de la franja dentro del día escolar.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">
                                Hora inicio <span class="text-danger">*</span>
                            </label>
                            <input type="time" name="hora_inicio" id="editHoraInicio"
                                class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">
                                Hora fin <span class="text-danger">*</span>
                            </label>
                            <input type="time" name="hora_fin" id="editHoraFin"
                                class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre / Etiqueta</label>
                            <input type="text" name="nombre" id="editNombre"
                                class="form-control" placeholder="Ej: Primera hora, Recreo... (opcional)" maxlength="50">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    name="es_recreo" id="editEsRecreo" value="1">
                                <label class="form-check-label fw-semibold" for="editEsRecreo">
                                    Es recreo / descanso
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    name="activa" id="editActiva" value="1">
                                <label class="form-check-label fw-semibold" for="editActiva">
                                    Franja activa
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
                        <i class="bi bi-floppy me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    @if($errors->any())
        const createModal = new bootstrap.Modal(document.getElementById('modalNuevaFranja'));
        createModal.show();
    @endif

    const editModal = new bootstrap.Modal(document.getElementById('modalEditarFranja'));

    function abrirEditar(id, numero, horaInicio, horaFin, nombre, esRecreo, activa) {
        const form = document.getElementById('formEditarFranja');
        form.action = '{{ url("admin/horarios/config/franjas") }}/' + id;

        document.getElementById('editNumero').value     = numero;
        document.getElementById('editHoraInicio').value = horaInicio.substring(0, 5);
        document.getElementById('editHoraFin').value    = horaFin.substring(0, 5);
        document.getElementById('editNombre').value     = nombre;
        document.getElementById('editEsRecreo').checked = esRecreo;
        document.getElementById('editActiva').checked   = activa;

        editModal.show();
    }
</script>
@endpush
