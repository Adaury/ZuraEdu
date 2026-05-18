@extends('layouts.admin')
@section('page-title', 'Docentes')

@push('styles')
<style>
    .avatar-initials {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        font-size: .75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: .03em;
        flex-shrink: 0;
    }
    .avatar-img {
        width: 40px; height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .table-hover tbody tr:hover { background: #f8faff; }
    .badge-activo   { background: #d1fae5; color: #065f46; }
    .badge-inactivo { background: #fee2e2; color: #991b1b; }
    .status-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .28rem .65rem;
        border-radius: 20px;
        letter-spacing: .03em;
    }
    .btn-action {
        padding: .25rem .55rem;
        font-size: .78rem;
        border-radius: 6px;
        line-height: 1.4;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3.5rem; display: block; margin-bottom: 1rem; opacity: .4; }
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .page-header h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
    }
    .search-form .input-group {
        max-width: 380px;
    }

    [data-theme="dark"] .badge-activo { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-inactivo { background: #1c0000; color: #f87171; }
</style>
@endpush

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Page header --}}
<div class="page-header p-slide-up">
    <div>
        <h1><i class="bi bi-person-badge me-2" style="color:var(--secondary);"></i>Docentes</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Gestión del personal docente
            @if($docentes->total() > 0)
                &nbsp;·&nbsp; <strong>{{ $docentes->total() }}</strong> registros
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.docentes.lista-pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.docentes.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.docentes.create') }}" class="btn btn-sm px-3 py-2 fw-600"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Docente
        </a>
    </div>
</div>

{{-- Search bar --}}
<div class="card border-0 shadow-sm mb-4 p-slide-up p-delay-1" style="border-radius:12px;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.docentes.index') }}" class="search-form">
            <div class="input-group">
                <span class="input-group-text border-end-0 bg-white" style="border-radius:8px 0 0 8px;">
                    <i class="bi bi-search text-muted" style="font-size:.85rem;"></i>
                </span>
                <input type="text"
                       name="buscar"
                       value="{{ $buscar }}"
                       class="form-control border-start-0 ps-0"
                       placeholder="Buscar por nombre, apellido o cédula…"
                       style="border-radius:0 8px 8px 0;">
                @if($buscar)
                    <a href="{{ route('admin.docentes.index') }}"
                       class="btn btn-outline-secondary ms-2"
                       style="border-radius:8px;font-size:.82rem;">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @else
                    <button type="submit" class="btn ms-2 px-3"
                            style="background:var(--primary);color:#fff;border-radius:8px;font-size:.82rem;">
                        Buscar
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table card --}}
<div class="card border-0 shadow-sm p-slide-up p-delay-2" style="border-radius:12px;overflow:hidden;">
    @if($docentes->isEmpty())
        <div class="empty-state">
            <i class="bi bi-person-badge"></i>
            @if($buscar)
                <p class="fw-600 mb-1" style="font-size:1.05rem;color:#374151;">Sin resultados para "{{ $buscar }}"</p>
                <p style="font-size:.88rem;">Intenta con otros términos de búsqueda.</p>
                <a href="{{ route('admin.docentes.index') }}" class="btn btn-sm btn-outline-secondary mt-1">
                    Ver todos los docentes
                </a>
            @else
                <p class="fw-600 mb-1" style="font-size:1.05rem;color:#374151;">No hay docentes registrados</p>
                <p style="font-size:.88rem;">Comienza añadiendo el primer docente.</p>
                <a href="{{ route('admin.docentes.create') }}" class="btn btn-sm mt-1"
                   style="background:var(--primary);color:#fff;border-radius:8px;">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo Docente
                </a>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                    <tr>
                        <th class="ps-4 py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Docente</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Cédula</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Especialidad</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Teléfono</th>
                        <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Estado</th>
                        <th class="py-3 pe-4 text-end" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docentes as $docente)
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($docente->foto)
                                    <img src="{{ asset('storage/'.$docente->foto) }}"
                                         alt="{{ $docente->nombres }}"
                                         class="avatar-img">
                                @else
                                    <div class="avatar-initials">{{ substr($docente->nombres,0,1) }}{{ substr($docente->apellidos,0,1) }}</div>
                                @endif
                                <div>
                                    <div class="fw-600" style="color:#111827;">{{ $docente->apellidos }}, {{ $docente->nombres }}</div>
                                    @if($docente->email)
                                        <div style="font-size:.76rem;color:#9ca3af;">{{ $docente->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <span style="font-family:monospace;color:#374151;">{{ $docente->cedula ?? '—' }}</span>
                        </td>
                        <td class="py-3" style="color:#374151;">
                            {{ $docente->especialidad ?? '—' }}
                        </td>
                        <td class="py-3" style="color:#374151;">
                            {{ $docente->telefono ?? '—' }}
                        </td>
                        <td class="py-3 text-center">
                            <span class="status-badge {{ $docente->estado === 'activo' ? 'badge-activo' : 'badge-inactivo' }}">
                                {{ $docente->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="py-3 pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.docentes.show', $docente) }}"
                                   class="btn btn-action btn-outline-primary"
                                   title="Ver perfil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.docente.setup', ['docente_id' => $docente->id]) }}"
                                   class="btn btn-action btn-outline-success"
                                   title="Configurar materias y grupos">
                                    <i class="bi bi-gear"></i>
                                </a>
                                <a href="{{ route('admin.docentes.edit', $docente) }}"
                                   class="btn btn-action btn-outline-secondary"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-action btn-outline-danger"
                                        title="Eliminar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDelete{{ $docente->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Delete Modal --}}
                    <div class="modal fade" id="modalDelete{{ $docente->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
                            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                                <div class="modal-body p-4 text-center">
                                    <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <h5 class="fw-700 mb-2" style="color:#111827;">¿Eliminar docente?</h5>
                                    <p class="text-muted mb-4" style="font-size:.88rem;">
                                        Se eliminará permanentemente el registro de
                                        <strong>{{ $docente->apellidos }}, {{ $docente->nombres }}</strong>.
                                        Esta acción no se puede deshacer.
                                    </p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                                        <form method="POST" action="{{ route('admin.docentes.destroy', $docente) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn px-4"
                                                    style="background:var(--secondary);color:#fff;border-radius:8px;">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($docentes->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="text-muted mb-0" style="font-size:.82rem;">
                    Mostrando {{ $docentes->firstItem() }}–{{ $docentes->lastItem() }} de {{ $docentes->total() }} docentes
                </p>
                <div>
                    {{ $docentes->links() }}
                </div>
            </div>
        @endif
    @endif
</div>

@endsection
