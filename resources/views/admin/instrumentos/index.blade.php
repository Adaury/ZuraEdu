@extends('layouts.admin')

@section('title', 'Instrumentos de Evaluación')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Instrumentos de Evaluación</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Instrumentos</li>
            </ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.instrumentos.lista-pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('admin.instrumentos.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
            </a>
            <a href="{{ route('admin.instrumentos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Instrumento
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Buscar por título..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">-- Tipo --</option>
                        @foreach(\App\Models\InstrumentoEvaluacion::$tiposLabels as $k => $v)
                            <option value="{{ $k }}" @selected(request('tipo')==$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="{{ route('admin.instrumentos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Limpiar</a>
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
                            <th>Tipo</th>
                            <th>Asignación</th>
                            <th>Docente</th>
                            <th>Criterios</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($instrumentos as $inst)
                        <tr>
                            <td>
                                <a href="{{ route('admin.instrumentos.show', $inst) }}" class="fw-semibold text-decoration-none">
                                    {{ $inst->titulo }}
                                </a>
                            </td>
                            <td><span class="badge bg-info text-dark">{{ $inst->tipo_label }}</span></td>
                            <td class="small">
                                @if($inst->asignacion)
                                    {{ $inst->asignacion->asignatura->nombre ?? '—' }}<br>
                                    <span class="text-muted">{{ $inst->asignacion->grupo->nombre_completo ?? '' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $inst->docente?->nombre_completo ?? '—' }}</td>
                            <td class="text-center">{{ $inst->criterios->count() }}</td>
                            <td>
                                @if($inst->publicado)
                                    <span class="badge bg-success">Publicado</span>
                                @else
                                    <span class="badge bg-secondary">Borrador</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.instrumentos.show', $inst) }}" class="btn btn-sm btn-outline-primary" title="Ver / Evaluar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.instrumentos.destroy', $inst) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este instrumento?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay instrumentos registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($instrumentos->hasPages())
        <div class="card-footer">{{ $instrumentos->links() }}</div>
        @endif
    </div>
</div>
@endsection
