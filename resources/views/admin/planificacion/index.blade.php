@extends('layouts.admin')
@section('title', 'Planificaciones — Área Técnica')

@section('content')
<div class="container-fluid py-3">

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text text-primary me-2"></i>Planificaciones — Área Técnica</h4>
        <small class="text-muted">{{ $schoolYear->nombre }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.planificacion.lista-pdf') . '?' . http_build_query(request()->only('tipo','asignacion_id')) }}"
           target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.planificacion.lista-excel') . '?' . http_build_query(request()->only('tipo','asignacion_id')) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.planificacion.cumplimiento-pdf') }}" target="_blank"
           class="btn btn-sm fw-semibold" style="background:#dc2626;color:#fff;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Cumplim. PDF
        </a>
        <a href="{{ route('admin.planificacion.cumplimiento-excel') }}"
           class="btn btn-sm fw-semibold" style="background:#0369a1;color:#fff;">
            <i class="bi bi-check2-square me-1"></i>Cumplim. Excel
        </a>
        <a href="{{ route('admin.planificacion.create-ra') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nueva por RA
        </a>
        <a href="{{ route('admin.planificacion.create-actividad') }}" class="btn btn-success btn-sm" style="background:#059669;border-color:#059669;">
            <i class="bi bi-plus-circle me-1"></i>Nueva por Actividad
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label form-label-sm mb-1">Módulo / Asignación</label>
                <select name="asignacion_id" class="form-select form-select-sm">
                    <option value="">Todas las asignaciones</option>
                    @foreach($asignaciones as $asig)
                    <option value="{{ $asig->id }}" {{ request('asignacion_id') == $asig->id ? 'selected' : '' }}>
                        {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ra" {{ request('tipo') === 'ra' ? 'selected' : '' }}>Por Resultados de Aprendizaje</option>
                    <option value="actividad" {{ request('tipo') === 'actividad' ? 'selected' : '' }}>Por Actividad</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
            </div>
            <div class="col-sm-2">
                <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($planificaciones->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal-x" style="font-size:2.5rem;"></i>
            <div class="mt-2">No hay planificaciones registradas.</div>
            <div class="mt-2 d-flex gap-2 justify-content-center">
                <a href="{{ route('admin.planificacion.create-ra') }}" class="btn btn-primary btn-sm">Nueva por RA</a>
                <a href="{{ route('admin.planificacion.create-actividad') }}" class="btn btn-success btn-sm">Nueva por Actividad</a>
            </div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Módulo</th>
                        <th>Asignación</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th>Estado</th>
                        <th style="width:120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($planificaciones as $i => $plan)
                <tr>
                    <td class="text-muted">{{ $planificaciones->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-semibold">{{ $plan->modulo_nombre ?? $plan->asignacion?->asignatura?->nombre ?? '—' }}</div>
                        @if($plan->mf_codigo)
                        <small class="text-muted font-monospace">{{ $plan->mf_codigo }}</small>
                        @endif
                    </td>
                    <td>
                        <div>{{ $plan->asignacion?->asignatura?->nombre }}</div>
                        <small class="text-muted">{{ $plan->asignacion?->grupo?->nombre_completo }} · {{ $plan->asignacion?->docente?->nombre_completo }}</small>
                    </td>
                    <td>
                        @if($plan->tipo === 'ra')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Por RA</span>
                        @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Por Actividad</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->fecha_inicio && $plan->fecha_fin)
                        <small>{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}</small>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->publicado)
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-eye me-1"></i>Publicado</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-eye-slash me-1"></i>Borrador</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.planificacion.show', $plan) }}"
                               class="btn btn-outline-primary btn-sm py-0 px-2" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.planificacion.edit', $plan) }}"
                               class="btn btn-outline-secondary btn-sm py-0 px-2" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.planificacion.destroy', $plan) }}"
                                  onsubmit="return confirm('¿Eliminar esta planificación?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm py-0 px-2" title="Eliminar">
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
        <div class="px-3 py-2">{{ $planificaciones->links() }}</div>
        @endif
    </div>
</div>

</div>
@endsection
