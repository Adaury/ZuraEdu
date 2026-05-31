@extends('layouts.admin')
@section('page-title', 'Detalle de Usuario')

@push('styles')
<style>
    /* ── Hero card ───────────────────────────────── */
    .user-hero {
        background: linear-gradient(135deg, var(--primary), #2a5298);
        border-radius: 16px;
        padding: 1.75rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .user-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
        border-radius: 50%;
    }
    .user-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; left: 40%;
        width: 220px; height: 220px;
        background: rgba(255,255,255,.05);
        border-radius: 50%;
    }

    .avatar-xl {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: rgba(255,255,255,.18);
        border: 3px solid rgba(255,255,255,.35);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; font-weight: 800; color: #fff;
        flex-shrink: 0;
        letter-spacing: .02em;
    }
    .avatar-xl img {
        width: 100%; height: 100%;
        object-fit: cover; border-radius: 50%;
    }

    .hero-name {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: .3rem;
    }
    .hero-email {
        font-size: .87rem;
        opacity: .82;
    }
    .hero-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 20px;
        padding: .25rem .8rem;
        font-size: .78rem; font-weight: 600;
        color: #fff;
    }
    .hero-badge-green  { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); }
    .hero-badge-red    { background: rgba(239,68,68,.25);  border-color: rgba(239,68,68,.4);  }

    /* ── Info cards ──────────────────────────────── */
    .info-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 4px rgba(15,23,42,.05);
    }
    .info-card-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .45rem;
        margin-bottom: 1.1rem;
        display: flex; align-items: center; gap: .5rem;
    }

    /* ── Field rows ──────────────────────────────── */
    .field-row {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: .65rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .field-row:last-child { border-bottom: none; }
    .field-label {
        font-size: .78rem;
        font-weight: 600;
        color: #6b7280;
        min-width: 160px;
        flex-shrink: 0;
        padding-top: .05rem;
    }
    .field-value {
        font-size: .875rem;
        color: #111827;
        font-weight: 500;
        word-break: break-word;
    }
    .field-empty { color: #9ca3af; font-style: italic; font-weight: 400; }

    /* ── Role badge ──────────────────────────────── */
    .role-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .78rem; font-weight: 600;
        padding: .28rem .8rem;
        border-radius: 20px;
    }
    .role-administrador { background: #dbeafe; color: #1e3a6e; }
    .role-director      { background: #ede9fe; color: #5b21b6; }
    .role-docente       { background: #ccfbf1; color: #0f766e; }
    .role-estudiante    { background: #dcfce7; color: #166534; }
    .role-secretaria    { background: #ffedd5; color: #9a3412; }
    .role-coordinador   { background: #e0e7ff; color: #3730a3; }
    .role-other         { background: #f3f4f6; color: #4b5563; }

    /* ── Status pill ─────────────────────────────── */
    .status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .78rem; font-weight: 600;
        padding: .28rem .8rem;
        border-radius: 20px;
    }
    .pill-activo   { background: #d1fae5; color: #065f46; }
    .pill-inactivo { background: #fee2e2; color: #991b1b; }
    .pill-warning  { background: #fef3c7; color: #92400e; }
    .pill-info     { background: #e0f2fe; color: #0369a1; }

    /* ── Linked profile card ─────────────────────── */
    .linked-card {
        display: flex; align-items: center; gap: .85rem;
        background: #f8faff;
        border: 1px solid #e0e7ff;
        border-radius: 12px;
        padding: .85rem 1rem;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .15s, border-color .15s;
    }
    .linked-card:hover {
        box-shadow: 0 2px 10px rgba(30,58,110,.12);
        border-color: var(--primary);
        color: inherit;
    }
    .linked-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        background: var(--primary);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem; flex-shrink: 0;
    }

    /* ── Action strip ────────────────────────────── */
    .action-strip {
        display: flex; gap: .5rem; flex-wrap: wrap;
    }

    [data-theme="dark"] .info-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .field-row { border-color: #293548; }
    [data-theme="dark"] .field-value { color: #e2e8f0; }
    [data-theme="dark"] .linked-card { background: #162032; border-color: #334155; }
</style>
@endpush

@section('content')

@php
    $rol      = $usuario->getRoleNames()->first() ?? '';
    $rolSlug  = match(true) {
        str_contains(strtolower($rol), 'administrador') => 'administrador',
        str_contains(strtolower($rol), 'director')      => 'director',
        str_contains(strtolower($rol), 'docente')        => 'docente',
        str_contains(strtolower($rol), 'estudiante')     => 'estudiante',
        str_contains(strtolower($rol), 'secretar')       => 'secretaria',
        str_contains(strtolower($rol), 'coordinador')    => 'coordinador',
        default                                          => 'other',
    };
    $iniciales = mb_strtoupper(mb_substr($usuario->name, 0, 1))
               . mb_strtoupper(mb_substr($usuario->apellidos ?? '', 0, 1));
@endphp

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-circle-fill"></i>{{ session('error') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Breadcrumb + back ────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 mb-3" style="font-size:.83rem;color:#6b7280;">
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Usuarios
    </a>
    <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
    <span class="text-truncate">{{ $usuario->nombre_completo }}</span>
</div>

{{-- ── HERO ─────────────────────────────────────────────────────────── --}}
<div class="user-hero p-slide-up">
    <div class="d-flex align-items-center gap-3 flex-wrap" style="position:relative;z-index:1;">

        {{-- Avatar --}}
        <div class="avatar-xl">
            @if($usuario->photo_url)
                <img src="{{ $usuario->photo_url }}" alt="{{ $usuario->name }}">
            @else
                {{ $iniciales }}
            @endif
        </div>

        {{-- Info --}}
        <div class="flex-grow-1">
            <div class="hero-name">{{ $usuario->nombre_completo }}</div>
            <div class="hero-email mb-2">
                <i class="bi bi-envelope me-1"></i>{{ $usuario->email }}
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($rol)
                <span class="hero-badge">
                    <i class="bi bi-person-badge"></i>{{ $rol }}
                </span>
                @endif
                <span id="hero-status-badge"
                      class="hero-badge {{ $usuario->activo ? 'hero-badge-green' : 'hero-badge-red' }}">
                    <i class="bi {{ $usuario->activo ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                    <span id="hero-status-text">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span>
                </span>
                @if($usuario->must_change_password)
                <span class="hero-badge">
                    <i class="bi bi-key"></i>Debe cambiar contraseña
                </span>
                @endif
                @if($usuario->pendiente_aprobacion)
                <span class="hero-badge" style="background:rgba(251,191,36,.25);border-color:rgba(251,191,36,.4);">
                    <i class="bi bi-hourglass-split"></i>Pendiente de aprobación
                </span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="action-strip mt-2 mt-md-0">
            <a href="{{ route('admin.usuarios.edit', $usuario) }}"
               class="btn btn-sm"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-size:.83rem;">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <button type="button"
                    id="btn-toggle-hero"
                    class="btn btn-sm"
                    style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-size:.83rem;"
                    onclick="toggleActivoHero({{ $usuario->id }})"
                    title="{{ $usuario->activo ? 'Desactivar cuenta' : 'Activar cuenta' }}">
                <i class="bi {{ $usuario->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}" id="hero-toggle-icon"></i>
                <span id="hero-toggle-label">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span>
            </button>
            <button type="button"
                    class="btn btn-sm"
                    style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-size:.83rem;"
                    data-bs-toggle="modal" data-bs-target="#modalResetPass">
                <i class="bi bi-key me-1"></i>Reset pass
            </button>
            <button type="button"
                    class="btn btn-sm"
                    style="background:rgba(220,38,38,.35);color:#fff;border:1px solid rgba(220,38,38,.5);border-radius:8px;font-size:.83rem;"
                    data-bs-toggle="modal" data-bs-target="#modalDelete">
                <i class="bi bi-trash me-1"></i>Eliminar
            </button>
        </div>

    </div>
</div>

<div class="row g-4">

    {{-- ── Columna izquierda ──────────────────────────────────────────── --}}
    <div class="col-lg-7">

        {{-- Información personal --}}
        <div class="info-card p-slide-up p-delay-1">
            <div class="info-card-title">
                <i class="bi bi-person-badge"></i>Información Personal
            </div>

            <div class="field-row">
                <span class="field-label">Nombres</span>
                <span class="field-value">{{ $usuario->name ?: '—' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">Apellidos</span>
                <span class="field-value {{ !$usuario->apellidos ? 'field-empty' : '' }}">
                    {{ $usuario->apellidos ?: 'No especificado' }}
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Cédula</span>
                <span class="field-value {{ !$usuario->cedula ? 'field-empty' : '' }}">
                    {{ $usuario->cedula ?: 'No especificada' }}
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Teléfono</span>
                @if($usuario->telefono)
                <span class="field-value">
                    <a href="tel:{{ $usuario->telefono }}" class="text-decoration-none">
                        <i class="bi bi-telephone me-1 text-muted"></i>{{ $usuario->telefono }}
                    </a>
                </span>
                @else
                <span class="field-value field-empty">No especificado</span>
                @endif
            </div>
            <div class="field-row">
                <span class="field-label">Correo</span>
                <span class="field-value">
                    <a href="mailto:{{ $usuario->email }}" class="text-decoration-none">
                        <i class="bi bi-envelope me-1 text-muted"></i>{{ $usuario->email }}
                    </a>
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Área de trabajo</span>
                <span class="field-value {{ !$usuario->area_trabajo ? 'field-empty' : '' }}">
                    {{ $usuario->area_trabajo ?: 'No especificada' }}
                </span>
            </div>
        </div>

        {{-- Perfil vinculado --}}
        @if($usuario->docente || $usuario->estudiante || $usuario->nominaEmpleado)
        <div class="info-card p-slide-up p-delay-2">
            <div class="info-card-title">
                <i class="bi bi-link-45deg"></i>Perfil Vinculado
            </div>
            <div class="d-flex flex-column gap-2">

                @if($usuario->docente)
                <a href="{{ route('admin.docentes.show', $usuario->docente) }}"
                   class="linked-card" title="Ver perfil docente">
                    <div class="linked-icon" style="background:#0f766e;">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <div style="font-size:.85rem;font-weight:700;color:#111827;">Docente</div>
                        <div style="font-size:.78rem;color:#6b7280;">
                            {{ $usuario->docente->especialidad ?? 'Sin especialidad registrada' }}
                        </div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted" style="font-size:.8rem;"></i>
                </a>
                @endif

                @if($usuario->estudiante)
                <a href="{{ route('admin.estudiantes.show', $usuario->estudiante) }}"
                   class="linked-card" title="Ver perfil estudiante">
                    <div class="linked-icon" style="background:#166534;">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <div style="font-size:.85rem;font-weight:700;color:#111827;">Estudiante</div>
                        <div style="font-size:.78rem;color:#6b7280;">
                            {{ $usuario->estudiante->nombre_completo ?? 'Ver perfil' }}
                        </div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted" style="font-size:.8rem;"></i>
                </a>
                @endif

                @if($usuario->nominaEmpleado)
                <div class="linked-card" style="cursor:default;">
                    <div class="linked-icon" style="background:#9a3412;">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <div style="font-size:.85rem;font-weight:700;color:#111827;">Nómina</div>
                        <div style="font-size:.78rem;color:#6b7280;">
                            {{ $usuario->nominaEmpleado->cargo ?? 'Empleado' }}
                            &nbsp;·&nbsp;
                            RD$ {{ number_format($usuario->nominaEmpleado->salario_base, 2) }}
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
        @endif

    </div>

    {{-- ── Columna derecha ────────────────────────────────────────────── --}}
    <div class="col-lg-5">

        {{-- Estado de cuenta --}}
        <div class="info-card p-slide-up p-delay-1">
            <div class="info-card-title">
                <i class="bi bi-shield-check"></i>Estado de Cuenta
            </div>

            <div class="field-row">
                <span class="field-label">Estado</span>
                <span class="field-value">
                    <span id="status-pill"
                          class="status-pill {{ $usuario->activo ? 'pill-activo' : 'pill-inactivo' }}">
                        <i class="bi {{ $usuario->activo ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Rol</span>
                <span class="field-value">
                    @if($rol)
                    <span class="role-pill role-{{ $rolSlug }}">
                        <i class="bi bi-person-badge"></i>{{ $rol }}
                    </span>
                    @else
                    <span class="field-empty">Sin rol asignado</span>
                    @endif
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Email verificado</span>
                <span class="field-value">
                    @if($usuario->email_verified_at)
                    <span class="status-pill pill-activo">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ $usuario->email_verified_at->format('d/m/Y') }}
                    </span>
                    @else
                    <span class="status-pill pill-warning">
                        <i class="bi bi-exclamation-circle-fill"></i>Sin verificar
                    </span>
                    @endif
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Cambio de clave</span>
                <span class="field-value">
                    @if($usuario->must_change_password)
                    <span class="status-pill pill-warning">
                        <i class="bi bi-key-fill"></i>Requerido
                    </span>
                    @else
                    <span class="status-pill pill-activo">
                        <i class="bi bi-check-circle-fill"></i>No requerido
                    </span>
                    @endif
                </span>
            </div>
            @if($usuario->pendiente_aprobacion)
            <div class="field-row">
                <span class="field-label">Aprobación</span>
                <span class="field-value">
                    <span class="status-pill pill-warning">
                        <i class="bi bi-hourglass-split"></i>Pendiente
                    </span>
                </span>
            </div>
            @endif
        </div>

        {{-- Actividad --}}
        <div class="info-card p-slide-up p-delay-2">
            <div class="info-card-title">
                <i class="bi bi-clock-history"></i>Actividad
            </div>

            <div class="field-row">
                <span class="field-label">ID del sistema</span>
                <span class="field-value" style="font-family:monospace;font-size:.82rem;color:#6b7280;">
                    #{{ str_pad($usuario->id, 4, '0', STR_PAD_LEFT) }}
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Registrado</span>
                <span class="field-value">
                    <span title="{{ $usuario->created_at->format('d/m/Y H:i') }}">
                        {{ $usuario->created_at->format('d/m/Y') }}
                    </span>
                    <small class="text-muted ms-1">({{ $usuario->created_at->diffForHumans() }})</small>
                </span>
            </div>
            <div class="field-row">
                <span class="field-label">Última actualización</span>
                <span class="field-value">
                    <span title="{{ $usuario->updated_at->format('d/m/Y H:i') }}">
                        {{ $usuario->updated_at->format('d/m/Y') }}
                    </span>
                    <small class="text-muted ms-1">({{ $usuario->updated_at->diffForHumans() }})</small>
                </span>
            </div>
        </div>

    </div>
</div>

{{-- ── Modal Reset Password ─────────────────────────────────────────── --}}
<div class="modal fade" id="modalResetPass" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header py-2" style="background:#d97706;color:#fff;border-radius:14px 14px 0 0;">
                <h6 class="modal-title mb-0"><i class="bi bi-key me-1"></i>Restablecer Contraseña</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.usuarios.reset-password', $usuario) }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.83rem;">
                        Nueva contraseña para <strong>{{ $usuario->nombre_completo }}</strong>.
                        El usuario deberá cambiarla al iniciar sesión.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control form-control-sm"
                               minlength="8" required placeholder="Mínimo 8 caracteres">
                    </div>
                    <div>
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm"
                               minlength="8" required>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning text-white">
                        <i class="bi bi-key me-1"></i>Restablecer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Eliminar ───────────────────────────────────────────────── --}}
<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-2" style="color:#111827;">¿Eliminar usuario?</h5>
                <p class="text-muted mb-4" style="font-size:.88rem;">
                    Se eliminará permanentemente la cuenta de
                    <strong>{{ $usuario->nombre_completo }}</strong>
                    ({{ $usuario->email }}).
                    Esta acción no se puede deshacer.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn px-4"
                                style="background:var(--secondary);color:#fff;border-radius:8px;">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleActivoHero(userId) {
    const btn        = document.getElementById('btn-toggle-hero');
    const icon       = document.getElementById('hero-toggle-icon');
    const label      = document.getElementById('hero-toggle-label');
    const heroBadge  = document.getElementById('hero-status-badge');
    const heroText   = document.getElementById('hero-status-text');
    const statusPill = document.getElementById('status-pill');
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    btn.disabled = true;

    fetch(`/admin/usuarios/${userId}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
    .then(data => {
        const activo = data.activo;

        // Hero badge
        heroBadge.className = 'hero-badge ' + (activo ? 'hero-badge-green' : 'hero-badge-red');
        heroText.textContent = activo ? 'Activo' : 'Inactivo';

        // Toggle button
        icon.className  = 'bi ' + (activo ? 'bi-toggle-on' : 'bi-toggle-off');
        label.textContent = activo ? 'Activo' : 'Inactivo';
        btn.title = activo ? 'Desactivar cuenta' : 'Activar cuenta';

        // Status pill in info card
        statusPill.className = 'status-pill ' + (activo ? 'pill-activo' : 'pill-inactivo');
        statusPill.innerHTML = `<i class="bi ${activo ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i> ${activo ? 'Activo' : 'Inactivo'}`;
    })
    .catch(() => alert('No se pudo cambiar el estado. Intente nuevamente.'))
    .finally(() => { btn.disabled = false; });
}
</script>
@endpush
