@extends('layouts.admin')
@section('page-title', 'Bajas y Retiros')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-person-dash-fill me-2" style="color:#ef4444;"></i>Bajas y Retiros
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            Estudiantes retirados o transferidos
            @if($schoolYear) — <strong>{{ $schoolYear->nombre }}</strong> @endif
        </p>
    </div>
    <a href="{{ route('admin.registro-academico.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

@if(session('success'))
<div class="alert alert-success d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1.1rem 1.25rem;">
            <div style="font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Retirados</div>
            <div style="font-size:1.8rem;font-weight:800;color:#ef4444;">{{ $totalBajas }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1.1rem 1.25rem;">
            <div style="font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Transferidos</div>
            <div style="font-size:1.8rem;font-weight:800;color:#f59e0b;">{{ $totalTransferidas }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1.1rem 1.25rem;">
            <div style="font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Total</div>
            <div style="font-size:1.8rem;font-weight:800;color:var(--primary);">{{ $totalBajas + $totalTransferidas }}</div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    <input type="text" name="buscar" value="{{ request('buscar') }}"
           placeholder="Buscar por nombre o cédula..."
           class="form-control form-control-sm" style="max-width:260px;border-radius:8px;">
    <select name="estado" class="form-select form-select-sm" style="max-width:160px;border-radius:8px;">
        <option value="">Todos los tipos</option>
        <option value="retirada"    {{ request('estado') === 'retirada'    ? 'selected' : '' }}>Retirados</option>
        <option value="transferida" {{ request('estado') === 'transferida' ? 'selected' : '' }}>Transferidos</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;">
        <i class="bi bi-search me-1"></i>Filtrar
    </button>
    @if(request()->hasAny(['buscar','estado']))
    <a href="{{ route('admin.registro-academico.bajas') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Limpiar</a>
    @endif
</form>

<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.84rem;">
            <thead style="background:#f8faff;">
                <tr>
                    <th class="ps-4">Estudiante</th>
                    <th>Grupo</th>
                    <th>Tipo</th>
                    <th>Fecha Baja</th>
                    <th>Motivo</th>
                    <th>Institución Destino</th>
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
                    <td>
                        @if($m->estado === 'retirada')
                            <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:.15rem .55rem;font-size:.72rem;font-weight:700;">Retirado</span>
                        @else
                            <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:.15rem .55rem;font-size:.72rem;font-weight:700;">Transferido</span>
                        @endif
                    </td>
                    <td>{{ $m->fecha_baja?->format('d/m/Y') ?? '—' }}</td>
                    <td style="max-width:200px;">
                        <span title="{{ $m->motivo_baja }}">{{ \Str::limit($m->motivo_baja, 40) ?? '—' }}</span>
                    </td>
                    <td>{{ $m->institucion_traslado ?? '—' }}</td>
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
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle" style="font-size:1.5rem;color:#10b981;display:block;margin-bottom:.4rem;"></i>
                        No hay registros de bajas.
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
@endsection
