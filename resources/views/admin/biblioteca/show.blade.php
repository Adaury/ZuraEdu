@extends('layouts.admin')
@section('page-title', 'Biblioteca — ' . $libro->titulo)

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <a href="{{ route('admin.biblioteca.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <h4 class="mb-0 fw-bold">
                <i class="bi bi-book-half text-primary me-2"></i>{{ $libro->titulo }}
            </h4>
            <small class="text-muted">{{ $libro->autor }} — {{ $libro->categoria }}</small>
        </div>
        <div class="d-flex gap-2">
            @if($libro->cantidad_disponible > 0)
            <a href="{{ route('admin.biblioteca.prestamos.create') }}?libro_id={{ $libro->id }}"
               class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Prestar
            </a>
            @endif
            <a href="{{ route('admin.biblioteca.libros.edit', $libro) }}"
               class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
        </div>
    </div>

    {{-- Alertas --}}
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

    <div class="row g-3">

        {{-- Info del libro --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.06em;">
                        Información del Libro
                    </h6>

                    @if($libro->isbn)
                    <div class="mb-2">
                        <small class="text-muted d-block">ISBN</small>
                        <span class="fw-semibold font-monospace">{{ $libro->isbn }}</span>
                    </div>
                    @endif

                    <div class="mb-2">
                        <small class="text-muted d-block">Categoría</small>
                        <span class="badge bg-light text-dark border">{{ $libro->categoria }}</span>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted d-block">Descripción</small>
                        <span class="small text-secondary">{{ $libro->descripcion ?: '—' }}</span>
                    </div>

                    <hr>

                    {{-- Disponibilidad --}}
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 rounded-3 bg-light">
                                <div class="fw-bold fs-4">{{ $libro->cantidad_total }}</div>
                                <div class="small text-muted">Total</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-3 {{ $libro->cantidad_disponible > 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                                <div class="fw-bold fs-4 {{ $libro->cantidad_disponible > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $libro->cantidad_disponible }}
                                </div>
                                <div class="small text-muted">Disponibles</div>
                            </div>
                        </div>
                    </div>

                    @if($libro->cantidad_disponible <= 0)
                    <div class="alert alert-danger py-2 mt-3 mb-0 text-center small">
                        <i class="bi bi-exclamation-triangle me-1"></i>Sin ejemplares disponibles
                    </div>
                    @elseif($libro->cantidad_disponible <= 2)
                    <div class="alert alert-warning py-2 mt-3 mb-0 text-center small">
                        <i class="bi bi-exclamation-circle me-1"></i>Pocos ejemplares disponibles
                    </div>
                    @endif

                    <hr>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="fw-bold text-primary">{{ $prestamosActivos->count() }}</div>
                            <div class="small text-muted">Activos</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-danger">{{ $prestamosVencidos->count() }}</div>
                            <div class="small text-muted">Vencidos</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-success">{{ $historial->count() }}</div>
                            <div class="small text-muted">Devueltos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Préstamos --}}
        <div class="col-md-8">

            {{-- Activos + Vencidos --}}
            @if($prestamosActivos->isNotEmpty() || $prestamosVencidos->isNotEmpty())
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-bookmark-check text-primary me-2"></i>
                        Préstamos Activos
                        @php $pendientes = $prestamosActivos->count() + $prestamosVencidos->count(); @endphp
                        @if($pendientes > 0)
                        <span class="badge bg-primary ms-1">{{ $pendientes }}</span>
                        @endif
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Estudiante</th>
                                <th class="text-center">Préstamo</th>
                                <th class="text-center">Vencimiento</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($prestamosActivos->merge($prestamosVencidos)->sortBy('fecha_vencimiento') as $prestamo)
                        <tr class="{{ $prestamo->estado === 'vencido' ? 'table-danger' : '' }}">
                            <td class="fw-semibold">
                                {{ $prestamo->estudiante?->nombre_completo ?? '—' }}
                            </td>
                            <td class="text-center small">{{ $prestamo->fecha_prestamo?->format('d/m/Y') }}</td>
                            <td class="text-center small {{ $prestamo->fecha_vencimiento < now() ? 'text-danger fw-bold' : '' }}">
                                {{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}
                                @if($prestamo->fecha_vencimiento < now())
                                <br><span class="badge bg-danger" style="font-size:.65rem;">
                                    {{ now()->diffInDays($prestamo->fecha_vencimiento) }}d vencido
                                </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $prestamo->badge_estado }}">{{ ucfirst($prestamo->estado) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    {{-- Devolver --}}
                                    <form method="POST"
                                          action="{{ route('admin.biblioteca.prestamos.devolver', $prestamo) }}"
                                          onsubmit="return confirm('¿Confirmar devolución?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="Devolver">
                                            <i class="bi bi-arrow-return-left"></i>
                                        </button>
                                    </form>
                                    {{-- Renovar --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            title="Renovar"
                                            onclick="abrirRenovar({{ $prestamo->id }}, '{{ $prestamo->fecha_vencimiento?->format('Y-m-d') }}')">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Historial devueltos --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-clock-history text-success me-2"></i>Historial de Devoluciones
                    </h6>
                    <small class="text-muted">Últimos {{ $historial->count() }} registros</small>
                </div>
                @if($historial->isEmpty())
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-inbox d-block" style="font-size:2rem;"></i>
                    Sin devoluciones registradas aún.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Estudiante</th>
                                <th class="text-center">Préstamo</th>
                                <th class="text-center">Vencimiento</th>
                                <th class="text-center">Devolución</th>
                                <th class="text-center">Tiempo</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($historial as $prestamo)
                        @php
                            $diasTardanza = $prestamo->fecha_devolucion && $prestamo->fecha_vencimiento
                                ? $prestamo->fecha_devolucion->diffInDays($prestamo->fecha_vencimiento, false)
                                : null;
                        @endphp
                        <tr>
                            <td class="small fw-semibold">{{ $prestamo->estudiante?->nombre_completo ?? '—' }}</td>
                            <td class="text-center small">{{ $prestamo->fecha_prestamo?->format('d/m/Y') }}</td>
                            <td class="text-center small">{{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}</td>
                            <td class="text-center small">{{ $prestamo->fecha_devolucion?->format('d/m/Y') }}</td>
                            <td class="text-center">
                                @if($diasTardanza !== null)
                                    @if($diasTardanza >= 0)
                                        <span class="badge bg-success">A tiempo</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ abs($diasTardanza) }}d tarde</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

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
                    <input type="date" name="nueva_fecha" id="inputNuevaFecha"
                           class="form-control" required>
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
    // Sugerir 14 días más
    const d = new Date();
    d.setDate(d.getDate() + 14);
    input.value = d.toISOString().split('T')[0];
    input.min = new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('modalRenovar')).show();
}
</script>
@endpush
@endsection
