@extends('layouts.admin')
@section('page-title', 'Usuarios Pendientes de Aprobación')

@push('styles')
<style>
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

    .card-panel {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        border-radius: 20px;
        padding: .25rem .75rem;
        font-size: .78rem;
        font-weight: 600;
    }

    .user-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1.1rem 1.25rem;
        background: #fff;
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-start;
    }
    .user-card:hover { border-color: #bfdbfe; background: #f8faff; }

    .avatar-initials {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2a4f96, var(--primary));
        color: #fff;
        font-size: .8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .user-info-block { flex: 1; min-width: 0; }

    .user-fullname {
        font-size: .95rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: .15rem;
    }

    .user-meta {
        font-size: .8rem;
        color: #6b7280;
        display: flex;
        flex-wrap: wrap;
        gap: .5rem 1rem;
        margin-bottom: .35rem;
    }

    .user-meta span { display: flex; align-items: center; gap: .3rem; }

    .user-actions {
        display: flex;
        gap: .5rem;
        align-items: center;
        flex-shrink: 0;
    }

    .btn-approve {
        background: #22c55e;
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: .45rem 1rem;
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        transition: background .15s;
    }
    .btn-approve:hover { background: #16a34a; color: #fff; }

    .btn-reject {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        border-radius: 7px;
        padding: .45rem 1rem;
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        transition: background .15s, color .15s;
    }
    .btn-reject:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3rem; display: block; margin-bottom: .75rem; opacity: .5; }
    .empty-state p { font-size: .9rem; margin: 0; }

    .role-badge {
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 5px;
        padding: .15rem .5rem;
        font-size: .74rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .requested-at {
        font-size: .75rem;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    [data-theme="dark"] .card-panel { background: #1e293b; }
    [data-theme="dark"] .role-badge { background: #0c1f3f; color: #93c5fd; }
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
        <h1>
            <i class="bi bi-person-check me-2" style="color:var(--secondary);"></i>
            Usuarios Pendientes
        </h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Solicitudes de acceso que requieren tu aprobación
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($usuarios->count() > 0)
            <span class="badge-pending">
                <i class="bi bi-clock me-1"></i>
                {{ $usuarios->count() }} pendiente{{ $usuarios->count() !== 1 ? 's' : '' }}
            </span>
        @endif
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver a Usuarios
        </a>
    </div>
</div>

{{-- List --}}
<div class="card-panel">

    @if($usuarios->isEmpty())
        <div class="empty-state">
            <i class="bi bi-person-check"></i>
            <p>No hay solicitudes de acceso pendientes.</p>
        </div>
    @else
        @foreach($usuarios as $usuario)
            <div class="user-card">

                {{-- Avatar --}}
                <div class="avatar-initials">
                    {{ strtoupper(substr($usuario->name, 0, 1) . substr($usuario->apellidos ?? '', 0, 1)) }}
                </div>

                {{-- Info --}}
                <div class="user-info-block">
                    <div class="user-fullname">
                        {{ $usuario->name }} {{ $usuario->apellidos }}
                        <span class="role-badge ms-1">
                            {{ $usuario->getRoleNames()->first() ?? '—' }}
                        </span>
                    </div>

                    <div class="user-meta">
                        <span><i class="bi bi-envelope"></i> {{ $usuario->email }}</span>
                        @if($usuario->cedula)
                            <span><i class="bi bi-card-text"></i> {{ $usuario->cedula }}</span>
                        @endif
                        @if($usuario->telefono)
                            <span><i class="bi bi-telephone"></i> {{ $usuario->telefono }}</span>
                        @endif
                        @if($usuario->area_trabajo)
                            <span><i class="bi bi-diagram-3"></i> Área: {{ $usuario->area_trabajo }}</span>
                        @endif
                    </div>

                    <div class="requested-at">
                        <i class="bi bi-clock"></i>
                        Solicitado el {{ $usuario->created_at->format('d/m/Y') }} a las {{ $usuario->created_at->format('H:i') }}
                        ({{ $usuario->created_at->diffForHumans() }})
                    </div>
                </div>

                {{-- Actions --}}
                <div class="user-actions">
                    {{-- Approve --}}
                    <form method="POST" action="{{ route('admin.usuarios.aprobar', $usuario) }}"
                          onsubmit="return confirm('¿Aprobar la cuenta de {{ addslashes($usuario->name) }}?')">
                        @csrf
                        <button type="submit" class="btn-approve">
                            <i class="bi bi-check-lg"></i> Aprobar
                        </button>
                    </form>

                    {{-- Reject --}}
                    <button type="button" class="btn-reject"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRechazar"
                            data-usuario-id="{{ $usuario->id }}"
                            data-usuario-nombre="{{ $usuario->name }} {{ $usuario->apellidos }}">
                        <i class="bi bi-x-lg"></i> Rechazar
                    </button>
                </div>

            </div>{{-- /.user-card --}}
        @endforeach
    @endif

</div>{{-- /.card-panel --}}

{{-- Modal: Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarLabel">
                    <i class="bi bi-x-circle text-danger me-2"></i>Rechazar Solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="form-rechazar" action="">
                @csrf
                <div class="modal-body">
                    <p class="mb-3" style="font-size:.9rem;">
                        Estás por rechazar y eliminar la solicitud de
                        <strong id="nombre-rechazar"></strong>.
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="mb-0">
                        <label for="motivo" class="form-label" style="font-size:.85rem;font-weight:600;">
                            Motivo del rechazo (opcional)
                        </label>
                        <textarea id="motivo" name="motivo" class="form-control" rows="3"
                                  placeholder="Ej. Código de acceso incorrecto, rol no disponible..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash3 me-1"></i>Rechazar y eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('modalRechazar');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        var btn    = e.relatedTarget;
        var id     = btn.getAttribute('data-usuario-id');
        var nombre = btn.getAttribute('data-usuario-nombre');
        document.getElementById('nombre-rechazar').textContent = nombre;
        document.getElementById('form-rechazar').action =
            '/admin/usuarios/' + id + '/rechazar';
    });
})();
</script>
@endpush
