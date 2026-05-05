@extends('layouts.admin')
@section('page-title', 'Nuevo Usuario')

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

    /* Password strength hint */
    .password-hint {
        font-size: .76rem;
        color: #9ca3af;
        margin-top: .3rem;
    }

    /* Role option description */
    .role-hint {
        font-size: .76rem;
        color: #6b7280;
        margin-top: .3rem;
    }
    [data-theme="dark"] .form-section { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.usuarios.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-person-plus me-2" style="color:var(--secondary);"></i>Nuevo Usuario
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">Crea una nueva cuenta de acceso al sistema</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.usuarios.store') }}" novalidate>
    @csrf

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
                       value="{{ old('name') }}"
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
                       value="{{ old('apellidos') }}"
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
                           value="{{ old('email') }}"
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
                           value="{{ old('telefono') }}"
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
            <i class="bi bi-shield-lock"></i>Contraseña
        </div>

        <div class="row g-3">
            {{-- Password --}}
            <div class="col-md-6">
                <label for="password" class="form-label">
                    Contraseña <span class="required-mark">*</span>
                </label>
                <div class="input-group">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Mínimo 8 caracteres"
                           required
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
                <p class="password-hint"><i class="bi bi-info-circle me-1"></i>Al menos 8 caracteres.</p>
            </div>

            {{-- Password confirmation --}}
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">
                    Confirmar Contraseña <span class="required-mark">*</span>
                </label>
                <div class="input-group">
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control"
                           placeholder="Repite la contraseña"
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
                            {{ old('role') === $rol->name ? 'selected' : '' }}>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="role-hint">
                    <i class="bi bi-info-circle me-1"></i>
                    El rol determina los permisos y accesos dentro del sistema.
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Actions ────────────────────────────────────────────────── --}}
    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="{{ route('admin.usuarios.index') }}"
           class="btn btn-outline-secondary px-4" style="border-radius:8px;">
            Cancelar
        </a>
        <button type="submit"
                class="btn px-4 fw-semibold"
                style="background:var(--primary);color:#fff;border-radius:8px;">
            <i class="bi bi-floppy me-1"></i>Guardar Usuario
        </button>
    </div>

</form>

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
        input.type  = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type  = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endpush
