@extends('layouts.admin')
@section('page-title', 'Asignaturas')

@push('styles')
<style>
    .color-circle {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid rgba(0,0,0,.1);
        vertical-align: middle;
    }
    .table th {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8faff;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
        font-size: .87rem;
    }
    .badge-area {
        font-size: .72rem;
        font-weight: 600;
        padding: .3em .75em;
        border-radius: 20px;
        background: #e0e7ff;
        color: #3730a3;
        white-space: nowrap;
    }

    [data-theme="dark"] .badge-area { background: #1e1b4b; color: #a5b4fc; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-book me-2"></i>Asignaturas
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Gestión de materias del plan de estudios.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.asignaturas.lista-pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.asignaturas.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.asignaturas.create') }}" class="btn btn-primary px-4 fw-semibold">
            <i class="bi bi-plus-circle me-2"></i>Nueva Asignatura
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2" style="font-size:.85rem;">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2" style="font-size:.85rem;">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Search --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.asignaturas.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary px-3">Buscar</button>
                @if(request('search'))
                <a href="{{ route('admin.asignaturas.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
                @endif
            </div>
            <div class="col-auto ms-auto text-muted" style="font-size:.8rem;">
                <i class="bi bi-collection me-1"></i>
                <strong>{{ $asignaturas->total() }}</strong> asignaturas
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Código</th>
                        <th>Nombre</th>
                        <th>Área</th>
                        <th class="text-center">Horas/sem</th>
                        <th class="text-center">Color</th>
                        <th class="text-center">Básica</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignaturas as $asignatura)
                    <tr>
                        <td class="ps-3 text-muted" style="font-size:.8rem;font-family:monospace;">
                            {{ $asignatura->codigo ?? '—' }}
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $asignatura->nombre }}</span>
                            @if($asignatura->descripcion)
                            <div class="text-muted" style="font-size:.75rem;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $asignatura->descripcion }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @if($asignatura->area)
                            <span class="badge-area">{{ $asignatura->area }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($asignatura->horas_semanales)
                            <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                                {{ $asignatura->horas_semanales }}h
                            </span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="color-circle" style="background-color:{{ $asignatura->color ?? '#1e3a6e' }};"
                                  title="{{ $asignatura->color ?? '#1e3a6e' }}"></span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('admin.asignaturas.update', $asignatura) }}" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="nombre"          value="{{ $asignatura->nombre }}">
                                <input type="hidden" name="codigo"          value="{{ $asignatura->codigo }}">
                                <input type="hidden" name="descripcion"     value="{{ $asignatura->descripcion }}">
                                <input type="hidden" name="area"            value="{{ $asignatura->area }}">
                                <input type="hidden" name="horas_semanales" value="{{ $asignatura->horas_semanales }}">
                                <input type="hidden" name="color"           value="{{ $asignatura->color }}">
                                <input type="hidden" name="activo"          value="{{ $asignatura->activo ? 1 : 0 }}">
                                <input type="hidden" name="es_basica"       value="{{ $asignatura->es_basica ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm border-0 p-0"
                                        title="{{ $asignatura->es_basica ? 'Quitar de básicas' : 'Marcar como básica' }}">
                                    @if($asignatura->es_basica)
                                    <span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;font-size:.75rem;">
                                        <i class="bi bi-star-fill me-1"></i>Sí
                                    </span>
                                    @else
                                    <span class="badge rounded-pill" style="background:#f3f4f6;color:#6b7280;font-size:.75rem;">
                                        <i class="bi bi-star me-1"></i>No
                                    </span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('admin.asignaturas.update', $asignatura) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="nombre"          value="{{ $asignatura->nombre }}">
                                <input type="hidden" name="codigo"          value="{{ $asignatura->codigo }}">
                                <input type="hidden" name="descripcion"     value="{{ $asignatura->descripcion }}">
                                <input type="hidden" name="area"            value="{{ $asignatura->area }}">
                                <input type="hidden" name="horas_semanales" value="{{ $asignatura->horas_semanales }}">
                                <input type="hidden" name="color"           value="{{ $asignatura->color }}">
                                <input type="hidden" name="activo"          value="{{ $asignatura->activo ? 0 : 1 }}">
                                <input type="hidden" name="es_basica"       value="{{ $asignatura->es_basica ? 1 : 0 }}">
                                <button type="submit" class="btn btn-sm border-0 p-0"
                                        title="{{ $asignatura->activo ? 'Desactivar' : 'Activar' }}">
                                    @if($asignatura->activo)
                                    <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:.75rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>Activa
                                    </span>
                                    @else
                                    <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:.75rem;">
                                        <i class="bi bi-x-circle-fill me-1"></i>Inactiva
                                    </span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('admin.asignaciones.create', ['asignatura_id' => $asignatura->id]) }}"
                                   class="btn btn-sm btn-outline-success px-2"
                                   title="Asignar docente a grupos">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                                <a href="{{ route('admin.asignaturas.edit', $asignatura) }}"
                                   class="btn btn-sm btn-outline-primary px-2" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.asignaturas.destroy', $asignatura) }}"
                                      onsubmit="return confirm('¿Eliminar esta asignatura?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-book" style="font-size:3rem;opacity:.3;"></i>
                            <p class="mt-3 mb-0">No hay asignaturas registradas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($asignaturas->hasPages())
    <div class="card-footer bg-white border-top py-2 px-3">
        {{ $asignaturas->links() }}
    </div>
    @endif
</div>

@endsection
