@extends('layouts.admin')
@section('page-title', 'Equipos — Préstamos')

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Préstamos de Equipos
            </h4>
            <small class="text-muted">Control de préstamos y devoluciones</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.equipos.prestamos.excel', request()->query()) }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            {{-- Verificar vencidos --}}
            <form method="POST" action="{{ route('admin.equipos.verificar-vencidos') }}">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm"
                        onclick="return confirm('¿Verificar y marcar préstamos vencidos?')">
                    <i class="bi bi-clock-history me-1"></i>Verificar Vencidos
                </button>
            </form>
            <a href="{{ route('admin.equipos.prestamos.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Préstamo
            </a>
            <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-laptop me-1"></i>Inventario
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
                           value="{{ request('q') }}" placeholder="Usuario, equipo o código...">
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
                    <a href="{{ route('admin.equipos.prestamos.index') }}" class="btn btn-outline-secondary btn-sm">
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
                <i class="bi bi-arrow-left-right" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">No hay préstamos registrados.</p>
                <a href="{{ route('admin.equipos.prestamos.create') }}" class="btn btn-primary btn-sm mt-2">
                    Registrar primer préstamo
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Equipo</th>
                            <th class="text-center">Préstamo</th>
                            <th class="text-center">Vencimiento</th>
                            <th class="text-center">Devolución</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prestamos as $prestamo)
                        <tr class="{{ $prestamo->estado === 'vencido' ? 'table-danger' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $prestamo->usuario?->name ?? '—' }}</div>
                                <small class="text-muted">{{ $prestamo->usuario?->email }}</small>
                            </td>
                            <td>
                                <div>{{ $prestamo->equipo?->nombre ?? '—' }}</div>
                                <small class="text-muted">
                                    {{ $prestamo->equipo?->etiqueta_tipo }}
                                    @if($prestamo->equipo?->codigo)
                                        · <code>{{ $prestamo->equipo->codigo }}</code>
                                    @endif
                                </small>
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
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    {{-- Comprobante PDF --}}
                                    <a href="{{ route('admin.equipos.prestamos.comprobante', $prestamo) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Ver comprobante PDF" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    {{-- Devolver --}}
                                    @if($prestamo->estado !== 'devuelto')
                                    <form method="POST"
                                          action="{{ route('admin.equipos.prestamos.devolver', $prestamo) }}"
                                          onsubmit="return confirm('¿Confirmar devolución del equipo «{{ addslashes($prestamo->equipo?->nombre) }}»?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="Registrar devolución">
                                            <i class="bi bi-arrow-return-left me-1"></i>Devolver
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-muted small">Devuelto</span>
                                    @endif
                                </div>
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
@endsection
