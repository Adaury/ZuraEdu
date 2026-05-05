@extends('layouts.admin')
@section('page-title', 'Biblioteca Escolar — Libros')

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="bi bi-book-half text-primary me-2"></i>Biblioteca Escolar
            </h4>
            <small class="text-muted">Catálogo de libros y control de inventario</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i>Ver Préstamos
            </a>
            <a href="{{ route('admin.biblioteca.libros.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Libro
            </a>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-journals" style="color:#3b82f6;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalLibros }}</div>
                        <div class="small text-muted">Total Libros</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle" style="color:#10b981;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalDisponibles }}</div>
                        <div class="small text-muted">Con Disponibles</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444 !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle" style="color:#ef4444;font-size:1.4rem;"></i>
                    <div>
                        <div class="fw-bold fs-5">{{ $totalAgotados }}</div>
                        <div class="small text-muted">Agotados</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b !important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right" style="color:#f59e0b;font-size:1.4rem;"></i>
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
                           value="{{ request('q') }}" placeholder="Título, autor o ISBN...">
                </div>
                <div class="col-sm-3">
                    <label class="form-label form-label-sm mb-1">Categoría</label>
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $key => $label)
                        <option value="{{ $key }}" {{ request('categoria') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3">
                    <label class="form-label form-label-sm mb-1">Disponibilidad</label>
                    <select name="disponibilidad" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="disponible" {{ request('disponibilidad') === 'disponible' ? 'selected' : '' }}>Con ejemplares</option>
                        <option value="agotado" {{ request('disponibilidad') === 'agotado' ? 'selected' : '' }}>Agotados</option>
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['q','categoria','disponibilidad']))
                    <a href="{{ route('admin.biblioteca.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de libros --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($libros->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-book" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">No hay libros registrados.</p>
                <a href="{{ route('admin.biblioteca.libros.create') }}" class="btn btn-primary btn-sm mt-2">
                    Registrar el primer libro
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoría</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Disponibles</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($libros as $libro)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $libro->titulo }}</div>
                                @if($libro->isbn)
                                <small class="text-muted">ISBN: {{ $libro->isbn }}</small>
                                @endif
                            </td>
                            <td>{{ $libro->autor }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $libro->categoria }}</span>
                            </td>
                            <td class="text-center">{{ $libro->cantidad_total }}</td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $libro->cantidad_disponible }}</span>
                            </td>
                            <td class="text-center">
                                @if($libro->cantidad_disponible <= 0)
                                    <span class="badge bg-danger">Agotado</span>
                                @elseif($libro->cantidad_disponible <= 2)
                                    <span class="badge bg-warning text-dark">Poco stock</span>
                                @else
                                    <span class="badge bg-success">Disponible</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.biblioteca.libros.edit', $libro) }}"
                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.biblioteca.libros.destroy', $libro) }}"
                                          onsubmit="return confirm('¿Eliminar el libro «{{ addslashes($libro->titulo) }}»?')">
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
            @if($libros->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $libros->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>
@endsection
