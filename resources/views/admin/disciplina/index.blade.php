@extends('layouts.admin')
@section('page-title', 'Disciplina Escolar')

@section('content')
<div class="container-fluid py-3">

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-shield-exclamation text-danger me-2"></i>Disciplina Escolar
        </h4>
        <small class="text-muted">Registro y seguimiento de faltas disciplinarias</small>
    </div>
    <a href="{{ route('admin.disciplina.create') }}" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Registrar Falta
    </a>
</div>

{{-- Tarjetas resumen por tipo --}}
<div class="row g-2 mb-3">
    @foreach($tipos as $key => $tipo)
    @php $count = $totalesTipo[$key] ?? 0; @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100"
             style="border-left:4px solid {{ $tipo['color'] }} !important;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi {{ $tipo['icon'] }}" style="color:{{ $tipo['color'] }};font-size:1.5rem;"></i>
                <div>
                    <div class="fw-bold fs-5">{{ $count }}</div>
                    <div class="small text-muted">{{ $tipo['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Alerta pendientes --}}
@if($totalPendientes > 0)
<div class="alert alert-warning alert-dismissible fade show py-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <strong>{{ $totalPendientes }}</strong> falta(s) sin resolver pendientes de atención.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Estudiante</label>
                <select name="estudiante_id" class="form-select form-select-sm">
                    <option value="">Todos los estudiantes</option>
                    @foreach($estudiantes as $est)
                    <option value="{{ $est->id }}" {{ request('estudiante_id') == $est->id ? 'selected' : '' }}>
                        {{ $est->apellidos }}, {{ $est->nombres }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm mb-1">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $key => $t)
                    <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>
                        {{ $t['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm mb-1">Estado</label>
                <select name="resuelto" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="0" {{ request('resuelto') === '0' ? 'selected' : '' }}>Pendiente</option>
                    <option value="1" {{ request('resuelto') === '1' ? 'selected' : '' }}>Resuelto</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm mb-1">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm"
                       value="{{ request('fecha_desde') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                       value="{{ request('fecha_hasta') }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Buscar estudiante</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Nombre..." value="{{ request('q') }}">
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.disciplina.index') }}" class="btn btn-outline-secondary btn-sm">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($faltas->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-shield-check" style="font-size:2.5rem;color:#10b981;"></i>
            <div class="mt-2 fw-semibold">No hay faltas disciplinarias registradas</div>
            @if(request()->hasAny(['estudiante_id','tipo','resuelto','fecha_desde','fecha_hasta','q']))
            <div class="mt-1 small">Intenta con otros filtros.</div>
            @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:110px;">Tipo</th>
                        <th>Estudiante</th>
                        <th>Descripción</th>
                        <th style="width:120px;">Docente</th>
                        <th style="width:90px;">Fecha</th>
                        <th style="width:90px;">Estado</th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($faltas as $falta)
                @php $ti = $falta->tipo_info; @endphp
                <tr>
                    <td>
                        <span class="badge rounded-pill"
                              style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};font-weight:700;font-size:.75rem;">
                            <i class="bi {{ $ti['icon'] }} me-1"></i>{{ $ti['label'] }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $falta->estudiante?->nombre_completo ?? '—' }}</div>
                        @if($falta->estudiante_id)
                        <a href="{{ route('admin.disciplina.expediente-pdf', $falta->estudiante_id) }}"
                           class="small text-danger text-decoration-none" target="_blank" title="Expediente PDF">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Expediente
                        </a>
                        @endif
                    </td>
                    <td>
                        <div style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                             title="{{ $falta->descripcion }}">
                            {{ $falta->descripcion }}
                        </div>
                        @if($falta->notas_resolucion)
                        <small class="text-success">
                            <i class="bi bi-check2-circle me-1"></i>{{ Str::limit($falta->notas_resolucion, 60) }}
                        </small>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ $falta->docente?->nombre_completo ?? '—' }}
                    </td>
                    <td class="text-muted small">
                        {{ $falta->fecha->format('d/m/Y') }}
                    </td>
                    <td class="text-center">
                        <form method="POST"
                              action="{{ route('admin.disciplina.toggle-resuelto', $falta) }}"
                              class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="btn btn-sm {{ $falta->resuelto ? 'btn-outline-success' : 'btn-outline-warning' }}"
                                    title="{{ $falta->resuelto ? 'Marcar como pendiente' : 'Marcar como resuelto' }}"
                                    style="padding:.15rem .45rem;font-size:.72rem;">
                                <i class="bi bi-{{ $falta->resuelto ? 'check-circle-fill' : 'hourglass-split' }}"></i>
                                {{ $falta->resuelto ? 'Resuelto' : 'Pendiente' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.disciplina.edit', $falta) }}"
                               class="btn btn-sm btn-outline-primary"
                               style="padding:.15rem .4rem;font-size:.72rem;" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('admin.disciplina.destroy', $falta) }}"
                                  onsubmit="return confirm('¿Eliminar esta falta disciplinaria?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        style="padding:.15rem .4rem;font-size:.72rem;" title="Eliminar">
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
        @if($faltas->hasPages())
        <div class="p-3">
            {{ $faltas->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

</div>
@endsection
