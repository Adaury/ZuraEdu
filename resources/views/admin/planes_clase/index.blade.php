@extends('layouts.admin')

@section('page-title', 'Planes de Clase')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Planes de Clase</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Planes de Clase</li>
            </ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.planes-clase.lista-pdf', request()->query()) }}"
               class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('admin.planes-clase.lista-excel', request()->query()) }}"
               class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.planes-clase.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Plan
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Buscar por título..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="area" class="form-select form-select-sm">
                        <option value="">-- Área --</option>
                        <option value="academica" @selected(request('area')=='academica')>Académica</option>
                        <option value="tecnica"   @selected(request('area')=='tecnica')>Técnica</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.planes-clase.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Tipo</th>
                            <th>Asignación</th>
                            <th>Docente</th>
                            <th>Fechas</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($planes as $plan)
                        <tr>
                            <td>
                                <a href="{{ route('admin.planes-clase.show', $plan) }}" class="fw-semibold text-decoration-none">
                                    {{ $plan->titulo }}
                                </a>
                                @if($plan->tieneArchivo())
                                    <i class="bi bi-paperclip text-muted ms-1" title="Tiene archivo adjunto"></i>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $plan->area === 'academica' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($plan->area) }}
                                </span>
                            </td>
                            <td class="text-capitalize">{{ $plan->tipo_plan }}</td>
                            <td>
                                @if($plan->asignacion)
                                    <span class="small">{{ $plan->asignacion->asignatura->nombre ?? '—' }}</span><br>
                                    <span class="text-muted small">{{ $plan->asignacion->grupo->nombre_completo ?? '' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $plan->docente?->nombre_completo ?? '—' }}</td>
                            <td class="small text-nowrap">
                                @if($plan->fecha_inicio)
                                    {{ $plan->fecha_inicio->format('d/m/Y') }}
                                    @if($plan->fecha_fin) – {{ $plan->fecha_fin->format('d/m/Y') }} @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->publicado)
                                    <span class="badge bg-success">Publicado</span>
                                @else
                                    <span class="badge bg-secondary">Borrador</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.planes-clase.show', $plan) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.planes-clase.edit', $plan) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($plan->tieneArchivo())
                                <a href="{{ route('admin.planes-clase.download', $plan) }}" class="btn btn-sm btn-outline-info" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                @endif
                                <form method="POST" action="{{ route('admin.planes-clase.destroy', $plan) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este plan de clase?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay planes de clase registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($planes->hasPages())
        <div class="card-footer">{{ $planes->links() }}</div>
        @endif
    </div>
</div>
@endsection
