@extends('layouts.admin')
@section('page-title', 'Soporte Interno')

@push('styles')
<style>
.badge-prioridad, .badge-estado {
    display: inline-flex;
    align-items: center;
    padding: .22rem .65rem;
    border-radius: 9999px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
}
.ticket-row:hover { background: #f8faff; }
.stat-card { border-radius: .75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary,#1e3a6e);">
            <i class="bi bi-headset me-2"></i>Tickets de Soporte
        </h4>
        <p class="text-muted small mb-0 mt-1">Gestión de solicitudes y reporte de incidencias</p>
    </div>
    <a href="{{ route('admin.soporte.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Ticket
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Contadores --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#eff6ff;">
                <i class="bi bi-ticket-detailed text-primary"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 lh-1">{{ $contadores['total'] }}</div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#f0fdf4;">
                <i class="bi bi-envelope-open text-success"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 lh-1">{{ $contadores['abierto'] }}</div>
                <div class="text-muted small">Abiertos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#fefce8;">
                <i class="bi bi-arrow-repeat text-warning"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 lh-1">{{ $contadores['en_proceso'] }}</div>
                <div class="text-muted small">En proceso</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#f5f3ff;">
                <i class="bi bi-check2-circle text-indigo"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 lh-1">{{ $contadores['resuelto'] }}</div>
                <div class="text-muted small">Resueltos</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.soporte.index') }}" class="row g-2 mb-3 align-items-end">
    <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold mb-1">Estado</label>
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach($estados as $val => $label)
            <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold mb-1">Categoría</label>
        <select name="categoria" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $val => $label)
            <option value="{{ $val }}" {{ request('categoria') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold mb-1">Prioridad</label>
        <select name="prioridad" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todas las prioridades</option>
            @foreach($prioridades as $val => $label)
            <option value="{{ $val }}" {{ request('prioridad') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        @if(request()->hasAny(['estado','categoria','prioridad']))
        <a href="{{ route('admin.soporte.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
        </a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8faff;">
                <tr>
                    <th class="ps-3 text-muted small fw-semibold">#</th>
                    <th class="text-muted small fw-semibold">Título</th>
                    <th class="text-muted small fw-semibold">Categoría</th>
                    <th class="text-muted small fw-semibold">Prioridad</th>
                    <th class="text-muted small fw-semibold">Estado</th>
                    @if($esAdmin)
                    <th class="text-muted small fw-semibold">Solicitante</th>
                    <th class="text-muted small fw-semibold">Asignado a</th>
                    @endif
                    <th class="text-muted small fw-semibold">Fecha</th>
                    <th class="text-muted small fw-semibold text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr class="ticket-row">
                    <td class="ps-3 text-muted small">{{ $ticket->id }}</td>
                    <td>
                        <a href="{{ route('admin.soporte.show', $ticket) }}" class="fw-semibold text-decoration-none" style="color:#1e293b;">
                            {{ Str::limit($ticket->titulo, 55) }}
                        </a>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-chat-dots me-1"></i>{{ $ticket->respuestas_count ?? $ticket->respuestas()->count() }} respuesta(s)
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border small">{{ $ticket->categoria_nombre }}</span>
                    </td>
                    <td>
                        <span class="badge-prioridad {{ $ticket->color_prioridad }}">
                            {{ $ticket->prioridad_nombre }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-estado {{ $ticket->color_estado }}">
                            {{ $ticket->estado_nombre }}
                        </span>
                    </td>
                    @if($esAdmin)
                    <td class="small text-muted">{{ $ticket->solicitante?->nombre_completo ?? '—' }}</td>
                    <td class="small text-muted">{{ $ticket->asignadoA?->nombre_completo ?? '—' }}</td>
                    @endif
                    <td class="small text-muted text-nowrap">{{ $ticket->created_at->format('d/m/Y') }}</td>
                    <td class="text-end pe-3">
                        <a href="{{ route('admin.soporte.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $esAdmin ? 9 : 7 }}" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                        No hay tickets con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tickets->hasPages())
    <div class="card-footer bg-transparent d-flex justify-content-end">
        {{ $tickets->links() }}
    </div>
    @endif
</div>

@endsection
