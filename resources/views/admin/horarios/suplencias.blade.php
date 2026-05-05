@extends('layouts.admin')

@section('title', 'Suplencias')

@section('content')
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios', 'url' => route('admin.horarios.index')],
    ['label' => 'Suplencias'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold" style="color: var(--primary);">
        <i class="bi bi-person-lines-fill me-2"></i>Suplencias
    </h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.horarios.suplencias.pdf', request()->query()) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.horarios.suplencias.excel', request()->query()) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAusencia">
            <i class="bi bi-plus-lg me-1"></i>Registrar Ausencia
        </button>
    </div>
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

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Fecha</th>
                        <th>Docente Ausente</th>
                        <th>Materia / Grupo</th>
                        <th>Hora</th>
                        <th>Suplente</th>
                        <th>Motivo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suplencias as $suplencia)
                        @php
                            $franja    = $suplencia->detalle->franja ?? null;
                            $asignacion = $suplencia->detalle->asignacion ?? null;
                            $grupo     = $asignacion->grupo ?? null;
                            $asignatura = $asignacion->asignatura ?? null;

                            $estadoConfig = [
                                'pendiente'  => ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split',    'label' => 'Pendiente'],
                                'cubierta'   => ['class' => 'bg-success',           'icon' => 'bi-check-circle',       'label' => 'Cubierta'],
                                'sin_cubrir' => ['class' => 'bg-danger',            'icon' => 'bi-x-circle',           'label' => 'Sin cubrir'],
                                'cancelada'  => ['class' => 'bg-secondary',         'icon' => 'bi-slash-circle',       'label' => 'Cancelada'],
                            ];
                            $estado = $estadoConfig[$suplencia->estado ?? 'pendiente'] ?? $estadoConfig['pendiente'];
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="fw-semibold">
                                    {{ \Carbon\Carbon::parse($suplencia->fecha)->format('d/m/Y') }}
                                </span>
                                <div class="text-muted small">
                                    {{ \Carbon\Carbon::parse($suplencia->fecha)->translatedFormat('l') }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-sm bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                                          style="width:32px;height:32px;">
                                        <i class="bi bi-person-x text-danger" style="font-size:.9rem;"></i>
                                    </span>
                                    <span>{{ $suplencia->docenteOriginal->nombre_completo ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($asignatura)
                                    <span class="fw-semibold">{{ $asignatura->nombre }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if($grupo)
                                    <div class="text-muted small">
                                        <i class="bi bi-people me-1"></i>{{ $grupo->nombre_completo ?? $grupo->nombre }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($franja)
                                    <span class="font-monospace">
                                        {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($suplencia->docenteSuplente)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-sm bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                                              style="width:32px;height:32px;">
                                            <i class="bi bi-person-check text-success" style="font-size:.9rem;"></i>
                                        </span>
                                        <span>{{ $suplencia->docenteSuplente->nombre_completo }}</span>
                                    </div>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>Sin cubrir
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($suplencia->motivo)
                                    <span
                                        class="d-inline-block text-truncate"
                                        style="max-width: 160px;"
                                        title="{{ $suplencia->motivo }}"
                                    >
                                        {{ $suplencia->motivo }}
                                    </span>
                                @else
                                    <span class="text-muted fst-italic">Sin motivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $estado['class'] }}">
                                    <i class="bi {{ $estado['icon'] }} me-1"></i>{{ $estado['label'] }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Editar suplencia"
                                    onclick="abrirEditarSuplencia(
                                        {{ $suplencia->id }},
                                        '{{ $suplencia->estado }}',
                                        {{ $suplencia->docente_suplente_id ?? 'null' }},
                                        '{{ addslashes($suplencia->motivo ?? '') }}'
                                    )">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-check display-6 d-block mb-2"></i>
                                No hay suplencias registradas.
                                <br>
                                <span class="small">Usa el botón "Registrar Ausencia" cuando un docente no pueda asistir.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($suplencias->hasPages())
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Mostrando {{ $suplencias->firstItem() }}–{{ $suplencias->lastItem() }}
                de {{ $suplencias->total() }} suplencias
            </small>
            {{ $suplencias->links() }}
        </div>
    @endif
</div>

{{-- ===================== MODAL: Registrar Ausencia ===================== --}}
<div class="modal fade" id="modalAusencia" tabindex="-1" aria-labelledby="modalAusenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.horarios.suplencias.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalAusenciaLabel">
                        <i class="bi bi-person-dash me-2"></i>Registrar Ausencia Docente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 mb-4 small" role="alert">
                        <i class="bi bi-robot flex-shrink-0 mt-1"></i>
                        <span>
                            El sistema buscará automáticamente un suplente disponible según las franjas horarias
                            afectadas y la disponibilidad registrada de los demás docentes.
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Docente ausente <span class="text-danger">*</span>
                            </label>
                            <select
                                name="docente_id"
                                class="form-select @error('docente_id') is-invalid @enderror"
                                required
                            >
                                <option value="">— Seleccione un docente —</option>
                                @foreach($docentes as $docente)
                                    <option
                                        value="{{ $docente->id }}"
                                        {{ old('docente_id') == $docente->id ? 'selected' : '' }}
                                    >
                                        {{ $docente->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('docente_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Fecha de ausencia <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                name="fecha"
                                class="form-control @error('fecha') is-invalid @enderror"
                                value="{{ old('fecha', now()->format('Y-m-d')) }}"
                                required
                            >
                            @error('fecha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Motivo</label>
                            <textarea
                                name="motivo"
                                class="form-control @error('motivo') is-invalid @enderror"
                                rows="3"
                                placeholder="Ej: Enfermedad, permiso personal, emergencia familiar..."
                            >{{ old('motivo') }}</textarea>
                            @error('motivo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Registrar y buscar suplente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ===================== MODAL: Editar Suplencia ===================== --}}
<div class="modal fade" id="modalEditarSuplencia" tabindex="-1" aria-labelledby="modalEditarSuplenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formEditarSuplencia" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header" style="background: var(--primary); color: #fff;">
                    <h5 class="modal-title" id="modalEditarSuplenciaLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Suplencia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                            <select name="estado" id="editSuplEstado" class="form-select" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="cubierta">Cubierta</option>
                                <option value="sin_cubrir">Sin cubrir</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Docente suplente</label>
                            <select name="docente_suplente_id" id="editSuplSuplente" class="form-select">
                                <option value="">— Sin asignar —</option>
                                @foreach($docentes as $docente)
                                    <option value="{{ $docente->id }}">{{ $docente->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Motivo</label>
                            <textarea name="motivo" id="editSuplMotivo" class="form-control" rows="3"
                                placeholder="Motivo de la ausencia..."></textarea>
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
        const ausenciaModal = new bootstrap.Modal(document.getElementById('modalAusencia'));
        ausenciaModal.show();
    @endif

    const editSuplModal = new bootstrap.Modal(document.getElementById('modalEditarSuplencia'));

    function abrirEditarSuplencia(id, estado, suplenteId, motivo) {
        const form = document.getElementById('formEditarSuplencia');
        form.action = '{{ url("admin/horarios/suplencias") }}/' + id;

        document.getElementById('editSuplEstado').value   = estado;
        document.getElementById('editSuplMotivo').value   = motivo;

        const sel = document.getElementById('editSuplSuplente');
        sel.value = suplenteId ?? '';

        editSuplModal.show();
    }
</script>
@endpush
