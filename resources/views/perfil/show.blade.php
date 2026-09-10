@php
    $user   = auth()->user();
    $isAdmin = $user->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Docente']);
    $layout  = $isAdmin ? 'layouts.admin' : 'layouts.portal';
@endphp

@extends($layout)
@section('page-title', 'Mi Perfil')
@if(!$isAdmin)
@section('portal-name', 'Mi Perfil')
@section('sidebar')
    <div class="prt-sidebar-section">Perfil</div>
    <a href="#info" class="prt-sidebar-link"><i class="bi bi-person"></i>Información</a>
    <a href="#seguridad" class="prt-sidebar-link"><i class="bi bi-shield-lock"></i>Seguridad</a>
    <a href="#notificaciones" class="prt-sidebar-link"><i class="bi bi-bell"></i>Notificaciones</a>
    <div class="prt-sidebar-section mt-3">Cuenta</div>
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
            <i class="bi bi-box-arrow-right"></i>Cerrar sesión
        </button>
    </form>
@endsection
@endif

@push('styles')
<style>
.pf-wrap { max-width: 680px; }
.pf-card {
    background: var(--prt-card, #fff);
    border: 1px solid var(--prt-border, #e5e7eb);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 6px rgba(0,0,0,.04);
}
.pf-card-header {
    background: linear-gradient(135deg, var(--primary, #2563eb), #3b82f6);
    color: #fff;
    padding: .85rem 1.25rem;
    display: flex; align-items: center; gap: .6rem;
}
.pf-card-header h2 { font-size: .95rem; font-weight: 700; margin: 0; }
.pf-card-body { padding: 1.25rem; }

/* Avatar */
.pf-avatar-wrap {
    display: flex; align-items: center; gap: 1.25rem;
    margin-bottom: 1.25rem; flex-wrap: wrap;
}
.pf-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    object-fit: cover; border: 3px solid #e5e7eb;
    flex-shrink: 0;
}
.pf-avatar-letter {
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--primary, #2563eb); color: #fff;
    font-size: 2rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pf-avatar-actions { display: flex; flex-direction: column; gap: .4rem; }

.form-label-pf { font-size: .83rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
.pf-rol-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe; border-radius: 8px;
    padding: .3rem .75rem; font-size: .8rem; font-weight: 700;
    margin-bottom: 1rem;
}

.pf-notif-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; padding: .7rem 0; border-bottom: 1px solid var(--prt-border, #f3f4f6);
}
.pf-notif-row:last-child { border-bottom: none; }
.pf-notif-icono {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; color: #fff; font-size: .9rem;
}
.pf-notif-label { font-size: .87rem; font-weight: 600; }
.pf-notif-desc { font-size: .76rem; color: #6b7280; }
.pf-notif-bloqueado { font-size: .72rem; color: #9ca3af; margin-top: .1rem; }
</style>
@endpush

@section('content')
<div class="pf-wrap">

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
</div>
@endif

{{-- ── Información personal ──────────────────────────────────────── --}}
<div class="pf-card" id="info">
    <div class="pf-card-header">
        <i class="bi bi-person-circle"></i>
        <h2>Información Personal</h2>
    </div>
    <div class="pf-card-body">

        {{-- Avatar --}}
        <div class="pf-avatar-wrap">
            @if($user->photo_url)
                <img src="{{ $user->photo_url }}" alt="Foto" class="pf-avatar">
            @else
                <div class="pf-avatar-letter">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            @endif
            <div class="pf-avatar-actions">
                <form method="POST" action="{{ route('perfil.foto') }}" enctype="multipart/form-data">
                    @csrf
                    <label style="cursor:pointer;">
                        <span class="btn btn-sm btn-outline-primary" style="font-size:.78rem;pointer-events:none;">
                            <i class="bi bi-camera me-1"></i>Cambiar foto
                        </span>
                        <input type="file" name="photo" accept="image/*" style="display:none;"
                               onchange="this.closest('form').submit()">
                    </label>
                </form>
                @if($user->profile_photo)
                <form method="POST" action="{{ route('perfil.foto.delete') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;"
                            onclick="return confirm('¿Eliminar foto?')">
                        <i class="bi bi-trash me-1"></i>Quitar foto
                    </button>
                </form>
                @endif
                <div style="font-size:.72rem;color:#6b7280;">JPG, PNG, WEBP · Máx. 2MB</div>
            </div>
        </div>

        {{-- Rol --}}
        <div class="pf-rol-badge">
            <i class="bi bi-shield-check"></i>
            {{ $user->getRoleNames()->first() ?? 'Usuario' }}
        </div>

        {{-- Formulario --}}
        <form method="POST" action="{{ route('perfil.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label-pf">Nombre(s)</label>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label-pf">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control form-control-sm"
                           value="{{ old('apellidos', $user->apellidos) }}">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-pf">Correo electrónico</label>
                    <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label-pf">Teléfono</label>
                    <input type="text" name="telefono" class="form-control form-control-sm"
                           value="{{ old('telefono', $user->telefono) }}" placeholder="Opcional">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Seguridad / Contraseña ────────────────────────────────────── --}}
<div class="pf-card" id="seguridad">
    <div class="pf-card-header" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
        <i class="bi bi-shield-lock"></i>
        <h2>Cambiar Contraseña</h2>
    </div>
    <div class="pf-card-body">
        <form method="POST" action="{{ route('perfil.password') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label-pf">Contraseña actual</label>
                    <input type="password" name="current_password"
                           class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                           autocomplete="current-password" required>
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label-pf">Nueva contraseña</label>
                    <input type="password" name="password"
                           class="form-control form-control-sm @error('password') is-invalid @enderror"
                           autocomplete="new-password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label-pf">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation"
                           class="form-control form-control-sm" autocomplete="new-password" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-sm px-4" style="background:#0f766e;color:#fff;border:none;">
                    <i class="bi bi-lock me-1"></i>Actualizar contraseña
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Notificaciones ────────────────────────────────────────────── --}}
<div class="pf-card" id="notificaciones">
    <div class="pf-card-header" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
        <i class="bi bi-bell"></i>
        <h2>Mis Notificaciones</h2>
    </div>
    <div class="pf-card-body">
        <p class="small text-muted mb-3">
            Elige por qué temas quieres que te suene el celular. Todas las notificaciones seguirán apareciendo en la campanita del sistema — esto solo controla las alertas push de la app móvil.
        </p>
        <form method="POST" action="{{ route('perfil.notificaciones') }}">
            @csrf
            @foreach($categorias as $clave => $meta)
                @php($institucionActiva = \App\Services\NotificacionPreferenciaService::pushInstitucionActivoCategoria($clave))
                <div class="pf-notif-row">
                    <div class="d-flex align-items-center gap-2">
                        <div class="pf-notif-icono" style="background:{{ $meta['color'] }};">
                            <i class="bi {{ $meta['icono'] }}"></i>
                        </div>
                        <div>
                            <div class="pf-notif-label">{{ $meta['label'] }}</div>
                            <div class="pf-notif-desc">{{ $meta['desc'] }}</div>
                            @unless($institucionActiva)
                                <div class="pf-notif-bloqueado"><i class="bi bi-lock-fill"></i> Desactivado por el centro educativo</div>
                            @endunless
                        </div>
                    </div>
                    <input type="checkbox" class="form-check-input" name="push_{{ $clave }}" value="1"
                           {{ $user->pushActivo($clave) ? 'checked' : '' }}
                           {{ $institucionActiva ? '' : 'disabled' }}
                           style="width:2.5rem;height:1.4rem;flex-shrink:0;">
                </div>
            @endforeach
            <div class="mt-3">
                <button type="submit" class="btn btn-sm px-4" style="background:#b45309;color:#fff;border:none;">
                    <i class="bi bi-check-lg me-1"></i>Guardar preferencias
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Info adicional solo lectura --}}
<div class="pf-card">
    <div class="pf-card-header" style="background:linear-gradient(135deg,#6366f1,#818cf8);">
        <i class="bi bi-info-circle"></i>
        <h2>Información de la Cuenta</h2>
    </div>
    <div class="pf-card-body">
        <div class="row g-2" style="font-size:.83rem;">
            <div class="col-sm-6">
                <div style="color:#6b7280;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Cédula</div>
                <div style="font-weight:600;">{{ $user->cedula ?? '—' }}</div>
            </div>
            <div class="col-sm-6">
                <div style="color:#6b7280;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Miembro desde</div>
                <div style="font-weight:600;">{{ $user->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="col-sm-6">
                <div style="color:#6b7280;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Estado</div>
                <span class="badge {{ $user->activo ? 'text-bg-success' : 'text-bg-danger' }}" style="font-size:.72rem;">
                    {{ $user->activo ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            @if($user->area_trabajo)
            <div class="col-sm-6">
                <div style="color:#6b7280;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Área</div>
                <div style="font-weight:600;">{{ $user->area_trabajo }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

</div>
@endsection
