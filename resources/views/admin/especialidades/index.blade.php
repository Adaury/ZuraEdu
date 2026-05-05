@extends('layouts.admin')

@section('page-title', 'Especialidades Técnicas')

@push('styles')
<style>
    .esp-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
        transition: box-shadow .18s, transform .18s;
    }
    .esp-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,.09);
        transform: translateY(-2px);
    }
    .esp-header {
        padding: 1rem 1.25rem;
        color: #fff;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .docente-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #f0f4ff; border: 1px solid #c7d7fd;
        color: #1e3a6e; border-radius: 20px;
        font-size: .76rem; font-weight: 500;
        padding: .2rem .65rem; margin: .1rem;
    }

    [data-theme="dark"] .esp-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .docente-chip { background: #0c1f3f; border-color: #1d4ed8; color: #93c5fd; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-mortarboard me-2"></i>Especialidades Técnicas
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Gestión de las especialidades del área técnica del politécnico.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.areas.tecnica') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Ver Área Técnica
        </a>
        <a href="{{ route('admin.especialidades.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nueva Especialidad
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

@if($especialidades->isEmpty())
<div class="empty-state-enhanced">
    <div class="empty-illustration"><i class="bi bi-mortarboard"></i></div>
    <div class="empty-title">Sin especialidades registradas</div>
    <div class="empty-desc">Crea las especialidades técnicas del politécnico para organizar a los docentes y la malla curricular.</div>
    <div class="empty-actions">
        <a href="{{ route('admin.especialidades.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Crear Primera Especialidad
        </a>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($especialidades->sortBy('orden') as $esp)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="esp-card">
            {{-- Header de color --}}
            <div class="esp-header" style="background:{{ $esp->color }};">
                <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                    <i class="bi {{ $esp->icono }}"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold" style="font-size:.95rem;">{{ $esp->nombre }}</div>
                    <div style="font-size:.75rem;opacity:.85;">Código: {{ $esp->codigo }}</div>
                </div>
                <span class="badge {{ $esp->activo ? 'bg-white text-success' : 'bg-dark text-light' }}" style="font-size:.65rem;">
                    {{ $esp->activo ? 'Activa' : 'Inactiva' }}
                </span>
            </div>

            {{-- Body --}}
            <div class="p-3">
                @if($esp->descripcion)
                <p class="text-muted mb-2" style="font-size:.8rem;">{{ Str::limit($esp->descripcion, 80) }}</p>
                @endif

                {{-- Coordinador --}}
                <div class="mb-2" style="font-size:.78rem;">
                    <span class="fw-semibold text-muted">Coordinador: </span>
                    @if($esp->coordinador)
                        <span style="color:#1e293b;">{{ $esp->coordinador->nombre_completo }}</span>
                    @else
                        <span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Sin asignar</span>
                    @endif
                </div>

                {{-- Docentes --}}
                <div class="mb-3">
                    <div style="font-size:.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.4rem;">
                        Docentes ({{ $esp->docentes->count() }})
                    </div>
                    @forelse($esp->docentes->take(4) as $doc)
                    <div class="docente-chip">
                        <i class="bi bi-person-fill" style="font-size:.65rem;"></i>
                        {{ $doc->nombre_completo }}
                        @if($doc->pivot->es_coordinador)
                        <i class="bi bi-star-fill" style="font-size:.6rem;color:#f59e0b;" title="Coordinador"></i>
                        @endif
                    </div>
                    @empty
                    <span class="text-muted" style="font-size:.78rem;">Sin docentes asignados</span>
                    @endforelse
                    @if($esp->docentes->count() > 4)
                    <div class="docente-chip" style="background:#f8fafc;color:#6b7280;border-color:#e5e7eb;">
                        +{{ $esp->docentes->count() - 4 }} más
                    </div>
                    @endif
                </div>

                {{-- Acciones --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.especialidades.edit', $esp) }}"
                       class="btn btn-sm btn-outline-primary flex-grow-1" style="font-size:.78rem;">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <a href="{{ route('admin.areas.tecnica') }}#esp-{{ $esp->id }}"
                       class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;" title="Ver docentes">
                        <i class="bi bi-eye"></i>
                    </a>
                    <form method="POST" action="{{ route('admin.especialidades.destroy', $esp) }}"
                          onsubmit="return confirm('¿Eliminar la especialidad {{ $esp->nombre }}? Esta acción no se puede deshacer.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
