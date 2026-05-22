@extends('layouts.admin')
@section('page-title', 'Incidentes Médicos')

@section('content')
<div class="container-fluid py-3">

{{-- Encabezado ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-clipboard2-pulse text-warning me-2"></i>Incidentes Médicos
        </h4>
        <small class="text-muted">Registro de accidentes, enfermedades y atenciones médicas</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.salud.incidentes.pdf', request()->query()) }}"
           class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.salud.incidentes.excel', request()->query()) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.salud.incidentes.crear') }}" class="btn btn-warning btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Registrar incidente
        </a>
    </div>
</div>

{{-- Alertas ─────────────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tarjetas resumen por tipo ────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    @foreach($tipos as $key => $ti)
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="card-body p-2">
                <div class="fs-4 mb-1" style="color:{{ $ti['color'] }};">
                    <i class="bi {{ $ti['icon'] }}"></i>
                </div>
                <div class="fw-bold" style="color:{{ $ti['color'] }};">{{ $conteosTipo[$key] ?? 0 }}</div>
                <div class="text-muted small">{{ $ti['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtros ──────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.salud.incidentes') }}" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small mb-1 fw-semibold">Estudiante</label>
                <select name="estudiante_id" class="form-select form-select-sm">
                    <option value="">— Todos —</option>
                    @foreach($estudiantes as $est)
                    <option value="{{ $est->id }}" {{ request('estudiante_id') == $est->id ? 'selected' : '' }}>
                        {{ $est->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small mb-1 fw-semibold">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">— Todos —</option>
                    @foreach($tipos as $key => $ti)
                    <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>
                        {{ $ti['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small mb-1 fw-semibold">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm"
                       value="{{ request('fecha_desde') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small mb-1 fw-semibold">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                       value="{{ request('fecha_hasta') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small mb-1 fw-semibold">Búsqueda</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Descripción, acción…" value="{{ request('q') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                @if(request()->hasAny(['tipo','estudiante_id','fecha_desde','fecha_hasta','q']))
                <a href="{{ route('admin.salud.incidentes') }}" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabla de incidentes ──────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha / Hora</th>
                        <th>Estudiante</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Acción tomada</th>
                        <th>Remitido a</th>
                        <th>Notificado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidentes as $inc)
                    @php $ti = $inc->tipo_info; @endphp
                    <tr>
                        <td class="ps-3 small text-nowrap">
                            {{ $inc->fecha->format('d/m/Y') }}
                            @if($inc->hora)
                            <div class="text-muted" style="font-size:.72rem;">{{ \Carbon\Carbon::parse($inc->hora)->format('H:i') }}</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.salud.ficha', $inc->estudiante) }}"
                               class="text-decoration-none fw-semibold small">
                                {{ $inc->estudiante?->nombre_completo ?? '—' }}
                            </a>
                        </td>
                        <td>
                            <span class="badge rounded-pill"
                                  style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};">
                                <i class="bi {{ $ti['icon'] }} me-1"></i>{{ $ti['label'] }}
                            </span>
                        </td>
                        <td class="small" style="max-width:220px;">
                            {{ Str::limit($inc->descripcion, 80) }}
                        </td>
                        <td class="small text-muted" style="max-width:180px;">
                            {{ Str::limit($inc->accion_tomada, 60) }}
                        </td>
                        <td class="small text-muted">{{ $inc->remitido_a ?? '—' }}</td>
                        <td>
                            @if($inc->notificado_representante)
                            <span class="badge bg-success" style="font-size:.68rem;">
                                <i class="bi bi-check-lg me-1"></i>Sí
                            </span>
                            @else
                            <span class="badge bg-warning text-dark" style="font-size:.68rem;">
                                <i class="bi bi-bell-slash me-1"></i>No
                            </span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.salud.incidentes.editar', $inc) }}"
                                   class="btn btn-outline-secondary btn-sm py-0 px-2" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.salud.incidentes.eliminar', $inc) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este incidente?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"
                                            title="Eliminar">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard2-x fs-3 d-block mb-2 text-secondary"></i>
                            No se encontraron incidentes con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($incidentes->hasPages())
    <div class="card-footer bg-transparent border-top py-2">
        {{ $incidentes->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

</div>
@endsection
