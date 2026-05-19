@extends('layouts.admin')
@section('page-title', 'Conceptos de Pago')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }
    .table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
    .table-card table { margin:0; }
    .table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
    .table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
    .table-card tbody tr:last-child td { border-bottom:none; }
    .table-card tbody tr:hover { background:#f9fafb; }
    .tipo-badge { display:inline-block; border-radius:20px; padding:.2rem .65rem; font-size:.72rem; font-weight:700; }
    .modal-content { border-radius:14px; border:none; box-shadow:0 16px 48px rgba(0,0,0,.15); }
    .modal-header { border-bottom:1px solid #f3f4f6; padding:1rem 1.25rem; }
    .modal-footer { border-top:1px solid #f3f4f6; padding:.75rem 1.25rem; }

    [data-theme="dark"] .table-card { background:#1e293b !important; border-color:#334155 !important; }
    [data-theme="dark"] .table-card thead th { background:#1e3a8a !important; border-color:#334155 !important; color:#93c5fd !important; }
    [data-theme="dark"] .table-card tbody td { border-color:#334155 !important; color:#e2e8f0 !important; }
    [data-theme="dark"] .table-card tbody tr:hover { background:#334155 !important; }
    [data-theme="dark"] .modal-content { background:#1e293b !important; }
    [data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer { border-color:#334155 !important; }
</style>
@endpush

@section('content')

<div class="page-header p-slide-up">
    <h1>
        <a href="{{ route('admin.pagos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <i class="bi bi-tags me-2" style="color:var(--primary)"></i>Conceptos de Pago
    </h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Concepto
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info border-0 mb-3" style="border-radius:10px;background:#eff6ff;font-size:.83rem;">
    <i class="bi bi-info-circle me-2 text-primary"></i>
    Los conceptos predefinidos aparecen como opciones al crear pagos individuales y al generar cuotas masivas, autocompletando el concepto y el monto por defecto.
</div>

<div class="table-card p-slide-up p-delay-1">
    @if($conceptos->isEmpty())
        <div class="text-center py-5 px-3">
            <i class="bi bi-tags" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
            <h6 class="fw-semibold text-muted">No hay conceptos definidos</h6>
            <p class="text-muted mb-3" style="font-size:.83rem;">Crea conceptos de pago reutilizables como "Colegiatura Mensual", "Inscripción", etc.</p>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                <i class="bi bi-plus-lg me-1"></i>Crear primer concepto
            </button>
        </div>
    @else
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Monto Defecto</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($conceptos as $cp)
                <tr>
                    <td class="fw-semibold">{{ $cp->nombre }}</td>
                    <td>
                        <span class="tipo-badge"
                              style="background:{{ $cp->tipo_badge_color }};color:{{ $cp->tipo_text_color }};">
                            {{ $cp->tipo_label }}
                        </span>
                    </td>
                    <td>
                        @if($cp->monto_defecto)
                            <span style="font-family:monospace;font-weight:700;color:#1d4ed8;">
                                RD$ {{ number_format($cp->monto_defecto, 2) }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">{{ $cp->descripcion ?? '—' }}</td>
                    <td>
                        @if($cp->activo)
                            <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:.2rem .6rem;font-size:.72rem;font-weight:700;">Activo</span>
                        @else
                            <span style="background:#f3f4f6;color:#6b7280;border-radius:20px;padding:.2rem .6rem;font-size:.72rem;font-weight:700;">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary py-1"
                                    style="font-size:.75rem;"
                                    onclick="abrirEditar({{ $cp->id }}, '{{ addslashes($cp->nombre) }}', '{{ $cp->tipo }}', '{{ $cp->monto_defecto ?? '' }}', '{{ addslashes($cp->descripcion ?? '') }}', {{ $cp->activo ? 'true' : 'false' }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.pagos.conceptos.destroy', $cp) }}"
                                  onsubmit="return confirm('¿Eliminar este concepto?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1" style="font-size:.75rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Modal: Nuevo Concepto --}}
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-tags me-2"></i>Nuevo Concepto de Pago
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.pagos.conceptos.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" style="border-radius:8px;font-size:.875rem;"
                               placeholder="Ej: Colegiatura Mensual" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Tipo *</label>
                            <select name="tipo" class="form-select" style="border-radius:8px;font-size:.875rem;" required>
                                <option value="mensualidad">Mensualidad</option>
                                <option value="inscripcion">Inscripción</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Monto por defecto (RD$)</label>
                            <input type="number" name="monto_defecto" class="form-control" style="border-radius:8px;font-size:.875rem;"
                                   step="0.01" min="0" placeholder="Opcional">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"
                                  style="border-radius:8px;font-size:.875rem;"
                                  placeholder="Descripción opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Crear Concepto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Editar Concepto --}}
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-pencil me-2"></i>Editar Concepto
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditar" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Nombre *</label>
                        <input type="text" id="editNombre" name="nombre" class="form-control"
                               style="border-radius:8px;font-size:.875rem;" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Tipo *</label>
                            <select id="editTipo" name="tipo" class="form-select" style="border-radius:8px;font-size:.875rem;" required>
                                <option value="mensualidad">Mensualidad</option>
                                <option value="inscripcion">Inscripción</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Monto por defecto (RD$)</label>
                            <input type="number" id="editMonto" name="monto_defecto" class="form-control"
                                   style="border-radius:8px;font-size:.875rem;" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Descripción</label>
                        <textarea id="editDescripcion" name="descripcion" class="form-control" rows="2"
                                  style="border-radius:8px;font-size:.875rem;"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="editActivo" name="activo" value="1">
                        <label class="form-check-label" for="editActivo" style="font-size:.83rem;font-weight:600;">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function abrirEditar(id, nombre, tipo, monto, descripcion, activo) {
    const baseUrl = '{{ url("admin/pagos/conceptos") }}';
    document.getElementById('formEditar').action = `${baseUrl}/${id}`;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editTipo').value = tipo;
    document.getElementById('editMonto').value = monto || '';
    document.getElementById('editDescripcion').value = descripcion;
    document.getElementById('editActivo').checked = activo;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
@endpush
