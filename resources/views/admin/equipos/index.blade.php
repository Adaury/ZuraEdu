@extends('layouts.admin')
@section('page-title', 'Equipos — Inventario')

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="bi bi-laptop text-primary me-2"></i>Equipos Tecnológicos
            </h4>
            <small class="text-muted">Inventario y control de estado de equipos</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.equipos.prestamos.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i>Ver Préstamos
            </a>
            <a href="{{ route('admin.equipos.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Equipo
            </a>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-pc-display" style="color:#3b82f6;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalEquipos }}</div>
                        <div class="small text-muted">Total Equipos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle" style="color:#10b981;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalDisponibles }}</div>
                        <div class="small text-muted">Disponibles</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right" style="color:#6366f1;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalPrestados }}</div>
                        <div class="small text-muted">Prestados</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-tools" style="color:#f59e0b;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalMantenimiento }}</div>
                        <div class="small text-muted">Mantenimiento</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #dc2626 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bookmark-check" style="color:#dc2626;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $prestamosActivos }}</div>
                        <div class="small text-muted">Préstamos Activos</div>
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
                <div class="col-sm-4">
                    <label class="form-label form-label-sm mb-1">Buscar</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           value="{{ request('q') }}" placeholder="Nombre o código...">
                </div>
                <div class="col-sm-3">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $key => $label)
                        <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3">
                    <label class="form-label form-label-sm mb-1">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los estados</option>
                        @foreach($estados as $key => $label)
                        <option value="{{ $key }}" {{ request('estado') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['q','tipo','estado']))
                    <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de equipos --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($equipos->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-laptop" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">No hay equipos registrados.</p>
                <a href="{{ route('admin.equipos.create') }}" class="btn btn-primary btn-sm mt-2">
                    Registrar el primer equipo
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Código</th>
                            <th class="text-center">Estado</th>
                            <th>Descripción</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipos as $equipo)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $equipo->nombre }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $equipo->etiqueta_tipo }}</span>
                            </td>
                            <td>
                                <code class="text-muted small">{{ $equipo->codigo ?? '—' }}</code>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $equipo->badge_estado }}">
                                    {{ $equipo->etiqueta_estado }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $equipo->descripcion ? \Illuminate\Support\Str::limit($equipo->descripcion, 60) : '—' }}
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    @if($equipo->estado === 'disponible')
                                    <a href="{{ route('admin.equipos.prestamos.create') }}?equipo_id={{ $equipo->id }}"
                                       class="btn btn-sm btn-outline-success" title="Prestar equipo">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('admin.equipos.edit', $equipo) }}"
                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.equipos.destroy', $equipo) }}"
                                          onsubmit="return confirm('¿Eliminar el equipo «{{ addslashes($equipo->nombre) }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($equipos->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $equipos->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>
@endsection
