@extends('layouts.admin')
@section('page-title', 'Traslados')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-arrow-left-right me-2" style="color:#f59e0b;"></i>Traslados entre Instituciones
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            Estudiantes transferidos a otras instituciones
            @if($schoolYear) — <strong>{{ $schoolYear->nombre }}</strong> @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.registro-academico.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('admin.estudiantes.index') }}" class="btn btn-sm" style="background:var(--secondary);color:#fff;border-radius:8px;font-weight:600;">
            <i class="bi bi-search me-1"></i>Buscar Estudiante
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-exclamation-circle-fill"></i>{{ session('error') }}
</div>
@endif

{{-- Filtro búsqueda --}}
<form method="GET" class="d-flex gap-2 mb-3">
    <input type="text" name="buscar" value="{{ request('buscar') }}"
           placeholder="Buscar por nombre..."
           class="form-control form-control-sm" style="max-width:280px;border-radius:8px;">
    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;">
        <i class="bi bi-search me-1"></i>Buscar
    </button>
    @if(request('buscar'))
    <a href="{{ route('admin.registro-academico.traslados') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Limpiar</a>
    @endif
</form>

<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.84rem;">
            <thead style="background:#fffbeb;">
                <tr>
                    <th class="ps-4">Estudiante</th>
                    <th>Grupo</th>
                    <th>Fecha Traslado</th>
                    <th>Institución Destino</th>
                    <th>Motivo</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matriculas as $m)
                <tr>
                    <td class="ps-4">
                        <div style="font-weight:600;">{{ $m->estudiante->apellidos }}, {{ $m->estudiante->nombres }}</div>
                        <div style="font-size:.74rem;color:#9ca3af;">{{ $m->estudiante->numero_matricula }}</div>
                    </td>
                    <td>{{ $m->grupo?->grado?->nombre }} {{ $m->grupo?->seccion?->nombre }}</td>
                    <td>{{ $m->fecha_baja?->format('d/m/Y') ?? '—' }}</td>
                    <td style="font-weight:500;">{{ $m->institucion_traslado ?? '—' }}</td>
                    <td style="max-width:180px;color:#6b7280;">{{ \Str::limit($m->motivo_baja, 45) ?? '—' }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.perfiles.estudiante', $m->estudiante) }}"
                           class="btn btn-sm btn-outline-secondary" style="border-radius:6px;font-size:.73rem;">
                            Perfil
                        </a>
                        <form method="POST" action="{{ route('admin.registro-academico.baja.reactivar', $m) }}"
                              class="d-inline"
                              onsubmit="return confirm('¿Reactivar matrícula de {{ $m->estudiante->nombre_completo }}?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success ms-1" style="border-radius:6px;font-size:.73rem;">
                                Reactivar
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-arrow-left-right" style="font-size:1.5rem;opacity:.3;display:block;margin-bottom:.4rem;"></i>
                        No hay traslados registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($matriculas->hasPages())
    <div class="px-4 py-3 border-top">{{ $matriculas->links() }}</div>
    @endif
</div>

<div class="mt-3 p-3" style="background:#fffbeb;border-radius:10px;border:1px solid #fde68a;font-size:.82rem;color:#92400e;">
    <i class="bi bi-info-circle me-1"></i>
    Para registrar un traslado nuevo, busca el estudiante en <a href="{{ route('admin.estudiantes.index') }}" style="color:#92400e;font-weight:600;">Estudiantes</a>
    y desde su perfil usa la opción <strong>Registrar Traslado</strong>.
</div>
@endsection
