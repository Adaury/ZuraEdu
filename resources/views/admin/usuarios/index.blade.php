@extends('layouts.admin')
@section('page-title', 'Usuarios del Sistema')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────── */
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

    /* ── Filter bar ──────────────────────────────── */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }
    .filter-bar .form-control,
    .filter-bar .form-select {
        font-size: .84rem;
        border-radius: 8px;
        padding: .45rem .8rem;
    }

    /* ── Table ───────────────────────────────────── */
    .table-hover tbody tr:hover { background: #f8faff; }

    /* ── Avatar initials ─────────────────────────── */
    .avatar-initials {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2a4f96, var(--primary));
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        letter-spacing: .03em;
    }

    /* ── Status badge ────────────────────────────── */
    .status-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .28rem .65rem;
        border-radius: 20px;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .badge-activo   { background: #d1fae5; color: #065f46; }
    .badge-inactivo { background: #fee2e2; color: #991b1b; }

    /* ── Role badge ──────────────────────────────── */
    .role-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .28rem .65rem;
        border-radius: 20px;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .role-administrador { background: #dbeafe; color: #1e3a6e; }
    .role-director      { background: #ede9fe; color: #5b21b6; }
    .role-docente       { background: #ccfbf1; color: #0f766e; }
    .role-estudiante    { background: #dcfce7; color: #166534; }
    .role-secretaria    { background: #ffedd5; color: #9a3412; }
    .role-coordinador   { background: #e0e7ff; color: #3730a3; }
    .role-other         { background: #f3f4f6; color: #4b5563; }

    /* ── Action buttons ──────────────────────────── */
    .btn-action {
        padding: .25rem .55rem;
        font-size: .78rem;
        border-radius: 6px;
        line-height: 1.4;
    }

    /* ── Toggle button ───────────────────────────── */
    .btn-toggle {
        background: none;
        border: none;
        padding: .2rem .35rem;
        cursor: pointer;
        border-radius: 6px;
        font-size: 1.15rem;
        line-height: 1;
        transition: opacity .15s;
    }
    .btn-toggle:hover { opacity: .7; }
    .btn-toggle.loading { opacity: .4; pointer-events: none; }

    /* ── Empty state ─────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3.5rem; display: block; margin-bottom: 1rem; opacity: .4; }

    [data-theme="dark"] .filter-bar { background: #162032; border-color: #334155; }
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
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        {{ session('error') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Page header --}}
<div class="page-header">
    <div>
        <h1><i class="bi bi-people-fill me-2" style="color:var(--secondary);"></i>Usuarios del Sistema</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Gestión de cuentas y roles
            @if($usuarios->total() > 0)
                &nbsp;·&nbsp; <strong>{{ $usuarios->total() }}</strong>
                {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @php $pendientesCount = \App\Models\User::where('pendiente_aprobacion', true)->count(); @endphp
        <a href="{{ route('admin.usuarios.pendientes') }}"
           class="btn btn-sm px-3 py-2 position-relative"
           style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-person-check me-1"></i>
            Accesos Pendientes
            @if($pendientesCount > 0)
                <span class="badge rounded-pill bg-warning text-dark ms-1" style="font-size:.7rem;">
                    {{ $pendientesCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('admin.usuarios.lista-pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.usuarios.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.usuarios.create') }}"
           class="btn btn-sm px-3 py-2"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-person-plus-fill me-1"></i>Nuevo Usuario
        </a>
    </div>
</div>

{{-- Filter bar --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;border:1px solid #e5e7eb !important;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.usuarios.index') }}">
            <div class="filter-bar">
                {{-- Search input --}}
                <div class="input-group flex-grow-1" style="max-width:340px;">
                    <span class="input-group-text border-end-0 bg-white" style="border-radius:8px 0 0 8px;">
                        <i class="bi bi-search text-muted" style="font-size:.85rem;"></i>
                    </span>
                    <input type="text"
                           name="buscar"
                           value="{{ $buscar }}"
                           class="form-control border-start-0 ps-0"
                           placeholder="Nombre, apellido o correo…"
                           style="border-radius:0 8px 8px 0;">
                </div>

                {{-- Role select --}}
                <select name="rol" class="form-select" style="max-width:220px;">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->name }}"
                            {{ $rolFiltro === $rol->name ? 'selected' : '' }}>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn px-3"
                        style="background:var(--primary);color:#fff;border-radius:8px;font-size:.84rem;">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>

                @if($buscar || $rolFiltro)
                    <a href="{{ route('admin.usuarios.index') }}"
                       class="btn btn-outline-secondary"
                       style="border-radius:8px;font-size:.84rem;">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table card --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;border:1px solid #e5e7eb !important;overflow:hidden;">
    @if($usuarios->isEmpty())
        <div class="empty-state">
            <i class="bi bi-people"></i>
            @if($buscar || $rolFiltro)
                <p class="fw-semibold mb-1" style="font-size:1.05rem;color:#374151;">Sin resultados</p>
                <p style="font-size:.88rem;">Prueba con otros filtros de búsqueda.</p>
                <a href="{{ route('admin.usuarios.index') }}"
                   class="btn btn-sm btn-outline-secondary mt-1">
                    Ver todos los usuarios
                </a>
            @else
                <p class="fw-semibold mb-1" style="font-size:1.05rem;color:#374151;">No hay usuarios registrados</p>
                <p style="font-size:.88rem;">Comienza añadiendo el primer usuario del sistema.</p>
                <a href="{{ route('admin.usuarios.create') }}"
                   class="btn btn-sm mt-1"
                   style="background:var(--primary);color:#fff;border-radius:8px;">
                    <i class="bi bi-person-plus-fill me-1"></i>Nuevo Usuario
                </a>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                    <tr>
                        <th class="ps-4 py-3 text-center"
                            style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;width:60px;">
                            ID
                        </th>
                        <th class="py-3"
                            style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">
                            Usuario
                        </th>
                        <th class="py-3"
                            style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">
                            Rol
                        </th>
                        <th class="py-3 text-center"
                            style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">
                            Estado
                        </th>
                        <th class="py-3 pe-4 text-end"
                            style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usuarios as $u)
                    @php
                        $rolNombre = $u->getRoleNames()->first() ?? '';
                        $rolSlug   = match(true) {
                            str_contains(strtolower($rolNombre), 'administrador')  => 'administrador',
                            str_contains(strtolower($rolNombre), 'director')       => 'director',
                            str_contains(strtolower($rolNombre), 'docente')        => 'docente',
                            str_contains(strtolower($rolNombre), 'estudiante')     => 'estudiante',
                            str_contains(strtolower($rolNombre), 'secretar')       => 'secretaria',
                            str_contains(strtolower($rolNombre), 'coordinador')    => 'coordinador',
                            default                                                 => 'other',
                        };
                    @endphp
                    <tr id="row-{{ $u->id }}">

                        {{-- ID --}}
                        <td class="ps-4 py-3 text-center">
                            <span style="font-size:.78rem;font-weight:700;color:#6b7280;font-family:monospace;">
                                #{{ str_pad($u->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>

                        {{-- Usuario --}}
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-initials">
                                    {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}{{ mb_strtoupper(mb_substr($u->apellidos ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color:#111827;">
                                        {{ $u->name }}{{ $u->apellidos ? ' '.$u->apellidos : '' }}
                                    </div>
                                    <div style="font-size:.76rem;color:#9ca3af;">{{ $u->email }}</div>
                                    @if($u->telefono)
                                        <div style="font-size:.74rem;color:#b0b7c3;">
                                            <i class="bi bi-telephone me-1"></i>{{ $u->telefono }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Rol --}}
                        <td class="py-3">
                            @if($rolNombre)
                                <span class="role-badge role-{{ $rolSlug }}">{{ $rolNombre }}</span>
                            @else
                                <span class="text-muted" style="font-size:.8rem;">—</span>
                            @endif
                        </td>

                        {{-- Estado + toggle --}}
                        <td class="py-3 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span id="badge-{{ $u->id }}"
                                      class="status-badge {{ $u->activo ? 'badge-activo' : 'badge-inactivo' }}">
                                    {{ $u->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                                <button type="button"
                                        class="btn-toggle"
                                        id="toggle-{{ $u->id }}"
                                        title="{{ $u->activo ? 'Desactivar usuario' : 'Activar usuario' }}"
                                        onclick="toggleActivo({{ $u->id }})">
                                    @if($u->activo)
                                        <i class="bi bi-toggle-on" style="color:#10b981;"></i>
                                    @else
                                        <i class="bi bi-toggle-off" style="color:#9ca3af;"></i>
                                    @endif
                                </button>
                            </div>
                        </td>

                        {{-- Acciones --}}
                        <td class="py-3 pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.usuarios.edit', $u) }}"
                                   class="btn btn-action btn-outline-secondary"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-action btn-outline-danger"
                                        title="Eliminar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDelete{{ $u->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Delete modal --}}
                    <div class="modal fade" id="modalDelete{{ $u->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
                            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                                <div class="modal-body p-4 text-center">
                                    <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2" style="color:#111827;">¿Eliminar usuario?</h5>
                                    <p class="text-muted mb-4" style="font-size:.88rem;">
                                        Se eliminará permanentemente la cuenta de
                                        <strong>{{ $u->name }}{{ $u->apellidos ? ' '.$u->apellidos : '' }}</strong>
                                        ({{ $u->email }}).
                                        Esta acción no se puede deshacer.
                                    </p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                                            Cancelar
                                        </button>
                                        <form method="POST"
                                              action="{{ route('admin.usuarios.destroy', $u) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn px-4"
                                                    style="background:var(--secondary);color:#fff;border-radius:8px;">
                                                <i class="bi bi-trash me-1"></i>Eliminar
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
        @if($usuarios->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2"
                 style="border-top:1px solid #e5e7eb;">
                <p class="text-muted mb-0" style="font-size:.82rem;">
                    Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }}
                    de {{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}
                </p>
                <div>{{ $usuarios->appends(request()->query())->links() }}</div>
            </div>
        @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
/**
 * AJAX toggle for the `activo` field.
 * POSTs to the toggle route, then swaps the icon and badge without reload.
 */
function toggleActivo(userId) {
    const btn   = document.getElementById('toggle-' + userId);
    const badge = document.getElementById('badge-'  + userId);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    btn.classList.add('loading');

    fetch(`/admin/usuarios/${userId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
    })
    .then(data => {
        const activo = data.activo;

        // Swap icon
        btn.innerHTML = activo
            ? '<i class="bi bi-toggle-on"  style="color:#10b981;"></i>'
            : '<i class="bi bi-toggle-off" style="color:#9ca3af;"></i>';
        btn.title = activo ? 'Desactivar usuario' : 'Activar usuario';

        // Swap status badge
        badge.className = 'status-badge ' + (activo ? 'badge-activo' : 'badge-inactivo');
        badge.textContent = activo ? 'Activo' : 'Inactivo';
    })
    .catch(err => {
        console.error('toggleActivo error:', err);
        alert('No se pudo cambiar el estado del usuario. Intente nuevamente.');
    })
    .finally(() => {
        btn.classList.remove('loading');
    });
}
</script>
@endpush
