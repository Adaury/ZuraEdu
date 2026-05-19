@extends('layouts.admin')
@section('page-title', 'Biblioteca — Préstamos')

@push('styles')
<style>
[data-theme="dark"] .card { background:#1e293b !important; border-color:#334155 !important; }
[data-theme="dark"] .card-body { background:#1e293b !important; }
[data-theme="dark"] .table-light, [data-theme="dark"] thead.table-light { background:#1e3a8a !important; }
[data-theme="dark"] thead th { color:#93c5fd !important; border-color:#334155 !important; }
[data-theme="dark"] .table-hover tbody tr:hover { background:#334155 !important; }
[data-theme="dark"] tbody td { border-color:#334155 !important; color:#e2e8f0 !important; }
[data-theme="dark"] .text-muted { color:#94a3b8 !important; }
[data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background:#1e293b; border-color:#334155; color:#e2e8f0; }
[data-theme="dark"] .modal-content { background:#1e293b !important; border-color:#334155 !important; }
[data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer { border-color:#334155 !important; }
[data-theme="dark"] .table-danger { background:rgba(239,68,68,.12) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Préstamos de Biblioteca
            </h4>
            <small class="text-muted">Control de préstamos y devoluciones</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Verificar vencidos --}}
            <form method="POST" action="{{ route('admin.biblioteca.verificar-vencidos') }}">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm"
                        onclick="return confirm('¿Verificar y marcar préstamos vencidos?')">
                    <i class="bi bi-clock-history me-1"></i>Verificar Vencidos
                </button>
            </form>
            <a href="{{ route('admin.biblioteca.prestamos.pdf') }}" target="_blank"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('admin.biblioteca.prestamos.excel') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.biblioteca.prestamos.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Préstamo
            </a>
            <a href="{{ route('admin.biblioteca.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-book me-1"></i>Catálogo
            </a>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bookmark-check" style="color:#3b82f6;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalActivos }}</div>
                        <div class="small text-muted">Activos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalVencidos }}</div>
                        <div class="small text-muted">Vencidos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle" style="color:#10b981;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalDevueltos }}</div>
                        <div class="small text-muted">Devueltos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-5">
                    <label class="form-label form-label-sm mb-1">Buscar</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           value="{{ request('q') }}" placeholder="Estudiante o título del libro...">
                </div>
                <div class="col-sm-3">
                    <label class="form-label form-label-sm mb-1">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activos</option>
                        <option value="vencido"  {{ request('estado') === 'vencido'  ? 'selected' : '' }}>Vencidos</option>
                        <option value="devuelto" {{ request('estado') === 'devuelto' ? 'selected' : '' }}>Devueltos</option>
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['q','estado']))
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de préstamos --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($prestamos->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-left-right" style="font-size:2.5rem;display:block;margin-bottom:.5rem;"></i>
                <p class="mt-2 mb-0">No hay préstamos registrados.</p>
                <a href="{{ route('admin.biblioteca.prestamos.create') }}" class="btn btn-primary btn-sm mt-2">
                    Registrar primer préstamo
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Libro</th>
                            <th class="text-center">Préstamo</th>
                            <th class="text-center">Vencimiento</th>
                            <th class="text-center">Devolución</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Renov.</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prestamos as $prestamo)
                        <tr class="{{ $prestamo->estado === 'vencido' ? 'table-danger' : '' }}">
                            <td>
                                <div class="fw-semibold">
                                    {{ $prestamo->estudiante?->nombre_completo ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $prestamo->libro?->titulo ?? '—' }}</div>
                                <small class="text-muted">{{ $prestamo->libro?->autor }}</small>
                            </td>
                            <td class="text-center">
                                {{ $prestamo->fecha_prestamo?->format('d/m/Y') }}
                            </td>
                            <td class="text-center">
                                <span class="{{ $prestamo->estado !== 'devuelto' && $prestamo->fecha_vencimiento < now() ? 'text-danger fw-bold' : '' }}">
                                    {{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="text-center">
                                {{ $prestamo->fecha_devolucion?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $prestamo->badge_estado }}">
                                    {{ ucfirst($prestamo->estado) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if(($prestamo->renovaciones ?? 0) > 0)
                                <span class="badge bg-secondary">{{ $prestamo->renovaciones }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($prestamo->estado !== 'devuelto')
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST"
                                          action="{{ route('admin.biblioteca.prestamos.devolver', $prestamo) }}"
                                          onsubmit="return confirm('¿Confirmar devolución del libro «{{ addslashes($prestamo->libro?->titulo) }}»?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="Registrar devolución">
                                            <i class="bi bi-arrow-return-left me-1"></i>Devolver
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Renovar préstamo"
                                            onclick="abrirRenovar({{ $prestamo->id }}, '{{ $prestamo->fecha_vencimiento?->format('Y-m-d') }}')">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                @else
                                <span class="text-muted small">Devuelto</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($prestamos->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $prestamos->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>

{{-- Modal renovar préstamo --}}
<div class="modal fade" id="modalRenovar" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-arrow-clockwise text-primary me-2"></i>Renovar Préstamo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRenovar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <label class="form-label fw-semibold">Nueva fecha de vencimiento</label>
                    <input type="date" name="nueva_fecha" id="inputNuevaFecha" class="form-control" required>
                    <small class="text-muted">Debe ser posterior a hoy.</small>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Renovar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirRenovar(prestamoId, fechaActual) {
    const form = document.getElementById('formRenovar');
    form.action = `/admin/biblioteca/prestamos/${prestamoId}/renovar`;
    const input = document.getElementById('inputNuevaFecha');
    const d = new Date();
    d.setDate(d.getDate() + 14);
    input.value = d.toISOString().split('T')[0];
    input.min = new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('modalRenovar')).show();
}
</script>
@endpush
@endsection
