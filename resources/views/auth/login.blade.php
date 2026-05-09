<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar Sesión — {{ $ls['system_name'] ?? 'ZuraEdu' }}</title>

    <!-- Google Fonts: Inter -->
    
    
    

    <!-- Bootstrap 5.3.2 CSS -->
    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">

    <!-- Bootstrap Icons 1.11.3 -->
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
            --primary: {{ $lBg2 }};
            --primary-dark: {{ $lBg1 }};
            --secondary: {{ $lAcc }};
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f4f8;
        }

        /* ── LEFT PANEL ──────────────────────────────────────────── */
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

        /* Decorative background circles */
        .panel-left::before,
        .panel-left::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.06;
            background: #ffffff;
        }

        .panel-left::before {
            width: 420px;
            height: 420px;
            top: -120px;
            right: -140px;
        }

        .panel-left::after {
            width: 300px;
            height: 300px;
            bottom: -80px;
            left: -100px;
        }

        .panel-left-content {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        /* Logo badge */
        .logo-badge {
            width: 70px;
            height: 70px;
            background-color: var(--secondary);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28pt;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -1px;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.45);
            flex-shrink: 0;
        }

        .school-name {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.4rem;
            line-height: 1.3;
            margin-top: 1.25rem;
            margin-bottom: 0.4rem;
        }

        .school-subtitle {
            color: #a8c0e8;
            font-size: 0.95rem;
            font-weight: 400;
            letter-spacing: 0.02em;
        }

        /* Feature bullets */
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2.25rem 0 0;
            width: 100%;
            max-width: 280px;
            text-align: left;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: #d6e4f7;
            font-size: 0.875rem;
            font-weight: 400;
            padding: 0.45rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li i {
            font-size: 1rem;
            color: #a8c0e8;
            flex-shrink: 0;
        }

        /* Footer text on left panel */
        .panel-left-footer {
            position: relative;
            z-index: 1;
            color: rgba(168, 192, 232, 0.65);
            font-size: 0.75rem;
            text-align: center;
            padding-top: 1rem;
        }

        /* ── RIGHT PANEL ─────────────────────────────────────────── */
        .panel-right {
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .form-card {
            width: 100%;
            max-width: 420px;
        }

        .form-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.3rem;
        }

        .form-lead {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        /* Input focus override */
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.15);
        }

        .input-group .form-control:focus {
            z-index: 3;
        }

        .input-group-text {
            background-color: #f8fafc;
            border-color: #dee2e6;
            color: #6b7280;
        }

        /* Submit button */
        .btn-login {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.2s ease, transform 0.1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background-color: var(--primary-dark);
            color: #ffffff;
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        .btn-toggle-eye {
            background-color: #f8fafc;
            border-color: #dee2e6;
            color: #6b7280;
            transition: color 0.15s;
        }

        .btn-toggle-eye:hover {
            color: var(--primary);
            background-color: #f0f4f8;
            border-color: #dee2e6;
        }

        .btn-toggle-eye:focus {
            box-shadow: none;
        }

        /* Security note */
        .security-note {
            color: #9ca3af;
            font-size: 0.775rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            justify-content: center;
        }

        .security-note i {
            color: #6b7280;
        }

        /* Admin note */
        .admin-note {
            color: #9ca3af;
            font-size: 0.8rem;
            text-align: center;
        }

        /* ── MOBILE OVERRIDES ────────────────────────────────────── */
        @media (max-width: 991.98px) {
            .panel-left {
                min-height: unset;
                padding: 1.5rem 1.5rem 1.25rem;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.75rem;
            }

            .panel-left::before,
            .panel-left::after {
                display: none;
            }

            .panel-left-content {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                align-items: center;
                gap: 0.75rem;
                text-align: left;
            }

            .logo-badge {
                width: 48px;
                height: 48px;
                font-size: 18pt;
                border-radius: 10px;
            }

            .school-name {
                font-size: 1.05rem;
                margin-top: 0;
                margin-bottom: 0.1rem;
            }

            .school-subtitle {
                font-size: 0.8rem;
            }

            .feature-list {
                display: none;
            }

            .panel-left-footer {
                display: none;
            }

            .panel-right {
                min-height: unset;
                padding: 2rem 1.25rem 2.5rem;
                align-items: flex-start;
            }

            .panel-right .form-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        {{-- ── LEFT PANEL ─────────────────────────────────────── --}}
        <div class="col-lg-6 panel-left">
            <div class="panel-left-content">

                {{-- Logo badge --}}
                <div class="logo-badge" style="background-color:{{ $lAcc }};box-shadow:0 8px 24px {{ $lAcc }}70;">
                    {{ strtoupper(substr($ls['system_abbr'] ?? 'ZE', 0, 4)) }}
                </div>

                {{-- School identity --}}
                <div>
                    <p class="school-name">{!! nl2br(e($ls['login_titulo'] ?? ($ls['system_name'] ?? 'ZuraEdu'))) !!}</p>
                    <p class="school-subtitle mb-0">{{ $ls['login_subtitulo'] ?? 'Plataforma de Gestión Escolar' }}</p>
                </div>

                {{-- Feature bullets --}}
                <ul class="feature-list">
                    <li>
                        <i class="bi bi-journal-check"></i>
                        Gestión de Calificaciones
                    </li>
                    <li>
                        <i class="bi bi-calendar-check"></i>
                        Control de Asistencia
                    </li>
                    <li>
                        <i class="bi bi-file-earmark-text"></i>
                        Boletines Académicos
                    </li>
                </ul>

            </div>{{-- /.panel-left-content --}}

            <p class="panel-left-footer">
                &copy; {{ date('Y') }} {{ $ls['system_abbr'] ?? 'ZuraEdu' }}
                @if(!empty($ls['system_name']) && ($ls['system_abbr'] ?? '') !== ($ls['system_name'] ?? ''))
                    &middot; {{ Illuminate\Support\Str::limit($ls['system_name'], 50) }}
                @endif
            </p>
        </div>{{-- /.panel-left --}}

        {{-- ── RIGHT PANEL ────────────────────────────────────── --}}
        <div class="col-lg-6 panel-right">
            <div class="form-card">

                <h1 class="form-title">Bienvenido</h1>
                <p class="form-lead">Ingresa tus credenciales para acceder al sistema</p>

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li style="font-size:.875rem;">{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                {{-- Session status --}}
                @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show py-2 px-3 mb-3" role="alert" style="font-size:.875rem;">
                        {{ session('status') }}
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                {{-- Login form --}}
                <form method="POST" action="{{ route('login.post') }}" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Correo Electrónico
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope-at"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control rounded-3"
                                style="border-top-left-radius:0!important;border-bottom-left-radius:0!important;"
                                placeholder="usuario@correo.com"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                style="border-radius:0;"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="btn btn-toggle-eye rounded-end rounded-3"
                                id="togglePassword"
                                aria-label="Mostrar u ocultar contraseña"
                                tabindex="-1"
                            >
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me + forgot password --}}
                    <div class="mb-4 d-flex align-items-center justify-content-between">
                        <div class="form-check mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember"
                                name="remember"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="remember" style="font-size:.875rem;color:#4b5563;">
                                Recordarme
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" style="font-size:.85rem;color:var(--primary);text-decoration:none;font-weight:500;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-login mb-3">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Iniciar Sesión
                    </button>

                    {{-- Register link (configurable) --}}
                    @if(($ls['login_allow_reg'] ?? '1') === '1')
                    <p class="admin-note mb-3" style="border-top:1px solid #f0f4f8;padding-top:.75rem;">
                        ¿Primera vez?
                        <a href="{{ route('register') }}" style="color:var(--primary);font-weight:600;text-decoration:none;">
                            &rarr; Solicitar acceso al sistema
                        </a>
                    </p>
                    @endif

                    {{-- Security note --}}
                    <p class="security-note">
                        <i class="bi bi-shield-check"></i>
                        Conexión protegida &middot; Máx. 5 intentos
                    </p>

                </form>{{-- /form --}}

            </div>{{-- /.form-card --}}
        </div>{{-- /.panel-right --}}

    </div>{{-- /.row --}}
</div>{{-- /.container-fluid --}}

<!-- Bootstrap 5.3.2 JS Bundle -->
<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        var toggleBtn   = document.getElementById('togglePassword');
        var pwdInput    = document.getElementById('password');
        var toggleIcon  = document.getElementById('togglePasswordIcon');

        if (toggleBtn && pwdInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                var isPassword = pwdInput.type === 'password';
                pwdInput.type  = isPassword ? 'text' : 'password';
                toggleIcon.classList.toggle('bi-eye',      !isPassword);
                toggleIcon.classList.toggle('bi-eye-slash', isPassword);
                toggleBtn.setAttribute('aria-pressed', String(isPassword));
            });
        }
    })();
</script>

</body>
</html>
