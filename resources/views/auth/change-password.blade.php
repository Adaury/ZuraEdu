<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña — SGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .change-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .brand-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #1e3a6e, #2563eb);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            margin: 0 auto 1.2rem;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .req-list { font-size: .78rem; color: #6b7280; margin-top: .3rem; padding-left: 1rem; }
        .req-list li { margin-bottom: .15rem; }
        .btn-primary-custom {
            background: linear-gradient(135deg, #1e3a6e, #2563eb);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            padding: .7rem;
            font-size: .95rem;
            transition: opacity .2s;
        }
        .btn-primary-custom:hover { opacity: .92; }
        .password-wrapper { position: relative; }
        .toggle-pass {
            position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #9ca3af; border: none; background: none; padding: 0;
        }
        .toggle-pass:hover { color: #374151; }
    </style>
</head>
<body>

<div class="change-card">
    <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>

    <h5 class="fw-bold text-center mb-1" style="color:#1e3a6e;">Cambiar Contraseña</h5>
    <p class="text-center text-muted mb-4" style="font-size:.84rem;">
        Tu cuenta requiere que establezcas una nueva contraseña antes de continuar.
    </p>

    {{-- Usuario actual --}}
    <div class="d-flex align-items-center gap-2 p-2 mb-4 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
        <div style="width:36px;height:36px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <div style="font-size:.82rem;font-weight:700;color:#1e3a6e;">{{ Auth::user()->name }} {{ Auth::user()->apellidos }}</div>
            <div style="font-size:.74rem;color:#6b7280;">{{ Auth::user()->email }}</div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;border-radius:10px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf

        {{-- Nueva contraseña --}}
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;color:#374151;">
                Nueva contraseña
            </label>
            <div class="password-wrapper">
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Mínimo 8 caracteres"
                       style="border-radius:10px;padding-right:2.5rem;"
                       autofocus>
                <button type="button" class="toggle-pass" onclick="toggleVis('password','eye1')">
                    <i class="bi bi-eye" id="eye1"></i>
                </button>
            </div>
            <ul class="req-list">
                <li>Al menos 8 caracteres</li>
                <li>Usa letras y números para mayor seguridad</li>
            </ul>
        </div>

        {{-- Confirmar contraseña --}}
        <div class="mb-4">
            <label class="form-label fw-semibold" style="font-size:.85rem;color:#374151;">
                Confirmar contraseña
            </label>
            <div class="password-wrapper">
                <input type="password"
                       name="password_confirmation"
                       id="password_confirmation"
                       class="form-control"
                       placeholder="Repite la nueva contraseña"
                       style="border-radius:10px;padding-right:2.5rem;">
                <button type="button" class="toggle-pass" onclick="toggleVis('password_confirmation','eye2')">
                    <i class="bi bi-eye" id="eye2"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 text-white">
            <i class="bi bi-check-lg me-2"></i>Guardar nueva contraseña
        </button>
    </form>

    <div class="text-center mt-3">
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-muted p-0" style="font-size:.78rem;">
                <i class="bi bi-box-arrow-left me-1"></i>Cerrar sesión
            </button>
        </form>
    </div>
</div>

<script>
function toggleVis(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    if (f.type === 'password') {
        f.type = 'text';
        i.className = 'bi bi-eye-slash';
    } else {
        f.type = 'password';
        i.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
