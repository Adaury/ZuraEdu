@extends('layouts.admin')
@section('page-title', 'Editar Usuario')

@push('styles')
<style>
    .form-section {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .section-title {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .5rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .form-label {
        font-size: .82rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .35rem;
    }
    .form-control,
    .form-select {
        border-radius: 8px;
        border-color: #d1d5db;
        font-size: .875rem;
        padding: .5rem .8rem;
        transition: border-color .18s, box-shadow .18s;
    }
    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
    }
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: var(--secondary);
    }
    .invalid-feedback { font-size: .78rem; }
    .required-mark    { color: var(--secondary); }

    /* Password hint */
    .password-hint {
        font-size: .76rem;
        color: #9ca3af;
        margin-top: .3rem;
    }

    /* Role hint */
    .role-hint {
        font-size: .76rem;
        color: #6b7280;
        margin-top: .3rem;
    }

    /* Activo toggle switch */
    .activo-toggle-wrap {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .activo-toggle-wrap .form-check-input {
        width: 2.4rem;
        height: 1.25rem;
        cursor: pointer;
    }
    .activo-toggle-wrap .form-check-input:checked {
        background-color: #10b981;
        border-color: #10b981;
    }
    .activo-toggle-wrap .form-check-label {
        font-size: .875rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        margin-bottom: 0;
    }
    .activo-status-text {
        font-size: .78rem;
        color: #6b7280;
        margin-top: .15rem;
    }

    /* User meta badge in header */
    .user-meta-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: #f0f4f8;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: .2rem .75rem;
        font-size: .78rem;
        color: #4b5563;
        font-weight: 500;
    }
    [data-theme="dark"] .form-section { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.usuarios.index') }}"
       class="btn btn-sm btn-outline-secondary mt-1" style="border-radius:8px;flex-shrink:0;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-1" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-person-gear me-2" style="color:var(--secondary);"></i>Editar
            Usuario&nbsp;&mdash;&nbsp;{{ $usuario->name }}{{ $usuario->apellidos ? ' '.$usuario->apellidos : '' }}
        </h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="user-meta-badge">
                <i class="bi bi-envelope"></i>{{ $usuario->email }}
            </span>
            <span class="user-meta-badge">
                <i class="bi bi-hash"></i>ID {{ $usuario->id }}
            </span>
            @php $rolActual = $usuario->getRoleNames()->first(); @endphp
            @if($rolActual)
                <span class="user-meta-badge">
                    <i class="bi bi-person-badge"></i>{{ $rolActual }}
                </span>
            @endif
        </div>
    </div>
</div>

<form method="POST"
      action="{{ route('admin.usuarios.update', $usuario) }}"
      novalidate>
    @csrf
    @method('PUT')

    {{-- ─── INFORMACIÓN PERSONAL ──────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-person-badge"></i>Información Personal
        </div>

        <div class="row g-3">
            {{-- Nombres --}}
            <div class="col-md-6">
                <label for="name" class="form-label">
                    Nombres <span class="required-mark">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $usuario->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Ej: Luis Alejandro"
                       required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Apellidos --}}
            <div class="col-md-6">
                <label for="apellidos" class="form-label">Apellidos</label>
                <input type="text"
                       id="apellidos"
                       name="apellidos"
                       value="{{ old('apellidos', $usuario->apellidos) }}"
                       class="form-control @error('apellidos') is-invalid @enderror"
                       placeholder="Ej: Martínez Díaz">
                @error('apellidos')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- ─── CONTACTO ───────────────────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-envelope"></i>Datos de Contacto
        </div>

        <div class="row g-3">
            {{-- Email --}}
            <div class="col-md-7">
                <label for="email" class="form-label">
                    Correo Electrónico <span class="required-mark">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white" style="border-radius:8px 0 0 8px;">
                        <i class="bi bi-envelope text-muted" style="font-size:.85rem;"></i>
                    </span>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', $usuario->email) }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="usuario@ejemplo.com"
                           required
                           style="border-radius:0 8px 8px 0;">
                </div>
                @error('email')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Teléfono --}}
            <div class="col-md-5">
                <label for="telefono" class="form-label">Teléfono</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" style="border-radius:8px 0 0 8px;">
                        <i class="bi bi-telephone text-muted" style="font-size:.85rem;"></i>
                    </span>
                    <input type="text"
                           id="telefono"
                           name="telefono"
                           value="{{ old('telefono', $usuario->telefono) }}"
                           class="form-control @error('telefono') is-invalid @enderror"
                           placeholder="(809) 000-0000"
                           style="border-radius:0 8px 8px 0;">
                </div>
                @error('telefono')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- ─── CONTRASEÑA ─────────────────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-shield-lock"></i>Cambiar Contraseña
        </div>

        <div class="row g-3">
            {{-- Password --}}
            <div class="col-md-6">
                <label for="password" class="form-label">Nueva Contraseña</label>
                <div class="input-group">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Dejar vacío para no cambiar"
                           autocomplete="new-password"
                           style="border-radius:8px 0 0 8px;">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            style="border-radius:0 8px 8px 0;"
                            onclick="togglePassword('password', this)"
                            title="Mostrar / ocultar">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
                <p class="password-hint">
                    <i class="bi bi-info-circle me-1"></i>
                    Dejar en blanco para mantener la contraseña actual. Si se cambia, mínimo 8 caracteres.
                </p>
            </div>

            {{-- Password confirmation --}}
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                <div class="input-group">
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control"
                           placeholder="Dejar vacío para no cambiar"
                           autocomplete="new-password"
                           style="border-radius:8px 0 0 8px;">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            style="border-radius:0 8px 8px 0;"
                            onclick="togglePassword('password_confirmation', this)"
                            title="Mostrar / ocultar">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── ROL ────────────────────────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-person-gear"></i>Rol del Sistema
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="role" class="form-label">
                    Rol asignado <span class="required-mark">*</span>
                </label>
                <select id="role"
                        name="role"
                        class="form-select @error('role') is-invalid @enderror"
                        required>
                    <option value="">— Seleccionar rol —</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->name }}"
                            {{ old('role', $rolActual) === $rol->name ? 'selected' : '' }}>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="role-hint">
                    <i class="bi bi-info-circle me-1"></i>
                    Cambiar el rol actualizará inmediatamente los permisos del usuario.
                </p>
            </div>
        </div>
    </div>

    {{-- ─── ESTADO DE CUENTA ───────────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-toggles"></i>Estado de la Cuenta
        </div>

        <div class="activo-toggle-wrap" style="max-width:420px;">
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="activo" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       id="activo"
                       name="activo"
                       value="1"
                       {{ old('activo', $usuario->activo) ? 'checked' : '' }}>
                <label class="form-check-label" for="activo">
                    Cuenta activa
                </label>
            </div>
            <div>
                <p class="activo-status-text mb-0" id="activoStatusText">
                    @if(old('activo', $usuario->activo))
                        El usuario puede iniciar sesión en el sistema.
                    @else
                        El usuario no puede iniciar sesión en el sistema.
                    @endif
                </p>
            </div>
        </div>
        @error('activo')
            <div class="text-danger mt-2" style="font-size:.78rem;">{{ $message }}</div>
        @enderror
    </div>

    {{-- ─── Actions ────────────────────────────────────────────────── --}}
    <div class="d-flex gap-2 justify-content-end mb-4 flex-wrap">
        <button type="button" class="btn btn-outline-warning px-3" style="border-radius:8px;"
                data-bs-toggle="modal" data-bs-target="#modalResetPass">
            <i class="bi bi-key me-1"></i>Restablecer contraseña
        </button>
        <a href="{{ route('admin.usuarios.index') }}"
           class="btn btn-outline-secondary px-4" style="border-radius:8px;">
            Cancelar
        </a>
        <button type="submit"
                class="btn px-4 fw-semibold"
                style="background:var(--primary);color:#fff;border-radius:8px;">
            <i class="bi bi-floppy me-1"></i>Guardar Cambios
        </button>
    </div>

</form>

{{-- Modal Reset Password --}}
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
                        Establece una nueva contraseña para <strong>{{ $usuario->nombre_completo }}</strong>.
                        El usuario deberá cambiarla al iniciar sesión.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control form-control-sm"
                               minlength="8" required placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="mb-1">
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

@endsection

@push('scripts')
<script>
/**
 * Toggle password field visibility.
 * @param {string} fieldId  - the input element id
 * @param {HTMLElement} btn - the toggle button
 */
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type     = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type     = 'password';
        icon.className = 'bi bi-eye';
    }
}

/**
 * Live-update the status description text when the activo checkbox changes.
 */
document.getElementById('activo').addEventListener('change', function () {
    const text = document.getElementById('activoStatusText');
    text.textContent = this.checked
        ? 'El usuario puede iniciar sesión en el sistema.'
        : 'El usuario no puede iniciar sesión en el sistema.';
});
</script>
@endpush
