@extends('layouts.admin')

@section('page-title', 'Pre-matrículas')

@push('styles')
<style>
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .9rem; margin-bottom: 1.25rem; }
    @media(max-width:768px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
    .stat-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.1rem 1.25rem; display: flex; align-items: center; gap: .85rem;
    }
    .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-val  { font-size: 1.5rem; font-weight: 900; line-height: 1; color: #0f172a; }
    .stat-lbl  { font-size: .73rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-top: .15rem; }

    .filter-bar { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1.25rem; }
    .table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 6px rgba(30,58,110,.05); }
    .table thead th {
        background: #f8fafc; border-bottom: 1px solid #e5e7eb;
        font-size: .72rem; font-weight: 700; letter-spacing: .07em;
        text-transform: uppercase; color: #2563eb; padding: .75rem 1rem; white-space: nowrap;
    }
    .table tbody td { padding: .7rem 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: .84rem; }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }

    .badge-pendiente  { background: #fef9c3; color: #854d0e; font-size: .7rem; font-weight: 700; padding: .22rem .65rem; border-radius: 20px; }
    .badge-aprobada   { background: #d1fae5; color: #065f46; font-size: .7rem; font-weight: 700; padding: .22rem .65rem; border-radius: 20px; }
    .badge-rechazada  { background: #fee2e2; color: #991b1b; font-size: .7rem; font-weight: 700; padding: .22rem .65rem; border-radius: 20px; }

    .btn-action { padding: .22rem .55rem; font-size: .75rem; border-radius: 6px; line-height: 1.4; }
    .nombre-pm  { font-weight: 700; color: #1d4ed8; font-size: .84rem; line-height: 1.2; }

    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .filter-bar,
    [data-theme="dark"] .table-card  { background: #1e293b !important; border-color: #334155 !important; }
    [data-theme="dark"] .table thead th { background: #1e3a8a !important; border-color: #334155 !important; color: #93c5fd !important; }
    [data-theme="dark"] .table tbody td { border-color: #334155 !important; color: #e2e8f0 !important; }
    [data-theme="dark"] .table tbody tr:hover td { background: #263348 !important; }
    [data-theme="dark"] .stat-val { color: #f1f5f9 !important; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-person-lines-fill text-primary me-1"></i> Pre-matrículas
        </h4>
        <p class="text-muted small mb-0">Gestión de solicitudes de inscripción en línea</p>
    </div>
    @if($conteos['pendiente'] > 0)
    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
        <i class="bi bi-clock-fill me-1"></i> {{ $conteos['pendiente'] }} pendiente{{ $conteos['pendiente'] != 1 ? 's' : '' }}
    </span>
    @endif
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tarjetas resumen --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-collection-fill"></i></div>
        <div><div class="stat-val">{{ $conteos['total'] }}</div><div class="stat-lbl">Total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;color:#a16207;"><i class="bi bi-clock-fill"></i></div>
        <div><div class="stat-val">{{ $conteos['pendiente'] }}</div><div class="stat-lbl">Pendientes</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle-fill"></i></div>
        <div><div class="stat-val">{{ $conteos['aprobada'] }}</div><div class="stat-lbl">Aprobadas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-x-circle-fill"></i></div>
        <div><div class="stat-val">{{ $conteos['rechazada'] }}</div><div class="stat-lbl">Rechazadas</div></div>
    </div>
</div>

{{-- Filtros --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.pre-matriculas.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-primary mb-1">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control form-control-sm"
                   placeholder="Nombre, cédula, correo...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-primary mb-1">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <option value="pendiente"  {{ request('estado') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                <option value="aprobada"   {{ request('estado') === 'aprobada'   ? 'selected' : '' }}>Aprobada</option>
                <option value="rechazada"  {{ request('estado') === 'rechazada'  ? 'selected' : '' }}>Rechazada</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-primary mb-1">Grado</label>
            <select name="grado" class="form-select form-select-sm">
                <option value="">Todos los grados</option>
                @foreach($grados as $g)
                <option value="{{ $g }}" {{ request('grado') === $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="bi bi-search"></i> Filtrar
            </button>
            <a href="{{ route('admin.pre-matriculas.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </form>
</div>

{{-- Tabla --}}
<div class="table-card">
    @if($solicitudes->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
        <p class="text-muted mb-0">No hay solicitudes que coincidan con los filtros.</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th>Grado</th>
                    <th>Representante</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $s)
                <tr>
                    <td class="text-muted" style="font-size:.76rem;">{{ $s->id }}</td>
                    <td>
                        <div class="nombre-pm">{{ $s->nombre_completo }}</div>
                        <div style="font-size:.75rem;color:#94a3b8;">{{ $s->fecha_nacimiento->format('d/m/Y') }}</div>
                    </td>
                    <td><span style="background:#eef2ff;color:#4f46e5;border-radius:6px;padding:.15rem .55rem;font-size:.76rem;font-weight:700;">{{ $s->grado_solicitado }}</span></td>
                    <td>
                        <div style="font-size:.84rem;font-weight:600;">{{ $s->nombre_representante }}</div>
                        <div style="font-size:.75rem;color:#94a3b8;">{{ $s->cedula_representante }}</div>
                    </td>
                    <td style="font-size:.82rem;">{{ $s->telefono }}</td>
                    <td style="font-size:.82rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $s->email }}</td>
                    <td style="font-size:.78rem;color:#64748b;white-space:nowrap;">{{ $s->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($s->estado === 'pendiente')
                            <span class="badge-pendiente"><i class="bi bi-clock-fill me-1"></i>Pendiente</span>
                        @elseif($s->estado === 'aprobada')
                            <span class="badge-aprobada"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>
                        @else
                            <span class="badge-rechazada"><i class="bi bi-x-circle-fill me-1"></i>Rechazada</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="{{ route('admin.pre-matriculas.show', $s) }}"
                               class="btn btn-outline-primary btn-action"
                               title="Ver detalle">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            @if($s->estado === 'pendiente')
                            <a href="{{ route('admin.pre-matriculas.show', $s) }}#resolver"
                               class="btn btn-success btn-action"
                               title="Aprobar / Rechazar">
                                <i class="bi bi-check2-square"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($solicitudes->hasPages())
    <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted">
            Mostrando {{ $solicitudes->firstItem() }}–{{ $solicitudes->lastItem() }} de {{ $solicitudes->total() }} solicitudes
        </span>
        {{ $solicitudes->links() }}
    </div>
    @endif
    @endif
</div>

@endsection
