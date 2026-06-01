<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Restablecer Contraseña — SGE</title>

    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">

    @php
        $ls   = \Illuminate\Support\Facades\DB::table('system_settings')->pluck('value','key');
        $lBg1 = $ls['login_color_bg1'] ?? '#0a0f2e';
        $lBg2 = $ls['login_color_bg2'] ?? '#1e3a8a';
        $lBg3 = $ls['login_color_bg3'] ?? '#1d4ed8';
        $lAcc = $ls['login_color_acc'] ?? '#10b981';
    @endphp

    <style>
        :root {
            --primary:      {{ $lBg2 }};
            --primary-dark: {{ $lBg1 }};
            --secondary:    {{ $lAcc }};
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
            background: #f1f5f9;
        }

        /* ── Panel izquierdo ── */
        .panel-left {
            background: linear-gradient(140deg, {{ $lBg1 }} 0%, {{ $lBg2 }} 55%, {{ $lBg3 }} 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 3rem 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .panel-left::before, .panel-left::after {
            content: ''; position: absolute;
            border-radius: 50%; opacity: .06; background: #fff;
        }
        .panel-left::before { width: 420px; height: 420px; top: -120px; right: -140px; }
        .panel-left::after  { width: 300px; height: 300px; bottom: -80px; left: -100px; }
        .panel-left-content {
            position: relative; z-index: 1; text-align: center;
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; width: 100%;
        }
        .logo-badge {
            width: 70px; height: 70px;
            background: var(--secondary);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28pt; font-weight: 700; color: #fff;
            box-shadow: 0 8px 24px rgba(16,185,129,.45);
        }
        .school-name { color: #fff; font-weight: 700; font-size: 1.4rem; line-height: 1.3; margin-top: 1.25rem; margin-bottom: .4rem; }
        .school-subtitle { color: #a8c0e8; font-size: .95rem; }

        .req-box {
            margin-top: 2rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            max-width: 280px;
            text-align: left;
        }
        .req-box-title { color: #fff; font-weight: 700; font-size: .88rem; margin-bottom: .75rem; }
        .req-item {
            display: flex; gap: .65rem; align-items: flex-start;
            color: #d6e4f7; font-size: .8rem; padding: .35rem 0;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .req-item:last-child { border-bottom: none; }
        .req-item i { color: var(--secondary); font-size: .9rem; margin-top: .05rem; flex-shrink: 0; }

        .panel-left-footer {
            position: relative; z-index: 1;
            color: rgba(168,192,232,.65); font-size: .75rem; text-align: center; padding-top: 1rem;
        }

        /* ── Panel derecho ── */
        .panel-right {
            background: #fff;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 3rem 2rem;
        }
        .form-card { width: 100%; max-width: 420px; }
        .form-title { font-size: 1.6rem; font-weight: 700; color: #1a1a2e; margin-bottom: .3rem; }
        .form-lead  { color: #6b7280; font-size: .9rem; margin-bottom: 1.75rem; }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30,64,175,.15);
        }
        .input-group .form-control:focus { z-index: 3; }
        .input-group-text {
            background: #f8fafc; border-color: #dee2e6; color: #6b7280;
        }
        .btn-toggle-eye {
            background: #f8fafc; border-color: #dee2e6; color: #6b7280; transition: color .15s;
        }
        .btn-toggle-eye:hover { color: var(--primary); background: #f0f4f8; border-color: #dee2e6; }
        .btn-toggle-eye:focus { box-shadow: none; }

        .btn-primary-custom {
            background: var(--primary); color: #fff;
            border: none; border-radius: .5rem;
            padding: .7rem 1rem; font-size: .95rem; font-weight: 600;
            width: 100%; transition: background .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-primary-custom:hover  { background: var(--primary-dark); color: #fff; }
        .btn-primary-custom:active { transform: scale(.99); }

        .strength-bar { height: 4px; border-radius: 4px; background: #e5e7eb; overflow: hidden; margin-top: .4rem; }
        .strength-fill { height: 100%; border-radius: 4px; width: 0; transition: width .3s, background .3s; }

        .back-link {
            display: flex; align-items: center; gap: .4rem;
            color: #6b7280; font-size: .85rem; text-decoration: none;
            transition: color .15s;
        }
        .back-link:hover { color: var(--primary); }

        @media (max-width: 991.98px) {
            .panel-left {
                min-height: unset; padding: 1.5rem 1.5rem 1.25rem;
                flex-direction: row; flex-wrap: wrap;
                justify-content: center; gap: .75rem;
            }
            .panel-left::before, .panel-left::after { display: none; }
            .panel-left-content {
                flex-direction: row; flex-wrap: wrap;
                justify-content: center; align-items: center; gap: .75rem; text-align: left;
            }
            .logo-badge { width: 48px; height: 48px; font-size: 18pt; border-radius: 10px; }
            .school-name { font-size: 1.05rem; margin-top: 0; margin-bottom: .1rem; }
            .school-subtitle { font-size: .8rem; }
            .req-box, .panel-left-footer { display: none; }
            .panel-right { min-height: unset; padding: 2rem 1.25rem 2.5rem; align-items: flex-start; }
            .panel-right .form-card { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        {{-- ── Panel izquierdo ── --}}
        <div class="col-lg-6 panel-left">
            <div class="panel-left-content">
                <div class="logo-badge" style="background:{{ $lAcc }};box-shadow:0 8px 24px {{ $lAcc }}70;">
                    {{ strtoupper(substr($ls['system_abbr'] ?? 'SGE', 0, 4)) }}
                </div>
                <div>
                    <p class="school-name">{!! nl2br(e($ls['login_titulo'] ?? ($ls['system_name'] ?? 'Sistema de Gestión Escolar'))) !!}</p>
                    <p class="school-subtitle mb-0">{{ $ls['login_subtitulo'] ?? 'Sistema de Gestión Escolar' }}</p>
                </div>
                <div class="req-box">
                    <div class="req-box-title"><i class="bi bi-key me-2"></i>Requisitos de contraseña</div>
                    <div class="req-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Mínimo 8 caracteres</span>
                    </div>
                    <div class="req-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Usa letras mayúsculas y minúsculas</span>
                    </div>
                    <div class="req-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Incluye al menos un número</span>
                    </div>
                    <div class="req-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Las dos contraseñas deben coincidir</span>
                    </div>
                </div>
            </div>
            <p class="panel-left-footer">&copy; {{ date('Y') }} {{ \App\Helpers\Setting::get('system_name', 'ZuraEdu') }} &middot; República Dominicana</p>
        </div>

        {{-- ── Panel derecho ── --}}
        <div class="col-lg-6 panel-right">
            <div class="form-card">

                <a href="{{ route('login') }}" class="back-link mb-4 d-inline-flex">
                    <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                </a>

                <h1 class="form-title">Nueva contraseña</h1>
                <p class="form-lead">
                    Elige una contraseña segura para tu cuenta.
                </p>

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li style="font-size:.875rem;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf

                    {{-- Token oculto --}}
                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Correo electrónico
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="tu@correo.com"
                                value="{{ old('email', request()->email) }}"
                                autocomplete="email"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    {{-- Nueva contraseña --}}
                    <div class="mb-3">
                        <label for="password" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Nueva contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                style="border-radius:0;"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                required
                                oninput="updateStrength(this.value)"
                            >
                            <button type="button" class="btn btn-toggle-eye rounded-end" id="togglePwd" tabindex="-1">
                                <i class="bi bi-eye" id="togglePwdIcon"></i>
                            </button>
                        </div>
                        <div class="strength-bar mt-1">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <small class="text-muted" id="strengthLabel" style="font-size:.75rem;"></small>
                    </div>

                    {{-- Confirmar contraseña --}}
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Confirmar contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                style="border-radius:0;"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="btn btn-toggle-eye rounded-end" id="togglePwdConf" tabindex="-1">
                                <i class="bi bi-eye" id="togglePwdConfIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-custom mb-3">
                        <i class="bi bi-shield-check"></i>
                        Restablecer contraseña
                    </button>

                    <p style="font-size:.8rem;color:#9ca3af;text-align:center;">
                        <i class="bi bi-info-circle me-1"></i>
                        Una vez restablecida, serás redirigido al inicio de sesión.
                    </p>
                </form>

            </div>
        </div>

    </div>
</div>

<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // Toggle password visibility
    function makeToggle(btnId, iconId, inputId) {
        var btn   = document.getElementById(btnId);
        var icon  = document.getElementById(iconId);
        var input = document.getElementById(inputId);
        if (!btn) return;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye',       !show);
            icon.classList.toggle('bi-eye-slash',  show);
        });
    }
    makeToggle('togglePwd',     'togglePwdIcon',     'password');
    makeToggle('togglePwdConf', 'togglePwdConfIcon', 'password_confirmation');

    // Password strength meter
    window.updateStrength = function (val) {
        var fill  = document.getElementById('strengthFill');
        var label = document.getElementById('strengthLabel');
        if (!fill) return;
        var score = 0;
        if (val.length >= 8)              score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[0-9]/.test(val))            score++;
        if (/[^A-Za-z0-9]/.test(val))     score++;

        var pct   = ['0%','30%','55%','80%','100%'][score];
        var color = ['#e5e7eb','#ef4444','#f59e0b','#3b82f6','#10b981'][score];
        var text  = ['','Muy débil','Débil','Buena','Fuerte'][score];

        fill.style.width      = pct;
        fill.style.background = color;
        label.textContent     = text;
        label.style.color     = color;
    };
})();
</script>
</body>
</html>
