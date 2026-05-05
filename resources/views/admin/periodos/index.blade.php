@extends('layouts.admin')
@section('page-title', 'Períodos Académicos')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-calendar3 me-2"></i>Períodos Académicos
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gestiona los períodos por año escolar.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.periodos.lista-pdf') }}" target="_blank" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.periodos.lista-excel') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.periodos.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Período
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@forelse($schoolYears as $sy)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between" style="background:linear-gradient(90deg,var(--primary),#2a4f96);color:#fff;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-mortarboard"></i>
            <span class="fw-bold">{{ $sy->nombre }}</span>
            @if($sy->activo)
            <span class="badge rounded-pill ms-2" style="background:var(--accent);color:#1e293b;font-size:.72rem;">Activo</span>
            @endif
        </div>
        <a href="{{ route('admin.periodos.create') }}?year={{ $sy->id }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus me-1"></i>Agregar Período
        </a>
    </div>
    <div class="card-body p-0">
        @if($sy->periodos->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="bi bi-calendar-x" style="font-size:1.5rem;opacity:.4;"></i>
            <p class="mt-2 mb-0 small">Sin períodos. <a href="{{ route('admin.periodos.create') }}?year={{ $sy->id }}">Crear uno</a></p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.88rem;">
                <thead style="background:#f8faff;">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Nombre</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Cerrado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sy->periodos as $periodo)
                    <tr>
                        <td class="ps-4 fw-bold" style="color:var(--primary);">{{ $periodo->numero }}</td>
                        <td class="fw-semibold">{{ $periodo->nombre }}</td>
                        <td>{{ $periodo->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $periodo->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-center">
                            @if($periodo->activo)
                            <span class="badge bg-success rounded-pill">Activo</span>
                            @else
                            <span class="badge bg-secondary rounded-pill">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($periodo->cerrado)
                            <span class="badge bg-danger rounded-pill">Cerrado</span>
                            @else
                            <span class="badge bg-success rounded-pill">Abierto</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.periodos.checklist', $periodo) }}"
                               class="btn btn-sm btn-outline-secondary me-1" title="Checklist de cierre">
                                <i class="bi bi-list-check"></i>
                            </a>
                            <a href="{{ route('admin.periodos.edit', $periodo) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.periodos.destroy', $periodo) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este período?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@empty
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-calendar3" style="font-size:2.5rem;opacity:.3;"></i>
        <p class="mt-3 mb-3">No hay años escolares. Primero crea un año escolar.</p>
        <a href="{{ route('admin.school-years.create') }}" class="btn btn-primary">
            <i class="bi bi-mortarboard me-2"></i>Crear Año Escolar
        </a>
    </div>
</div>
@endforelse

@endsection
