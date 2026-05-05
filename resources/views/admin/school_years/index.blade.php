@extends('layouts.admin')
@section('page-title', 'Años Escolares')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-mortarboard me-2"></i>Años Escolares
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gestiona los años escolares del sistema.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.school-years.lista-pdf') }}" target="_blank" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.school-years.lista-excel') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.school-years.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Año Escolar
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    @forelse($schoolYears as $sy)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100" style="{{ $sy->activo ? 'border-left:4px solid var(--primary)!important;' : '' }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="color:var(--primary)">{{ $sy->nombre }}</h5>
                        <div class="text-muted" style="font-size:.82rem;">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $sy->fecha_inicio?->format('d/m/Y') ?? '—' }}
                            &rarr;
                            {{ $sy->fecha_fin?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                    @if($sy->activo)
                    <span class="badge rounded-pill px-3 py-2" style="background:var(--accent-light);color:#92400e;font-size:.75rem;border:1px solid #fcd34d;">
                        <i class="bi bi-check-circle me-1"></i>Activo
                    </span>
                    @else
                    <span class="badge bg-secondary rounded-pill px-3 py-2" style="font-size:.75rem;">Inactivo</span>
                    @endif
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div class="text-center px-3 py-2 rounded" style="background:#eef3fb;flex:1;">
                        <div class="fw-bold" style="color:var(--primary);font-size:1.1rem;">{{ $sy->grupos_count }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Grupos</div>
                    </div>
                    <div class="text-center px-3 py-2 rounded" style="background:#eef3fb;flex:1;">
                        <div class="fw-bold" style="color:var(--primary);font-size:1.1rem;">{{ $sy->periodos_count }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Períodos</div>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.school-years.edit', $sy) }}" class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <a href="{{ route('admin.periodos.index') }}?year={{ $sy->id }}" class="btn btn-sm btn-outline-secondary flex-fill">
                        <i class="bi bi-calendar3 me-1"></i>Períodos
                    </a>
                    @if($sy->grupos_count === 0)
                    <form method="POST" action="{{ route('admin.school-years.destroy', $sy) }}" onsubmit="return confirm('¿Eliminar este año escolar?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
                {{-- Matrícula masiva --}}
                <div class="mt-2">
                    <a href="{{ route('admin.school-years.matricula-masiva', $sy) }}"
                       class="btn btn-sm w-100"
                       style="background:#0d6efd18;color:#0d6efd;border:1px solid #0d6efd30;font-size:.78rem;">
                        <i class="bi bi-person-plus-fill me-1"></i>
                        Matrícula Masiva desde Año Anterior
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-mortarboard" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-3 mb-3">No hay años escolares registrados.</p>
                <a href="{{ route('admin.school-years.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Crear Primer Año Escolar
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

@endsection
